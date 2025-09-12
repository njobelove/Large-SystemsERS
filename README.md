# HR_ERP — Local PHP/MySQL Project

## What's included
- `sql/schema.sql` — Database schema for hr_erp
- `api/` — Backend API and DB config
- `lib/` — Helper and auth libraries
- `public/` — Frontend pages (login, dashboard, employees, attendance, performance, assets, users, logout)
- `scripts/create_admin.php` — Run this (in browser or CLI) to create a default admin user (admin / Admin@123). Change password after first login.

## Quick setup (VS Code + XAMPP)
1. Open VS Code and clone / extract this project into your webserver's document root.
2. Start Apache & MySQL (XAMPP / MAMP / Laragon).
3. Import `sql/schema.sql` into MySQL (phpMyAdmin or CLI).
4. Update DB credentials in `api/config.php` if needed.
5. In your browser, run `http://localhost/hr_erp/scripts/create_admin.php` to insert an admin user (username: admin, password: Admin@123).
6. Open `http://localhost/hr_erp/public/login.php` and login using the admin credentials.
7. Explore: create employees, mark attendance, add performance reviews, manage assets and users.

## Security notes
- Change the admin password after first login.
- For production: enable HTTPS, set secure session cookie flags, implement CSRF protection, and restrict CORS.
