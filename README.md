# Incident Monitoring System

A Laravel-based subdivision incident and visitor management system.

## Prerequisites

- PHP 8.2+
- [Composer](https://getcomposer.org/)
- Node.js 18+ and npm

## Quick setup

```bash
git clone <repo-url>
cd incident-monitoring
composer run setup
```

This single command installs all dependencies, copies `.env.example` to `.env`, generates an app key, creates the SQLite database, runs migrations with demo seed data, links storage, and builds frontend assets.

## Running in development

```bash
composer run dev
```

Starts the Laravel server, queue worker, log viewer, and Vite dev server concurrently.

## Reset the database

```bash
php artisan migrate:fresh --seed
```

## Demo accounts

All accounts use the password: `password`

| Email | Role | Notes |
|---|---|---|
| `admin@example.com` | Admin | Full access, no subdivision |
| `staff@example.com` | Staff | Tina Lopez — Dona Maria Dizon |
| `staff2@example.com` | Staff | Erin Ramos — Dona Maria Dizon |
| `security@example.com` | Security | Sam Navarro — Dona Maria Dizon |
| `security2@example.com` | Security | Leo Cortez — Dona Maria Dizon |
| `resident.portal@example.com` | Resident | Rina Dela Cruz — House 1 |

## Seeded demo data

- 1 subdivision (Dona Maria Dizon, Calasiao Pangasinan)
- 2 houses in the subdivision
- 9 residents across both houses
- 6 user accounts (see table above)
- 4 visitor requests (pending, approved ×2, declined)
- 2 visitors (one currently inside, one checked out)
- 4 incidents (2 open/investigating, 2 resolved with photos)

## Running tests

```bash
composer run test
```
