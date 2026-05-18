# Phase 6 — Deployment & CI/CD Pipeline

> **Claude Code instruction:** Read this file and execute every step in order. This phase sets up GitHub Actions to automatically test and deploy on every push to `main`. Run the verification command at the end of each step before proceeding.

---

## Step 1 — Create the full GitHub Actions deploy workflow

**File:** `.github/workflows/deploy.yml` (replace or create)

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test-and-deploy:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: easy_go_shopping_test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      # ── Checkout ────────────────────────────────────────────────────────
      - uses: actions/checkout@v4

      # ── PHP setup ───────────────────────────────────────────────────────
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql, mbstring, fileinfo
          coverage: none

      # ── Node setup ──────────────────────────────────────────────────────
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      # ── Install dependencies ─────────────────────────────────────────────
      - name: Install PHP dependencies
        run: composer install --no-dev --optimize-autoloader --no-interaction

      - name: Install Node dependencies
        run: npm ci

      - name: Build frontend assets
        run: npm run build

      # ── Environment setup ────────────────────────────────────────────────
      - name: Set up .env for testing
        run: |
          cp .env.example .env
          php artisan key:generate
          sed -i 's/DB_DATABASE=.*/DB_DATABASE=easy_go_shopping_test/' .env
          sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=secret/' .env

      # ── Run migrations and tests ─────────────────────────────────────────
      - name: Run database migrations
        run: php artisan migrate --force

      - name: Run PHPUnit tests
        run: php artisan test --stop-on-failure

      # ── Deploy (only if tests pass) ──────────────────────────────────────
      - name: Deploy to server via SSH
        if: success()
        uses: appleboy/ssh-action@v1.0.0
        with:
          host:     ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key:      ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            set -e
            cd /var/www/easy-go-shopping

            echo ">>> Pulling latest code"
            git pull origin main

            echo ">>> Installing PHP dependencies"
            composer install --no-dev --optimize-autoloader --no-interaction

            echo ">>> Installing Node dependencies and building"
            npm ci
            npm run build

            echo ">>> Running migrations"
            php artisan migrate --force

            echo ">>> Clearing and rebuilding caches"
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan event:cache

            echo ">>> Restarting queue workers"
            php artisan queue:restart

            echo ">>> Reloading PHP-FPM"
            sudo systemctl reload php8.3-fpm

            echo ">>> Deploy complete"
```

---

## Step 2 — Set GitHub repository secrets

Go to your GitHub repository:
**Settings → Secrets and variables → Actions → New repository secret**

Create **each** of these secrets:

| Secret name | Value |
|---|---|
| `SERVER_HOST` | Your server IP address or domain (e.g. `123.45.67.89`) |
| `SERVER_USER` | SSH username (e.g. `ubuntu` or `deploy`) |
| `SSH_PRIVATE_KEY` | Full content of your private key (`cat ~/.ssh/id_rsa`) |

> ⚠️ **Note:** Never put these values in any file in the repository. Only in GitHub Secrets.

---

## Step 3 — Set up the production server (run once)

SSH into your production server and run these commands:

```bash
# Install required software (Ubuntu/Debian)
sudo apt update
sudo apt install -y nginx php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip mysql-server nodejs npm git

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Create project directory
sudo mkdir -p /var/www/easy-go-shopping
sudo chown -R $USER:$USER /var/www/easy-go-shopping

# Clone the repository
cd /var/www/easy-go-shopping
git clone https://github.com/YOUR_USERNAME/easy-go-shopping.git .

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Set up .env
cp .env.example .env
php artisan key:generate
# Edit .env with production values (see Step 4)

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Run migrations and seed
php artisan migrate --force
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=AdminSeeder

# Create storage symlink
php artisan storage:link
```

---

## Step 4 — Production `.env` values

**On the production server**, open `/var/www/easy-go-shopping/.env` and set:

```env
APP_NAME="Easy Go Shopping"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_go_shopping
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_db_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=public
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

> ⚠️ **For production with high traffic**, upgrade `CACHE_DRIVER` and `SESSION_DRIVER` to `redis` once Redis is installed.

---

## Step 5 — Configure Nginx

**File:** `/etc/nginx/sites-available/easy-go-shopping` (on the production server)

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/easy-go-shopping/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable the site
sudo ln -s /etc/nginx/sites-available/easy-go-shopping /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Step 6 — Allow PHP-FPM reload without sudo password (for CI/CD)

On the production server:

```bash
sudo visudo
```

Add this line (replace `ubuntu` with your SSH username):

```
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
```

---

## Step 7 — Trigger first deployment

```bash
# From your local machine
git add .
git commit -m "ci: add full deploy workflow"
git push origin main
```

Then watch the Actions tab:
`github.com/YOUR_USERNAME/easy-go-shopping/actions`

> ✅ **Verify:** GitHub Actions shows a green checkmark. Visit `https://your-domain.com/admin` — the login page should load.

---

## Step 8 — Production launch checklist

Run these on the production server and confirm each passes:

```bash
# 1. APP_DEBUG must be false
php artisan config:show app | grep debug
# Expected: debug => false

# 2. All caches are built
ls -la bootstrap/cache/
# Should see: config.php, routes-v7.php, events.php

# 3. Storage symlink exists
ls -la public/storage
# Should be a symlink

# 4. Database is seeded
php artisan tinker --execute="echo Category::count() . ' categories';"
# Should show: 5 categories

# 5. Test the live API
curl https://your-domain.com/api/v1/categories
# Should return JSON with 5 categories

# 6. Test admin login
# Open https://your-domain.com/admin/login
# Login: admin@easygo.com / admin123456
```

---

## Step 9 — Recommended: Set up SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
sudo systemctl reload nginx
```

> After SSL is set up, update `APP_URL` in `.env` to `https://your-domain.com` and run `php artisan config:cache`.

---

## Phase 6 complete ✓

Your Easy Go Shopping Laravel API is now:
- Fully deployed with CI/CD via GitHub Actions
- Every push to `main` runs tests then deploys automatically
- Admin panel accessible at `https://your-domain.com/admin`
- REST API accessible at `https://your-domain.com/api/v1`

---

## API base URLs for frontend/mobile teams

```
Production:  https://your-domain.com/api/v1
Development: http://localhost:8000/api/v1

Authentication header (all protected routes):
Authorization: Bearer <token-from-login>
Content-Type: application/json
Accept: application/json
```
