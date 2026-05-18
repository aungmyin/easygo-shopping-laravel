# Phase 1 — Project Setup, Laravel Installation & GitHub

> **Claude Code instruction:** Read this file and execute every step in order. Do not skip any step. Run the verification command at the end of each step before proceeding.

---

## Prerequisites — verify before starting

```bash
php -v          # Must show PHP 8.3+
composer -V     # Must show Composer 2.x
node -v         # Must show v20+
npm -v          # Must show 10+
mysql --version # Must show 8.0+
git --version   # Any recent version
```

---

## Step 1 — Create Laravel project

```bash
cd ~/projects
composer create-project laravel/laravel easy-go-shopping
cd easy-go-shopping
```

> ✅ **Verify:** `ls -la` — you should see `artisan`, `composer.json`, `app/`, `routes/`

---

## Step 2 — Install required PHP packages

```bash
composer require laravel/sanctum
composer require inertiajs/inertia-laravel
composer require laravel/tinker
```

> ✅ **Verify:** `composer show | grep -E 'sanctum|inertia'`

---

## Step 3 — Install Node packages

```bash
npm install
npm install vue@3 @inertiajs/vue3 @vitejs/plugin-vue
npm install -D typescript vue-tsc @types/node @vue/tsconfig
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

> ✅ **Verify:** `cat package.json | grep -E 'vue|typescript|tailwind'`

---

## Step 4 — Create the .env file

```bash
cp .env.example .env
php artisan key:generate
```

Then open `.env` and set **exactly** these values:

```env
APP_NAME="Easy Go Shopping"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_go_shopping
DB_USERNAME=root
DB_PASSWORD=your_password_here

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000
SESSION_DRIVER=cookie
FILESYSTEM_DISK=public
```

> ✅ **Verify:** `php artisan config:show app | grep name` — must show `Easy Go Shopping`

---

## Step 5 — Create MySQL database

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS easy_go_shopping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> ✅ **Verify:** `mysql -u root -p -e "SHOW DATABASES;" | grep easy_go_shopping`

---

## Step 6 — Create `vite.config.ts`

**File:** `vite.config.ts` (project root — replace existing)

```ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.ts', 'resources/css/app.css'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
  ],
  resolve: {
    alias: { '@': path.resolve(__dirname, 'resources/js') },
  },
})
```

---

## Step 7 — Create `tsconfig.json`

**File:** `tsconfig.json` (project root)

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "strict": true,
    "jsx": "preserve",
    "lib": ["ES2020", "DOM"],
    "baseUrl": ".",
    "paths": { "@/*": ["resources/js/*"] }
  },
  "include": ["resources/js/**/*", "vite.config.ts"],
  "exclude": ["node_modules", "public"]
}
```

> ✅ **Verify:** `npx vue-tsc --noEmit` — should complete with no errors

---

## Step 8 — Create `resources/js/app.ts`

**File:** `resources/js/app.ts`

```ts
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import '../css/app.css'

createInertiaApp({
  title: (title) => `${title} — Easy Go Shopping Admin`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

---

## Step 9 — Create `resources/css/app.css`

**File:** `resources/css/app.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

---

## Step 10 — Configure Tailwind

**File:** `tailwind.config.js` (replace existing)

```js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/js/**/*.{vue,ts,tsx}',
    './resources/views/**/*.blade.php',
  ],
  theme: { extend: {} },
  plugins: [],
}
```

---

## Step 11 — Create Inertia root Blade template

**File:** `resources/views/app.blade.php` (create new)

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="bg-gray-50 antialiased">
    @inertia
</body>
</html>
```

---

## Step 12 — Publish Sanctum config & add middleware

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**File:** `app/Http/Kernel.php` — in `$middlewareGroups['api']`, add as the **first item**:

```php
\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
```

---

## Step 13 — Publish and register Inertia middleware

```bash
php artisan inertia:middleware
```

**File:** `app/Http/Kernel.php` — in `$middlewareGroups['web']`, add:

```php
\App\Http\Middleware\HandleInertiaRequests::class,
```

---

## Step 14 — Verify the build works

```bash
npm run build
```

> ✅ **Verify:** `public/build/manifest.json` is created. No TypeScript errors in output.

---

## Step 15 — Initialise GitHub repository

```bash
git init
git add .
git commit -m "feat: initial Laravel 11 + Inertia + Sanctum setup"
git branch -M main
# Create the repo on github.com first, then:
git remote add origin https://github.com/YOUR_USERNAME/easy-go-shopping.git
git push -u origin main
```

> ⚠️ **Note:** Replace `YOUR_USERNAME` with your actual GitHub username.

---

## Step 16 — Create develop branch

```bash
git checkout -b develop
git push -u origin develop
```

---

## Step 17 — Create GitHub Actions CI workflow

**File:** `.github/workflows/ci.yml` (create directories and file)

```yaml
name: CI

on:
  push:
    branches: ['*']
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: easy_go_shopping_test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql, mbstring

      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - run: composer install --no-interaction --prefer-dist
      - run: npm ci
      - run: npm run build
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: |
          sed -i 's/DB_DATABASE=.*/DB_DATABASE=easy_go_shopping_test/' .env
          sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
      - run: php artisan migrate --force
      - run: php artisan test
```

```bash
git add .github/
git commit -m "ci: add GitHub Actions CI workflow"
git push
```

> ✅ **Verify:** Go to `github.com/YOUR_USERNAME/easy-go-shopping/actions` — CI should be green.

---

## Phase 1 complete ✓

Proceed to `phase-2-database.md`
