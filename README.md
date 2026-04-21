# 🏥 Hospital Appointment Booking System

> A web-based hospital appointment scheduling platform built with PHP, MySQL, HTML, CSS, and JavaScript.
> Developed as a Final Year Project — Crawford University, Department of Computer and Mathematical Sciences.
>
> **Student:** SEDI DIVINE NURUDEEN | **Matric:** 220502078

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Architecture](#system-architecture)
- [Folder Structure](#folder-structure)
- [Database Schema](#database-schema)
- [User Roles](#user-roles)
- [Pages & Routes](#pages--routes)
- [Setup & Installation](#setup--installation)
- [Environment Configuration](#environment-configuration)
- [Build Instructions for AI](#build-instructions-for-ai)
- [UI/UX Guidelines](#uiux-guidelines)
- [Security Requirements](#security-requirements)
- [Testing Checklist](#testing-checklist)

---

## Project Overview

The Hospital Appointment Booking System is a full-stack web application that allows patients to register online, browse available doctors by department or specialisation, and book medical appointments. Hospital administrators can manage doctors, approve or decline bookings, and view appointment reports. Doctors can view their own schedules and mark availability.

The system replaces manual paper-based scheduling with a centralised, reliable digital platform.

---

## Features

### Patient
- Register and log in with email and password
- Browse doctors filtered by department/specialisation
- Book an appointment (select doctor → select date → select time slot)
- View, reschedule, or cancel upcoming appointments
- View full appointment history

### Doctor
- Log in with credentials created by admin
- View personal appointment schedule (calendar or list view)
- Mark specific dates as unavailable
- View patient details for upcoming appointments

### Administrator
- Full dashboard with stats (total patients, doctors, appointments today, pending requests)
- Add, edit, deactivate doctors and assign them departments
- View all appointments (filter by date, doctor, status)
- Approve or decline pending appointment requests
- Manage departments/specialisations
- Generate and view basic appointment reports

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x (procedural or OOP, your choice) |
| Database | MySQL 8.x |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Local Server | XAMPP (Apache + MySQL) |
| Styling | Custom CSS (no frameworks — keep it clean and original) |
| Icons | Font Awesome 6 (CDN) |
| Fonts | Google Fonts (your choice — something clean and medical) |

> ⚠️ Do NOT use Laravel, CodeIgniter, or any PHP framework. Plain PHP only.
> ⚠️ Do NOT use Bootstrap. Write custom CSS.
> ⚠️ Do NOT use jQuery. Vanilla JavaScript only.

---

## System Architecture

```
Browser (HTML/CSS/JS)
        │
        ▼
  Apache (XAMPP)
        │
        ▼
   PHP Scripts  ──────►  MySQL Database
        │
        ▼
  Session Management
  (PHP $_SESSION)
```

- All pages are `.php` files
- Authentication uses PHP sessions (`$_SESSION`)
- Database access uses `mysqli` with prepared statements
- No REST API — standard form submissions and server-side rendering

---

## Folder Structure

```
hospital-appointment/
│
├── index.php                  # Landing/home page (redirects based on login state)
├── config.php                 # DB connection (single file, included everywhere)
│
├── auth/
│   ├── login.php              # Unified login page (role detected after auth)
│   ├── register.php           # Patient self-registration
│   ├── logout.php             # Destroys session, redirects to login
│   └── forgot-password.php    # Optional: password reset (basic email token or skip)
│
├── patient/
│   ├── dashboard.php          # Patient home: upcoming appointments + quick book
│   ├── book.php               # Step 1: Choose department
│   ├── book-doctor.php        # Step 2: Choose doctor from department
│   ├── book-slot.php          # Step 3: Choose date and time slot
│   ├── book-confirm.php       # Step 4: Review and confirm booking
│   ├── appointments.php       # Full list: upcoming + history
│   ├── cancel.php             # Cancel an appointment (POST action)
│   └── profile.php            # View/edit own profile
│
├── doctor/
│   ├── dashboard.php          # Doctor home: today's appointments
│   ├── schedule.php           # Full schedule view (week/list)
│   ├── availability.php       # Mark dates unavailable
│   └── profile.php            # View profile
│
├── admin/
│   ├── dashboard.php          # Admin home: stats overview
│   ├── doctors.php            # List all doctors
│   ├── add-doctor.php         # Add new doctor (creates login credentials)
│   ├── edit-doctor.php        # Edit doctor details
│   ├── appointments.php       # All appointments with filters
│   ├── approve.php            # Approve/decline appointment (POST action)
│   ├── departments.php        # Manage departments/specialisations
│   ├── patients.php           # View all registered patients
│   └── reports.php            # Basic appointment stats/reports
│
├── includes/
│   ├── header.php             # HTML <head> + top navigation
│   ├── footer.php             # Footer HTML
│   ├── sidebar-patient.php    # Sidebar nav for patient pages
│   ├── sidebar-doctor.php     # Sidebar nav for doctor pages
│   ├── sidebar-admin.php      # Sidebar nav for admin pages
│   └── auth-guard.php         # Checks session role, redirects if unauthorised
│
├── assets/
│   ├── css/
│   │   ├── main.css           # Global styles, variables, reset
│   │   ├── auth.css           # Login/register page styles
│   │   ├── dashboard.css      # Dashboard layout styles
│   │   └── forms.css          # Form component styles
│   ├── js/
│   │   ├── main.js            # Shared JS (alerts, toggles)
│   │   ├── booking.js         # Multi-step booking form logic
│   │   └── calendar.js        # Doctor schedule calendar (optional)
│   └── img/
│       └── logo.png           # Hospital logo placeholder
│
└── sql/
    └── hospital_db.sql        # Full database dump (run this to set up DB)
```

---

## Database Schema

### Table: `users`
Stores all users (patients, doctors, admins) in one table, differentiated by `role`.

```sql
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(150) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,         -- bcrypt hashed
  phone       VARCHAR(20),
  role        ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_active   TINYINT(1) DEFAULT 1
);
```

### Table: `departments`
Medical departments or specialisations available in the hospital.

```sql
CREATE TABLE departments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description TEXT
);
```

Seed with at least: General Medicine, Cardiology, Paediatrics, Gynaecology, Orthopaedics, Dermatology, ENT, Ophthalmology.

### Table: `doctors`
Extended profile for users with `role = 'doctor'`.

```sql
CREATE TABLE doctors (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL UNIQUE,
  department_id   INT NOT NULL,
  specialisation  VARCHAR(150),
  bio             TEXT,
  available_days  VARCHAR(100),              -- e.g. "Mon,Tue,Wed,Thu,Fri"
  slot_duration   INT DEFAULT 30,            -- minutes per appointment slot
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(id)
);
```

### Table: `appointments`

```sql
CREATE TABLE appointments (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT NOT NULL,
  doctor_id       INT NOT NULL,              -- references doctors.id
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  reason          TEXT,
  status          ENUM('pending', 'approved', 'declined', 'completed', 'cancelled') DEFAULT 'pending',
  notes           TEXT,                      -- admin/doctor notes
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);
```

### Table: `doctor_unavailability`
Specific dates a doctor marks as unavailable.

```sql
CREATE TABLE doctor_unavailability (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id   INT NOT NULL,
  unavail_date DATE NOT NULL,
  reason      VARCHAR(255),
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);
```

### Default Admin Account (seed data)
Insert this into `users` when setting up:
```sql
INSERT INTO users (full_name, email, password, role) VALUES
('Hospital Admin', 'admin@hospital.com', '<bcrypt_of_admin123>', 'admin');
```
> Generate the bcrypt hash using PHP: `password_hash('admin123', PASSWORD_BCRYPT)`

---

## User Roles

| Role | Access Level | Login Entry |
|---|---|---|
| Patient | Self-registered, books appointments | `/auth/login.php` |
| Doctor | Created by admin only | `/auth/login.php` |
| Admin | Seeded in DB, single superuser | `/auth/login.php` |

All roles use the **same login page**. After password verification, the system checks `users.role` and redirects:
- `patient` → `/patient/dashboard.php`
- `doctor` → `/doctor/dashboard.php`
- `admin` → `/admin/dashboard.php`

---

## Pages & Routes

### Public (no login required)
| URL | File | Purpose |
|---|---|---|
| `/` | `index.php` | Landing page or redirect |
| `/auth/login.php` | `auth/login.php` | Login form |
| `/auth/register.php` | `auth/register.php` | Patient registration |

### Patient (requires `role = patient` session)
| URL | File | Purpose |
|---|---|---|
| `/patient/dashboard.php` | | Upcoming appointments + quick stats |
| `/patient/book.php` | | Step 1: Pick department |
| `/patient/book-doctor.php?dept_id=X` | | Step 2: Pick doctor |
| `/patient/book-slot.php?doctor_id=X` | | Step 3: Pick date + time |
| `/patient/book-confirm.php` | | Step 4: Confirm booking |
| `/patient/appointments.php` | | Full appointment list |
| `/patient/profile.php` | | Edit profile |

### Doctor (requires `role = doctor` session)
| URL | File | Purpose |
|---|---|---|
| `/doctor/dashboard.php` | | Today's schedule |
| `/doctor/schedule.php` | | Full week schedule |
| `/doctor/availability.php` | | Mark off days |

### Admin (requires `role = admin` session)
| URL | File | Purpose |
|---|---|---|
| `/admin/dashboard.php` | | Stats: patients, doctors, today's appointments |
| `/admin/doctors.php` | | List + manage doctors |
| `/admin/add-doctor.php` | | Add new doctor |
| `/admin/appointments.php` | | All appointments + filters |
| `/admin/departments.php` | | CRUD for departments |
| `/admin/reports.php` | | Simple stats view |

---

## Setup & Installation

### Prerequisites
- XAMPP installed (Apache + MySQL)
- PHP 8.x
- A modern browser

### Steps

1. **Clone or copy the project** into your XAMPP `htdocs` folder:
   ```
   C:/xampp/htdocs/hospital-appointment/
   ```

2. **Start XAMPP** — ensure Apache and MySQL are both running.

3. **Create the database:**
   - Open `http://localhost/phpmyadmin`
   - Create a new database named `hospital_db`
   - Import the file `sql/hospital_db.sql`

4. **Configure the database connection** in `config.php`:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'hospital_db');

   $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

   if (!$conn) {
       die("Connection failed: " . mysqli_connect_error());
   }
   ```

5. **Seed the admin account** (run once in phpMyAdmin or via a setup script):
   ```php
   <?php
   require 'config.php';
   $hash = password_hash('admin123', PASSWORD_BCRYPT);
   $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('Hospital Admin', 'admin@hospital.com', '$hash', 'admin')";
   mysqli_query($conn, $sql);
   echo "Admin created.";
   ```

6. **Visit the app:**
   ```
   http://localhost/hospital-appointment/
   ```

---

## Environment Configuration

There is no `.env` file. All config lives in `config.php` at the project root. This file must be included at the top of every PHP page that needs DB access:

```php
<?php
require_once '../config.php'; // adjust path depth as needed
```

Session must be started at the top of every protected page:
```php
<?php
session_start();
require_once '../config.php';
require_once '../includes/auth-guard.php'; // handles role check + redirect
```

---

## Build Instructions for AI

> These instructions are for Claude Code or any AI coding assistant building this project.

### Core Principles
- Every PHP file starts with `session_start()` if it involves auth
- Every protected page includes `auth-guard.php` before any output
- All DB queries use **prepared statements** with `mysqli_prepare()` — never raw string interpolation
- Passwords are hashed with `password_hash()` and verified with `password_verify()`
- All user input is sanitised with `htmlspecialchars()` before display
- Use `$_POST` for form submissions, `$_GET` for filters/pagination
- Flash messages (success/error) are passed via `$_SESSION['flash']` and displayed + cleared in `header.php`

### auth-guard.php Logic
```php
<?php
// includes/auth-guard.php
// Usage: require_once this file AFTER session_start()
// Pass $required_role = 'patient' | 'doctor' | 'admin' before including

if (!isset($_SESSION['user_id'])) {
    header("Location: /hospital-appointment/auth/login.php");
    exit();
}

if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    header("Location: /hospital-appointment/auth/login.php");
    exit();
}
```

### Login Logic (auth/login.php)
1. Show form on GET
2. On POST: fetch user by email, verify password with `password_verify()`
3. Set `$_SESSION['user_id']`, `$_SESSION['role']`, `$_SESSION['name']`
4. Redirect based on role

### Booking Flow (patient)
The booking is a **4-step flow across 4 pages**, passing data via `$_SESSION` or `$_GET`:

| Step | Page | Data Passed |
|---|---|---|
| 1 | `book.php` | User picks department → GET `dept_id` to next page |
| 2 | `book-doctor.php?dept_id=X` | User picks doctor → GET `doctor_id` to next page |
| 3 | `book-slot.php?doctor_id=X` | User picks date + available time slot |
| 4 | `book-confirm.php` | Review and submit — inserts into `appointments` table |

On Step 3, the available time slots are calculated as follows:
- Get doctor's `slot_duration` and `available_days`
- Generate time slots from 08:00 to 17:00 based on slot duration
- Exclude slots where an `approved` or `pending` appointment already exists for that doctor + date
- Exclude dates in `doctor_unavailability` for that doctor

### Admin Appointment Approval
- `admin/appointments.php` lists all appointments with filter by status
- Each row has Approve / Decline buttons (POST forms with `appointment_id` and `action`)
- `admin/approve.php` handles the POST: updates `appointments.status` accordingly

### Report Page
`admin/reports.php` should display:
- Total appointments this month
- Breakdown by status (pending, approved, declined, completed, cancelled)
- Top 3 most-booked doctors
- Total registered patients

Use `GROUP BY` SQL queries. Display results in simple HTML tables — no charts required.

---

## UI/UX Guidelines

### Colour Palette
```css
:root {
  --primary:     #1A6B8A;   /* deep medical teal */
  --primary-dark:#114E66;
  --accent:      #E8F4F8;   /* light teal background */
  --success:     #2E7D32;
  --danger:      #C62828;
  --warning:     #F57F17;
  --text-main:   #1C1C1E;
  --text-muted:  #6B7280;
  --border:      #D1D5DB;
  --bg:          #F9FAFB;
  --white:       #FFFFFF;
}
```

### Typography
- Use **Google Fonts**: `DM Sans` for body, `Playfair Display` for headings
- Load via CDN in `header.php`
- Base font size: 15px, line height: 1.6

### Layout
- Sidebar + main content layout for all authenticated pages
- Sidebar: 240px fixed width, dark teal (`--primary-dark`) background
- Main content: scrollable, `--bg` background, 24px padding
- Top navbar: white, shows logged-in user name and logout button
- All forms are centered cards with white background, box-shadow, and rounded corners

### Components to Build
- **Stat cards** (admin dashboard): icon + number + label, 4 per row
- **Appointment table**: sortable columns, status badge (colour-coded pill)
- **Step indicator**: shows booking progress (Step 1 of 4)
- **Alert/flash message**: green for success, red for error — auto-dismiss after 4s
- **Confirmation modal**: before cancelling appointment or declining a booking

### Status Badge Colours
| Status | Colour |
|---|---|
| pending | orange |
| approved | green |
| declined | red |
| completed | blue |
| cancelled | grey |

---

## Security Requirements

- All DB queries must use **prepared statements** — no exceptions
- Passwords stored as **bcrypt** only
- Session IDs regenerated on login: `session_regenerate_id(true)`
- CSRF protection on all forms: generate token in session, verify on POST
  ```php
  // Generate in form:
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  // Hidden input: <input type="hidden" name="csrf_token" value="...">
  // Verify on POST:
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) { die("Invalid token"); }
  ```
- Role-based access enforced on every protected page via `auth-guard.php`
- No direct file execution without session check
- XSS: always wrap output in `htmlspecialchars()`

---

## Testing Checklist

Before submission, verify the following manually:

### Auth
- [ ] Patient can register with valid data
- [ ] Duplicate email shows error
- [ ] Login works for all three roles and redirects correctly
- [ ] Wrong password shows error
- [ ] Logout destroys session and redirects to login

### Patient Booking
- [ ] All 4 booking steps work in sequence
- [ ] Booked slots no longer appear available for the same doctor + date
- [ ] Patient can cancel a pending appointment
- [ ] Cancelled slot becomes available again

### Admin
- [ ] Admin can add a new doctor (user + doctor record created)
- [ ] Admin can approve and decline appointments
- [ ] Filters on appointment list work (by status, by date)
- [ ] Department CRUD works correctly
- [ ] Reports page loads and shows correct figures

### Doctor
- [ ] Doctor can view their schedule
- [ ] Doctor can mark a date unavailable
- [ ] Unavailable dates block patient booking for that doctor

### Security
- [ ] Accessing `/admin/dashboard.php` without login redirects to login
- [ ] Patient cannot access `/admin/` or `/doctor/` routes
- [ ] SQL injection attempt on login form is blocked by prepared statements

---

## Notes for Defence

- Be ready to explain the booking slot generation logic
- Know the difference between `pending` and `approved` appointment states
- Understand why `password_hash()` is used instead of MD5
- Be able to trace a booking from patient click → DB insert → admin approval
- Know what a foreign key constraint does in the `appointments` table

---

*Project built for academic purposes — Crawford University Final Year Project, 2025/2026 Academic Session.*
