# Incident Monitoring System

> A modern, secure subdivision incident and visitor management system built with Laravel 12 and Tailwind CSS, designed to streamline neighborhood security and communication.

![Status](https://img.shields.io/badge/status-active%20development-brightgreen)
![Platform](https://img.shields.io/badge/platform-web-FF2D20?logo=laravel&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/UI-Tailwind%20CSS-06B6D4?logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-77C1D2?logo=alpine.js&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4.x-FF6384?logo=chart.js&logoColor=white)
![SQLite](https://img.shields.io/badge/Database-SQLite-003B57?logo=sqlite&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-4169E1?logo=postgresql&logoColor=white)
![Laravel Breeze](https://img.shields.io/badge/Auth-Laravel%20Breeze-FF2D20?logo=laravel&logoColor=white)
![License](https://img.shields.io/badge/license-portfolio-lightgrey)

---

# 📖 Overview

Incident Monitoring System is a comprehensive platform for subdivision properties, neighborhood safety tracking, and visitor check-in logs.

Instead of manual paper logbooks, it provides a centralized platform for managing subdivision details, tracking neighborhood incidents with photo attachments, and regulating visitor access. It ensures seamless coordination between Admins, Staff, and Security Guards through role-based access control and real-time dashboard analytics.

Built with Laravel 12, the application features an elegant web interface styled with Tailwind CSS, a robust SQLite database back-end, and support for automated PDF report generation.

---

# 📥 Local Installation

You can set up and run the Incident Monitoring System locally in a few quick steps:

### ⚙️ Prerequisites
* **PHP 8.2+**
* **Composer**
* **Node.js 18+ and npm**
* **SQLite 3**

### ⚡ Automatic Setup
Clone the repository and run the setup script to configure your environment:
```bash
git clone <repo-url>
cd incident-monitoring
composer run setup
```

> [!NOTE]
> **What the setup script does:**
> 1. Installs all PHP dependencies via Composer.
> 2. Copies `.env.example` to `.env` if not already present.
> 3. Generates a secure Laravel application key.
> 4. Creates a local SQLite database (`database/database.sqlite`).
> 5. Runs database migrations and seeds database with demo subdivision data, roles, and test accounts.
> 6. Links the storage directory for media attachments.
> 7. Installs npm packages and builds frontend assets using Vite.

---

## 🏗️ Architecture

This application follows a structured **Model-View-Controller (MVC)** architectural pattern, divided into clean layers:

```text
Presentation (Frontend)
├── Blade Views (Tailwind CSS)
│   ├── auth / layouts / components
│   ├── subdivisions / houses / residents
│   ├── incidents / incident-photos
│   ├── visitors
│   └── analytics / dashboard / profile
└── Assets (Vite & Tailwind CSS compilation)

Application (Backend Logic)
├── Http Controllers
├── Middleware (role-based restrictions, force password change)
└── Notifications & Mailers

Business Domain
├── Models (Subdivision, House, Resident, Incident, Visitor...)
├── Enums (UserRole, IncidentStatus, VisitorStatus, etc.)
└── Support (Helpers & utility classes)

Data Access & Infrastructure
├── Spatie Laravel Permission (RBAC Engine)
├── Laravel Eloquent ORM
└── SQLite Database (Local) / PostgreSQL (Production) / Local Storage (File management)
```

---

## 🛠️ Tech Stack

- **PHP 8.2+**
- **Laravel 12.x** (Modern framework backend)
- **Tailwind CSS & Laravel Breeze** (Elegant, responsive styling, lightweight Alpine.js reactivity, and authentication scaffolding)
- **Vite** (Fast asset bundling and hot module replacement)
- **SQLite & PostgreSQL** (SQLite for simple local development, PostgreSQL configured for secure production/Docker environments)
- **Spatie Laravel Permission** (Role-Based Access Control)
- **Barryvdh Laravel DomPDF** (Dynamic PDF export for incidents and visitor logs)
- **Laravel Pail & Tinker** (Interactive debugging and logging)

---

## 🧪 Run locally

### Start Development Server

```powershell
composer run dev
```

> This starts the Laravel server (`php artisan serve`), queue listener (`php artisan queue:listen`), logs monitoring (`php artisan pail`), and the Vite development server concurrently.

### Reset Database with Demo Data

```powershell
php artisan migrate:fresh --seed
```

### Run Tests

```powershell
composer run test
```

---

## 🔑 Demo Accounts

To test the application, use any of the seeded accounts below. All accounts use the password: `password`

| Email | Role | Subdivision Scope / Notes |
|---|---|---|
| `admin@example.com` | Admin | Full global access (no subdivision limits) |
| `staff@example.com` | Staff | Tina Lopez — Dona Maria Dizon Subdivision |
| `staff2@example.com` | Staff | Erin Ramos — Dona Maria Dizon Subdivision |
| `security@example.com` | Security | Sam Navarro — Dona Maria Dizon Subdivision |
| `security2@example.com` | Security | Leo Cortez — Dona Maria Dizon Subdivision |

---

## 📦 Seeded Demo Data

The default seed database contains pre-configured records to instantly explore the app:
- **1 Subdivision:** Dona Maria Dizon, Calasiao Pangasinan (with subdivision profile & logo)
- **2 Houses:** Registered within the subdivision
- **9 Residents:** Registered and mapped to houses
- **5 User Accounts:** Pre-seeded for testing roles (see table above)
- **4 Visitor Requests:** 1 pending, 2 approved, 1 declined
- **2 Visitors:** 1 currently checked-in, 1 checked-out
- **4 Incidents:** 2 open/under investigation, 2 resolved with photo attachments

---

## 🚀 Features & Capabilities

### 🛡️ Access Control & Portals
- **Multi-tenant Role-Based Access (RBAC):** Admin, Staff, and Security Guard roles. Residents are records only and have no login accounts. The resident portal has been removed.
- **Dedicated Portals:** Tailored user interfaces for each role (e.g., Security Guard gate interface).
- **Password Enforcement:** Force password change on initial login for security.
- **Subdivision Scoping:** Staff and Security are scoped to their respective subdivisions.

### 📋 Incident Management
- **Incident Reporting:** File incidents with titles, descriptions, status, and priority levels.
- **Photo Attachments:** Upload and view multiple images for proof and investigation.
- **PDF Report Generation:** Print individual incidents or export a full list of incidents to PDF.
- **Incident Status Flow:** Follow incidents from Open, Investigating, to Resolved.

### 🚗 Visitor Access Control
- **Owner Permission:** Security guards contact the house owner and record permission before visitor entry.
- **Security Check-in/Check-out:** Security guards log visitors at the gate, capture ID photos, and record check-out times.
- **Notifications:** Admins receive notifications of visitor status updates.
- **Visitor Logs Export:** Export and print comprehensive visitor logbooks to PDF.

### 📊 Analytics & Administration
- **Interactive Dashboard:** Quick summaries of active incidents, checked-in visitors, and recent requests.
- **Detailed Analytics:** Trend analysis and graphs of incidents and visitor frequencies.
- **Database Soft Deletes:** Safely delete users, incidents, and visitors with restoration capabilities.
- **Custom Branding:** Change subdivision logos, page icons, and name configurations dynamically.

---

## 📅 Roadmap

- [x] Project Initialization with Laravel 12 & Laravel Breeze
- [x] Role-Based Access Control (Spatie Permissions)
- [x] SQLite & Database Migration Configuration
- [x] Setup script for one-click environment configuration
- [x] Subdivision & House Registry Management
- [x] Resident Profile Management
- [x] Incident Report Submission & Status Flow
- [x] Incident Photo Uploads & Local Storage Handler
- [x] Visitor Check-in & Check-out logs
- [x] Owner Permission Recording
- [x] Soft Deletes & Admin Restore Utilities
- [x] PDF Export engine for Incidents & Visitors
- [x] Real-time Admin visitor notifications
- [x] Live log viewer & developer tools (Pail & Tinker integration)
- [x] Multi-process concurrently running dev stack
- [x] Automated Unit and Feature Testing

---

# 💡 Inspiration & Milestone

This project was developed as a capstone/thesis system designed to solve real-world security challenges in residential subdivisions. 

It marks a significant learning and development milestone—taking theoretical software engineering concepts from the classroom and applying them to build a functional, secure, and production-ready application that replaces traditional paper logbooks with a modern digital registry.

Existing installations must run `php artisan migrate` to retire old resident logins and remove account links. Former login identities remain archived without a role or usable credentials solely to preserve reporter names on existing incident reports. They cannot be restored through user management.

Adding a house requires its owner?s surname, first name, phone, and email; middle name and extension are optional. The house and an active Owner resident are saved together. Add or assign additional household members through Residents. Editing an existing house does not create another owner.

The dashboard has independent searches for pending incidents (category, location, description) and checked-in visitors (name, resident being visited, phone, plate, house, purpose). The resident's name appears below the visitor's name as “Visiting: …” when provided. Search filters the full permitted list and remains active while scrolling; dashboard summary counts remain unchanged.


## Visitor profiles and visits

Use **Visitors** in the sidebar to search profiles by visitor number, name, or phone. **Add Visitor** saves identity, phone, and an ID photo without checking anyone in. Open a profile to edit those details, review all visits, or choose **Add Visit**. **Gate Monitoring** retains the Currently Inside and Visit History lists, search, checkout, duration, and exports.

The visitor directory uses the shared scroll list instead of pagination controls. It loads ten records at a time as needed, fits the available space, and preserves the current search when fetching more profiles.

Adding or editing a visitor profile rejects an existing full name within the subdivision, regardless of phone number or capitalization. Middle names/initials and extensions distinguish names. Existing duplicate profiles remain intact.

The profile's **View ID Photo** URL changes when a replacement is uploaded. Profile and visit photo responses use private, no-store caching headers. Historical visits retain their recorded photos. Photo access requires Security or Admin access; Security users must belong to the profile's subdivision. A profile photo request for another subdivision returns 403. Missing photo files return 404. For intermittent production errors, inspect the failing request URL and server logs to distinguish application access checks from hosting or proxy errors.

Every visit stores a snapshot of the visitor?s identity plus its own destination, purpose, optional vehicle and passenger details, entry time, exit time, and duration. Profile edits apply to future visits and leave previous visit snapshots unchanged. The saved ID photo is reused; a replacement uploaded during check-in becomes the saved photo for subsequent visits. A visitor already inside cannot start another visit until checkout.

Choose **Resident Visit** for a specific household, including household deliveries. Select the resident, call the active house owner using the registered phone number, select the approving owner, and confirm permission before entry. This is required on every Resident Visit, even when visiting the owner directly. The owner name, phone, approving time, host resident, and recording guard are saved with that visit. **Walk-in** is for deliveries without a resident destination or amenity access: record the destination and purpose, with no resident selection or owner permission.

Run `php artisan migrate` on an existing installation. Migration `2026_10_11_010000_create_visitor_profiles.php` links existing visit records to profiles without changing their check-in/out times, approval data, photos, or archived state. It groups only matching full names and nonempty phone numbers within the same subdivision. Names with different or missing phone numbers remain separate profiles. The existing `visitors` table continues to hold individual visit records; `visitor_profiles` holds the reusable identity. Profiles and ID-photo routes enforce subdivision access; staff can view profiles, while security and admins can register, edit, and check in visitors.
