# Easy Go Shopping — Laravel API Build Specification

> **For Claude Code:** Read one phase file at a time and execute every step in order. Complete each phase fully before moving to the next.

---

## How to use these files

1. Open Claude Code in your terminal inside the project folder
2. Paste the contents of one phase file and say:
   **"Read this specification and execute every step in order."**
3. Wait for Claude Code to complete all steps and run verifications
4. Move to the next phase

---

## Phases

| File | Phase | What gets built | Est. time |
|---|---|---|---|
| `phase-1-project-setup.md` | 1 | Laravel install, Vite, TypeScript, Sanctum, Inertia, GitHub, CI | 1–2 days |
| `phase-2-database.md` | 2 | All migrations, models, relationships, seeders | 1–2 days |
| `phase-3-api.md` | 3 | All REST API endpoints — auth, products, cart, orders, admin | 2–3 days |
| `phase-4-admin-panel.md` | 4 | Vue 3 + TypeScript admin UI — dashboard, products, orders | 3–4 days |
| `phase-5-testing-security.md` | 5 | PHPUnit tests, factories, rate limiting, security checks | 1–2 days |
| `phase-6-deployment.md` | 6 | GitHub Actions CI/CD, server setup, Nginx, SSL | 1–2 days |

**Total: approximately 4–6 weeks for a solo developer**

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3, MySQL 8 |
| API auth | Laravel Sanctum (Bearer tokens) |
| Admin auth | Sanctum session (cookie-based) |
| Admin UI | Vue 3 + TypeScript + Inertia.js |
| CSS | Tailwind CSS 3 |
| Bundler | Vite 5 |
| Tests | PHPUnit (Laravel feature tests) |
| CI/CD | GitHub Actions |

---

## Default admin credentials (development)

```
URL:      http://localhost:8000/admin
Email:    admin@easygo.com
Password: admin123456
```

> ⚠️ Change the password immediately after first production login.

---

## API base URL

```
Development:  http://localhost:8000/api/v1
Production:   https://your-domain.com/api/v1
```
