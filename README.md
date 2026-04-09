# Incident Visitor System

SQLite is the default local database for this project.

## Local setup

```bash
php artisan migrate:fresh --seed
php artisan serve
```

## Seeded demo data

The default seeder creates:

- 1 subdivision
- 1 house in that subdivision
- 1 resident linked to that house
- 5 user accounts: admin, security, staff, investigator, resident
- 1 incident
- 2 visitors: one currently checked in and one checked out

## Demo accounts

All seeded accounts use the password:

```text
password
```
