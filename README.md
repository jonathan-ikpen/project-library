# Departmental Project Library System (DPLS)

A PHP 8.x, MySQL, PDO-based centralized digital repository. The codebase is organized as one continuous application allowing students to securely submit final academic projects, supervisors to review them, and the public to search and access approved materials.

## Stack

- PHP 8.x
- MySQL
- PDO
- HTML5, CSS3, Vanilla JavaScript
- Secure Local Storage Gateway

## Setup on XAMPP / Herd

1. Copy or map this folder to `htdocs/DPLS` (for XAMPP) or link it via Laravel Herd.
2. Create/import the database:
   - Import `database/schema.sql` (this includes table definitions and seed data).
3. Check database credentials in `config/connect.php`.
4. Visit `http://localhost/DPLS/index.php` (or your linked `.test` domain like `http://projectlibrary.test`).

## Seed Accounts

All seed passwords are `password`.

- Admin: `admin@dpls.test`
- Supervisor: `supervisor@dpls.test`
- Student: `student@dpls.test`

## Main Folders

- `config` - database and application bootstrap
- `components` - shared header and footer UI snippets
- `helpers` - escaping, redirecting, and authentication helpers
- `middleware` - session validation and role-based access checks
- `assets` - CSS styling adhering to strict Notion-inspired design rules
- `admin` - administrator workflows (moderation, users, categories)
- `student` - student workflows (dashboards, secure file uploads)
- `supervisor` - supervisor workflows (assigned project reviews)
- `auth` - login, registration, and logout handling
- `database` - SQL schema and automated seed data
- `uploads` - `.htaccess` protected secure storage for project files
