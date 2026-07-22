# SLA Tracker

A production-ready **SLA (Service Level Agreement) Management System** built with raw PHP 8.2+, PDO, and MySQL/MariaDB. No frameworks — just clean, object-oriented PHP suitable for any shared hosting environment (cPanel, Plesk, DirectAdmin) or a standard LAMP/LEMP stack.

Administrators define unlimited SLA policies (e.g. Critical — 2 hours, High — 4 hours). Those policies are assigned to records (tickets, cases, shipments, tasks — anything with a deadline). The system automatically calculates due dates, live countdown timers, breach status, and compliance reporting.

Works for logistics, courier companies, warehouses, customer support, banking, government, healthcare, manufacturing, IT operations, HR, or any business that needs to track deadlines against a service level agreement.

## Features

- Session-based authentication with `password_hash()` / `password_verify()`, CSRF protection on every form, prepared PDO statements everywhere, and output escaping to prevent XSS.
- Role-based access: Administrator, Manager, and User.
- Dashboard with live counts for total policies, total records, active/completed records, SLA breaches, records due today, SLA compliance %, and average completion time.
- Unlimited, fully editable SLA policies (hours, minutes, warning threshold, active/inactive toggle).
- Record management with reference numbers, priority, department, assigned user, and automatic due-date calculation based on the selected SLA policy.
- Live, auto-updating countdown/overdue timers with green / yellow / red / gray status coloring.
- Search and filtering by reference number, customer, department, assigned user, status, priority, SLA policy, and date range.
- Pagination on the records list.
- Reports dashboard with Chart.js visualizations: monthly statistics, status breakdown, and department performance, plus department and user performance tables.
- CSV export of the (filtered) records list.
- Full user management (create, edit, delete, enable/disable).
- Editable application settings (site name, company name, timezone).
- Activity log / audit trail per record, with the ability to add remarks.
- Responsive Bootstrap 5 UI with a collapsible sidebar, breadcrumbs, confirmation dialogs before every delete, and Bootstrap Icons.

## Requirements

- PHP 8.2 or higher, with the `pdo_mysql` extension enabled.
- MySQL 5.7+ or MariaDB.
- Apache with `mod_rewrite` / `mod_headers` (recommended; the app also runs fine without clean URLs).
- No Composer dependencies. Bootstrap 5, Bootstrap Icons, and Chart.js are loaded from CDN.

## Installation

Quick start below; for control-panel-by-control-panel walkthroughs (cPanel, Plesk, DirectAdmin, a bare VPS with Apache, a bare VPS with Nginx, aaPanel, etc.) see [`DEPLOYMENT.md`](DEPLOYMENT.md).

1. **Upload the files** — Upload the entire contents of this folder to your hosting account (e.g. `public_html/` or a subfolder of it).
2. **Create a database** — In cPanel/Plesk/DirectAdmin (or via the MySQL CLI), create a new empty MySQL database and a database user with full privileges on it.
3. **Import the schema** — Import `database.sql` into that database using phpMyAdmin, Adminer, or:
   ```bash
   mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database.sql
   ```
   This creates all required tables and pre-populates the system with a default administrator account, 5 sample SLA policies, 20 sample records, and matching activity logs so the dashboard has meaningful data immediately.
4. **Configure the database connection** — Copy `config/config.sample.php` to `config/config.php` (the real `config.php` is gitignored so credentials never get committed), then edit it:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```
5. **Log in** — Visit `index.php` (or your domain root) in a browser and log in with the default administrator credentials below. **Change the password immediately** via the Users page after your first login.

No other setup, build step, or Composer install is required.

## Default Login

```
Username: admin
Password: Admin@12345
```

The password is stored as a bcrypt hash in `database.sql` — it is never stored in plain text. Three additional demo users (`sarah.khan`, `ahmed.ali`, `priya.sharma`) are seeded with the same default password purely so the Reports and filter dropdowns have sample data to display; delete or disable them from the Users page once you no longer need them.

## Folder Structure

```
sla-tracker/
├── admin/                 Backend action handlers (create/update/delete/toggle, CSV export, record detail view)
├── assets/
│   ├── css/style.css      Application styling
│   └── js/app.js          Sidebar toggle, live clock, delete confirmations, live SLA countdown timers
├── classes/               Core OOP classes (Database, Auth, Csrf, SlaPolicy, RecordModel, User, Report, ActivityLog, Settings)
├── config/
│   ├── config.sample.php  Template — copy to config.php and edit with real credentials
│   └── config.php         Your real credentials (gitignored, created by you — not in version control)
├── includes/              Shared layout (header/sidebar/footer) and helper functions
├── storage/logs/          PHP error log destination (blocked from HTTP access) — not part of the public site
├── dashboard.php          Main dashboard with summary cards and recent activity
├── sla.php                SLA policy management
├── records.php            Record list, search/filter, create/edit
├── reports.php            Compliance reports and Chart.js visualizations
├── users.php              User management (Administrator only)
├── settings.php           Application settings (Administrator only)
├── login.php / logout.php Authentication
├── index.php              Entry point / router
├── 404.php                Not found page
├── database.sql           Full schema + sample data
├── .htaccess              Security headers, directory protection, custom 404
└── README.md              This file
```

Sensitive folders (`config/`, `classes/`, `includes/`, `storage/`) each contain a `.htaccess` that blocks all direct HTTP access — they are only ever loaded via PHP `include`/`require` or written to internally.

## Database Tables

| Table             | Purpose                                                             |
|-------------------|----------------------------------------------------------------------|
| `users`           | Application accounts (Administrator / Manager / User roles)         |
| `sla_policies`    | Reusable SLA definitions (duration + warning threshold)              |
| `records`         | The tracked items/tickets, each linked to one SLA policy             |
| `activity_logs`   | Audit trail of actions and remarks against each record               |
| `settings`        | Single-row table for site name, company name, and timezone           |
| `login_attempts`  | Tracks failed logins per IP for brute-force lockout                  |

## Security

Built-in protections:

- **SQL injection** — every query uses PDO prepared statements with bound parameters (`PDO::ATTR_EMULATE_PREPARES` disabled, so native server-side prepares are used); no user input is ever concatenated into SQL.
- **XSS** — all dynamic output is passed through `htmlspecialchars()` (the `e()` helper) before rendering.
- **CSRF** — every state-changing form (create, update, delete, toggle, settings, login) requires a per-session token verified with a timing-safe comparison.
- **CSV / formula injection** — exported CSV fields are sanitized so a value like `=cmd(...)` can't execute as a formula when opened in Excel/Sheets.
- **Authentication** — passwords hashed with `password_hash()` (bcrypt) and checked with `password_verify()`; login timing is equalized (a dummy hash is checked even for non-existent usernames) to resist user-enumeration via timing analysis; sessions regenerate their ID on login.
- **Brute-force protection** — after 5 failed login attempts from the same IP within 15 minutes, that IP is locked out for 15 minutes (`login_attempts` table, `classes/LoginThrottle.php`).
- **Session hardening** — `httponly` + `SameSite=Lax` cookies, custom session name, 30-minute inactivity timeout (configurable), `secure` flag auto-enabled when served over HTTPS.
- **Authorization** — every admin-only page/handler is guarded by `Auth::requireRole()`; users can't delete, disable, or demote their own account (prevents accidental admin lockout).
- **Input length limits** — server-side truncation matches database column limits, so oversized input can't trigger a database error even if a client bypasses the HTML `maxlength` attributes.
- **Error handling** — `display_errors` is off; PHP errors are logged to `storage/logs/php-error.log`, a directory blocked from direct HTTP access, instead of a default location that might be web-readable. User-facing error messages never include raw exception/SQL details.
- **Host header hardening** — the `Host` header is validated against a strict pattern before being used to build `BASE_URL`, falling back to the server-configured `SERVER_NAME` if it looks malformed.

### Hardening checklist before going live

1. **Change the default admin password immediately** (and consider renaming/disabling the `admin` account after creating your own — the dashboard shows a reminder until you do). Also disable or delete the three seeded demo accounts (`sarah.khan`, `ahmed.ali`, `priya.sharma`) if you don't need them.
2. **Use a database user with least privilege.** Create a dedicated MySQL user for this app with only `SELECT, INSERT, UPDATE, DELETE` on its own database — not a root/admin account — and use a strong, unique password in `config/config.php`.
3. **Serve the site over HTTPS.** Session cookies automatically get the `secure` flag when HTTPS is detected; without HTTPS, session cookies can be intercepted on untrusted networks. Most hosts provide a free Let's Encrypt certificate.
4. **Never commit real credentials.** `config/config.php` is already gitignored — only `config/config.sample.php` (a template with no real secrets) is tracked in version control. If you ever add credentials directly to a tracked file by mistake, rotate the database password immediately, since anything pushed to a public repo should be treated as permanently exposed.
5. **Verify the `.htaccess` protections are active** after upload by trying to open `/database.sql`, `/README.md`, and `/config/config.php` directly in a browser — each should return a 403 or 404. (These rules require Apache with `mod_authz_core`/`mod_headers`; if your host uses Nginx instead, ask your host to add equivalent `location` blocks denying access to `*.sql`, `*.md`, and the `config/`, `classes/`, `includes/`, `storage/` directories.)
6. **Keep PHP and MySQL/MariaDB patched.** This app has no Composer/npm dependencies to track, but the underlying PHP and database server versions should still receive security updates from your host.
7. **Back up `database.sql`-equivalent dumps regularly**, and restrict who has FTP/SSH/cPanel access to the server.

## Customization

- **Session timeout**: change `SESSION_TIMEOUT` in `config/config.php` (seconds).
- **Records per page**: change `RECORDS_PER_PAGE` in `config/config.php`.
- **Branding**: update Site Name, Company Name, and Timezone from the in-app Settings page (Administrator only).
- **SLA policies**: fully editable from the SLA Policies page — add as many as you need (Critical, High, Medium, Low, or any custom duration).

## Deployment Notes

- Compatible with PHP 8.2+, MySQL 5.7+/MariaDB, and Apache on shared hosting (cPanel, Plesk, DirectAdmin) as well as self-managed servers.
- If deploying into a subfolder (e.g. `https://example.com/sla-tracker/`), no additional configuration is needed — `BASE_URL` is derived automatically from the request.
- For production, ensure `display_errors` remains off (already set in `config/config.php`). PHP error logs are automatically written to `storage/logs/php-error.log`, which is blocked from direct HTTP access via `.htaccess`.

## License

This project is provided as-is for commercial or personal use. You are free to modify, extend, and deploy it for your own organization or client projects.
