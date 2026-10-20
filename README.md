# Hostel Management System

A small PHP and SQLite web application for managing hostel students, rooms, bed allocations, and allocation history. It replaces manual record keeping with a simple browser-based admin workspace. The project runs locally with XAMPP and does not need MySQL or phpMyAdmin.

## Objectives

- Keep student and guardian details in one searchable directory.
- Track hostel rooms, bed capacity, occupancy, and availability.
- Allocate, change, and vacate rooms while retaining placement history.
- Protect administrative pages and records with password hashing, sessions, CSRF checks, prepared statements, and server-side validation.

## Features

- **Accounts:** Register with a unique username and email; sign in with either; secure password hashing and logout.
- **Dashboard:** Live student, room, occupancy, and availability counts with recent registrations.
- **Students:** Add, view, search, filter, edit, and safely delete student records.
- **Rooms:** Add and edit rooms, view occupancy, enforce capacity, and prevent unsafe deletion.
- **Allocations:** Assign beds, change rooms, vacate residents, prevent duplicate active assignments, and retain history.
- **Interface:** Responsive admin navigation, forms, tables, notifications, and confirmation prompts.

> **Account permissions:** This mini project has one account type. Every registered account receives administrator access to student, room, and allocation records. Do not expose public registration on an internet-facing deployment without adding role controls or admin approval.

## Technologies

- PHP 8.1 or later
- PDO with the SQLite driver (`pdo_sqlite`)
- SQLite database file
- HTML5, CSS3, and JavaScript
- XAMPP Apache (or PHP's built-in development server)

No Python dependencies are used, so this project does not need a `requirements.txt` file.

## Project Structure

```text
hostal management/
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── config/database.php
├── database/
│   ├── .htaccess
│   ├── create_admin.php
│   ├── hostel_management.sql
│   └── migrate_legacy_mysql.php
├── includes/
│   ├── auth.php
│   ├── bootstrap.php
│   ├── footer.php
│   ├── header.php
│   ├── room_form.php
│   ├── sidebar.php
│   └── student_form.php
├── tests/sqlite_smoke_test.php
├── add_room.php
├── add_student.php
├── allocate_room.php
├── allocations.php
├── change_room.php
├── dashboard.php
├── edit_room.php
├── edit_student.php
├── index.php
├── login.php
├── logout.php
├── register.php
├── rooms.php
├── router.php
├── student_details.php
├── students.php
├── vacate_room.php
├── .gitignore
└── README.md
```

`database/hostel_management.db` is created automatically at runtime and intentionally excluded from Git. The SQL file contains the SQLite table/index/trigger definitions and idempotent sample room records; it does not seed student or account data.

## Requirements

- XAMPP for Windows with Apache and PHP 8.1+.
- PHP extension `pdo_sqlite` enabled. The separate `sqlite3` extension is not required.
- A modern web browser and Visual Studio Code.

## Setup and Run with XAMPP

1. Copy the project folder into XAMPP's `htdocs`, for example `C:\xampp\htdocs\hostel-management-system`.
2. In the XAMPP Control Panel, start **Apache**. MySQL can remain stopped.
3. Open `http://localhost/hostel-management-system/` in your browser.
4. The first request creates `database/hostel_management.db` and initializes its tables, indexes, and capacity constraints. Existing database records are not dropped or overwritten during startup.

### Enable SQLite in PHP

1. Find the active PHP configuration using `C:\xampp\php\php.exe --ini`. Apache's configuration is commonly `C:\xampp\php\php.ini`.
2. In `php.ini`, enable `extension=pdo_sqlite` (remove a leading `;` if present).
3. Save the file and restart Apache.
4. Confirm the XAMPP CLI PHP lists `pdo_sqlite` with `C:\xampp\php\php.exe -m`. Apache and CLI may use different PHP configurations.

### Run with PHP's Built-in Server

From the project directory, run:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t . router.php
```

Then visit `http://127.0.0.1:8000/`. The router blocks direct HTTP access to SQLite database files. The included `database/.htaccess` protects them under Apache.

## Register and Log In

1. Open the application and choose **Create an account** from the login page.
2. Enter a unique username, email, and password of at least 12 characters; confirm the password.
3. After registration, sign in using either the username or email and that password.

Passwords are saved using PHP's `password_hash()` and checked with `password_verify()`. Username and email uniqueness is case-insensitive. There is no default account or hard-coded password. The optional `database/create_admin.php` script can create an account from the command line when needed.

## Database and Backups

The SQLite file lives at `database/hostel_management.db`. The application enables foreign-key enforcement for every connection. Allocation transactions and schema constraints protect active beds and room capacity.

Back up the database file to a private location regularly. For a consistent backup while the app is running, use SQLite's online backup mechanism or briefly stop Apache before copying. The database may contain personal student information: do not commit it, email it, or upload it to a public repository.

The optional `database/migrate_legacy_mysql.php` is only for copying records from an existing, separate legacy MySQL database. The application itself does not use MySQL; the optional importer requires `pdo_mysql` only when explicitly run.

## Testing

Run the built-in SQLite smoke test from PowerShell in the project folder:

```powershell
C:\xampp\php\php.exe tests/sqlite_smoke_test.php
```

It uses an in-memory database and checks schema initialization, password hashing and uniqueness, student/room writes, duplicate allocations, room capacity, room changes, vacating/history, dashboard queries, and protected deletes without altering the persistent database.

## Troubleshooting

- **SQLite support unavailable:** Enable `pdo_sqlite` in the PHP configuration used by Apache and restart Apache.
- **Cannot open the database file:** Ensure the account running PHP can write to the project's `database` folder.
- **Database locked:** Close other tools using the file and avoid running the live database from a network or cloud-synced directory.
- **Unexpected server error:** Check the Apache/PHP error log. The app logs technical details rather than exposing paths or credentials in browser messages.

## Future Enhancements

- Add user roles and an approval process for new administrator accounts.
- Add printable student and allocation reports.
- Add configurable hostel terms, fee tracking, and maintenance requests.
- Add automated encrypted backups and database restore tooling.
- Add broader browser-driven regression tests.
