# Sport Club Management System

A web application for managing a sport club — members, payments, attendance, evaluations, belt progression, and schedules.

## Tech Stack

- **Backend:** PHP 8.3 (OOP)
- **Database:** MySQL
- **Frontend:** HTML, CSS (existing design)
- **Libraries:** phpdotenv, PhpSpreadsheet

## Project Structure

```
sport-club/
├── config/
│   └── database.php       # DB connection using .env variables
├── includes/              # All classes (autoloaded by Composer)
│   ├── Auth.php           # Login, logout, session, protection
│   ├── Adherent.php       # Member CRUD + ID generation
│   ├── Payment.php        # Payment CRUD + monthly check
│   ├── Attendance.php     # Attendance + monthly summary (WIP)
│   ├── Evaluation.php     # Evaluations (WIP)
│   ├── Schedule.php       # Schedules (WIP)
│   └── Plan.php           # Membership plans (WIP)
├── admin/                 # Admin pages (WIP)
│   └── layout/            # Shared header/footer
├── actions/               # Form handlers POST only (WIP)
├── public/                # Public landing page (WIP)
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
├── .env                   # Credentials — never commit this
├── .env.example           # Template for .env
├── .gitignore
└── composer.json
```

## Setup

1. Clone the repository
2. Copy `.env.example` to `.env` and fill in your credentials
3. Install dependencies:
   ```bash
   composer install
   ```
4. Import the database:
   ```bash
   mysql -u root sport_club < sport_club.sql
   ```
5. Link to Apache:
   ```bash
   sudo ln -s /path/to/sport-club /var/www/html/sport-club
   ```
6. Open `http://localhost/sport-club`

## Architecture

Each admin page follows this pattern:

```php
<?php
require '../vendor/autoload.php';
Auth::check();                        // redirect to login if not logged in
$adherent = new Adherent($conn);      // instantiate the class you need
$members  = $adherent->getAll();      // get data
?>
<?php require 'layout/header.php'; ?>
<!-- HTML here -->
<?php require 'layout/footer.php'; ?>
```

## Progress

| Feature | Class | Admin Page | Actions |
|---------|-------|------------|---------|
| Auth | ✅ | - | ✅ |
| Members | ✅ | 🔄 | 🔄 |
| Payments | ✅ | 🔄 | 🔄 |
| Attendance | 🔄 | 🔄 | 🔄 |
| Evaluations | 🔄 | 🔄 | 🔄 |
| Schedules | 🔄 | 🔄 | 🔄 |
| Plans | 🔄 | 🔄 | 🔄 |

✅ Done — 🔄 In progress
