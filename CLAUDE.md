# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project Overview

**Easy Go Shopping** is a full-stack e-commerce platform built with Laravel 11, Vue 3, and TypeScript. It consists of:
- **REST API** (Laravel backend) with Bearer token authentication (Sanctum)
- **Admin Panel** (Vue 3 + Inertia.js) with session-based authentication
- **Database** (MySQL 8) with models, migrations, relationships, and seeders

The project is built in 6 sequential phases (see `setup-phases/` directory).

---

## Architecture

### Backend (Laravel 11)
- **API endpoints:** RESTful design with versioning (`/api/v1/*`)
- **Authentication:** Laravel Sanctum for API tokens + session-based admin auth
- **Database:** MySQL 8 with migrations, models (Eloquent ORM), and relationships
- **Services:** Business logic layers for cart, orders, products, admin operations

### Frontend (Admin Panel)
- **Vue 3 + TypeScript** with Inertia.js as the bridge to Laravel
- **Pages:** Located in `resources/js/Pages/` (one .vue component per route)
- **Styling:** Tailwind CSS 3 with utility-first approach
- **Build:** Vite 5 for dev server and production bundling

### Database Schema
Core entities:
- `users` (customers + admins with role differentiation)
- `products` (with categories, SKU, pricing, inventory)
- `categories` (hierarchical)
- `cart_items` (session-based or user-specific)
- `orders` (with order items and status tracking)
- `order_items` (line items with snapshots of product data)

See `database/migrations/` for the complete schema and `database/seeders/` for initial data.

---

## Development Workflow

### Starting the development server
```bash
npm run dev          # Starts Vite dev server (localhost:5173)
php artisan serve    # Starts Laravel server (localhost:8000)
# In a third terminal:
mysql -u root -p     # Connect to database if needed
```

Both servers must be running. The Vite server watches frontend files; Laravel serves the backend API.

### Running tests
```bash
php artisan test                          # Run all tests
php artisan test --filter=YourTestName    # Run a single test class
php artisan test tests/Feature/YourTest.php  # Run a specific file
```

Tests use PHPUnit with Laravel's testing framework. Feature tests use HTTP assertions and database transactions for isolation.

### Building for production
```bash
npm run build        # Compiles Vue + TypeScript → public/build/
php artisan migrate  # Runs pending migrations
```

### Database operations
```bash
php artisan migrate              # Run all pending migrations
php artisan migrate:rollback     # Undo last batch
php artisan db:seed              # Run seeders
php artisan tinker               # Interactive shell for testing
```

### Other common commands
```bash
npm run lint         # Run TypeScript type checker
composer test        # Alias for php artisan test
php artisan routes   # List all API routes
```

---

## How to Use the Phase Files

The project is built in 6 sequential phases. Each phase file in `setup-phases/` contains step-by-step instructions:

1. **Phase 1** (`phase-1-project-setup.md`): Laravel installation, Vite config, Sanctum, Inertia, GitHub setup, CI
2. **Phase 2** (`phase-2-database.md`): Migrations, models, relationships, seeders
3. **Phase 3** (`phase-3-api.md`): REST API endpoints (auth, products, cart, orders, admin)
4. **Phase 4** (`phase-4-admin-panel.md`): Vue 3 admin UI (dashboard, products, orders)
5. **Phase 5** (`phase-5-testing-security.md`): PHPUnit tests, factories, rate limiting, security
6. **Phase 6** (`phase-6-deployment.md`): GitHub Actions CI/CD, server setup, Nginx, SSL

**When working on a phase:**
- Read the phase file completely before starting
- Execute steps in order
- Run the verification command after each step before proceeding
- Complete all steps before moving to the next phase

---

## Default Admin Credentials (Development)

```
URL:      http://localhost:8000/admin
Email:    admin@easygo.com
Password: admin123456
```

⚠️ Change immediately after first production login.

---

## API Base URL

```
Development:  http://localhost:8000/api/v1
Production:   https://your-domain.com/api/v1
```

---

## Key Files & Directories

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/` | API endpoint controllers |
| `app/Models/` | Eloquent models and relationships |
| `database/migrations/` | Database schema changes |
| `database/seeders/` | Initial/test data |
| `resources/js/Pages/` | Vue components (one per route) |
| `resources/js/Components/` | Reusable Vue components |
| `routes/api.php` | API route definitions |
| `tests/Feature/` | Feature/integration tests |
| `.env` | Environment variables (create from `.env.example`) |
| `vite.config.ts` | Vite build configuration |
| `tsconfig.json` | TypeScript configuration |

---

## Environment Setup

Before starting Phase 1, verify:
- PHP 8.3+
- Composer 2.x
- Node.js v20+
- npm 10+
- MySQL 8.0+

The `.env` file is gitignored; create it from `.env.example` and configure database credentials.

---

## CI/CD

GitHub Actions workflow (`.github/workflows/ci.yml`) runs on every push:
- Installs dependencies
- Runs TypeScript type checking
- Runs PHPUnit tests against a test database
- Builds assets

Must pass before merging to `main`.

---

## Notes for Future Work

- The admin panel only runs when both Vite dev server and Laravel server are active
- Inertia automatically syncs props from Laravel to Vue components
- Sanctum requires `SANCTUM_STATEFUL_DOMAINS` in `.env` for cookie-based admin auth
- Database transactions in tests ensure test isolation
- API responses follow RESTful conventions with appropriate HTTP status codes
