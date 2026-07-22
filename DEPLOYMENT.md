# Deployment Guide

SLA Tracker is raw PHP with no build step and no Composer dependencies, so it runs anywhere that offers PHP 8.2+ and MySQL/MariaDB. Pick the section that matches your hosting.

All paths assume every step in the main [README](README.md#installation) install checklist (upload files, create database, import `database.sql`, copy `config/config.sample.php` to `config/config.php` and fill in credentials) — this guide fills in the hosting-specific parts around that checklist.

---

## Contents

- [Shared hosting (cPanel / Plesk / DirectAdmin)](#shared-hosting-cpanel--plesk--directadmin)
- [VPS — Ubuntu/Debian + Apache](#vps--ubuntudebian--apache)
- [VPS — Ubuntu/Debian + Nginx + PHP-FPM](#vps--ubuntudebian--nginx--php-fpm)
- [VPS control panels (aaPanel, CyberPanel, CloudPanel, Webmin)](#vps-control-panels-aapanel-cyberpanel-cloudpanel-webmin)
- [Post-deployment checklist (all hosting types)](#post-deployment-checklist-all-hosting-types)

---

## Shared hosting (cPanel / Plesk / DirectAdmin)

These panels all follow the same basic flow, just under different menu names.

1. **Upload files.** Use the panel's File Manager (or FTP/SFTP) to upload the contents of this project into `public_html/` (site root) or `public_html/sla-tracker/` (subfolder). Make sure `index.php` ends up directly inside whichever folder you point the domain at — not nested one level too deep.
2. **Create a database.**
   - cPanel: **MySQL® Databases** → create a database, create a user, add the user to the database with **All Privileges**.
   - Plesk: **Databases** → **Add Database** → it creates a matching user for you.
   - DirectAdmin: **MySQL Management** → **Create new Database**.
3. **Import the schema.** Open **phpMyAdmin** (available in all three panels), select your new database, go to **Import**, choose `database.sql`, and run it. Confirm the tables `users`, `sla_policies`, `records`, `activity_logs`, `settings`, `login_attempts` all appear.
4. **Set the PHP version.** cPanel: **MultiPHP Manager**. Plesk: domain → **PHP Settings**. DirectAdmin: **PHP Selector**. Pick 8.2 or newer, and confirm the `pdo_mysql` extension is enabled (it usually is by default).
5. **Configure credentials.** Copy `config/config.sample.php` to `config/config.php` via File Manager and fill in the database host/name/user/password from step 2. On most shared hosts `DB_HOST` stays `localhost`.
6. **Set permissions.** Most shared hosts already run PHP as your account's own user, so default upload permissions (755/644) are fine. The one thing to double check is that `storage/logs/` is writable — if error logging fails silently, `chmod 755 storage/logs` via File Manager.
7. **SSL.** All three panels offer free AutoSSL / Let's Encrypt under **SSL/TLS** — enable it and force HTTPS redirects if the panel offers that toggle.
8. **Verify `.htaccess` is respected.** Shared hosting is almost always Apache (or LiteSpeed with Apache compatibility), so `.htaccess` works out of the box. Confirm by opening `https://yourdomain.com/database.sql` in a browser — it should return a 403/404, not download the file. If it does download, ask your host whether `AllowOverride All` is enabled for your account (rare to need this on shared hosting, but some restrictive hosts limit it).

---

## VPS — Ubuntu/Debian + Apache

Starting from a fresh Ubuntu 22.04+/Debian 12+ server with root/sudo access.

```bash
# 1. Install Apache, PHP 8.2+, and MySQL
sudo apt update
sudo apt install -y apache2 mysql-server \
  php8.2 php8.2-mysql php8.2-mbstring php8.2-xml libapache2-mod-php8.2

# 2. Enable the modules .htaccess relies on
sudo a2enmod rewrite headers
sudo systemctl restart apache2

# 3. Secure MySQL and create the database
sudo mysql_secure_installation
sudo mysql -u root -p
```
```sql
CREATE DATABASE sla_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sla_tracker_user'@'localhost' IDENTIFIED BY 'a-strong-password-here';
GRANT SELECT, INSERT, UPDATE, DELETE ON sla_tracker.* TO 'sla_tracker_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
```bash
# 4. Import the schema
mysql -u sla_tracker_user -p sla_tracker < database.sql

# 5. Upload the app (scp/rsync/git clone) into the web root
sudo mkdir -p /var/www/sla-tracker
# ... copy files here, e.g.: sudo cp -r ./* /var/www/sla-tracker/
sudo chown -R www-data:www-data /var/www/sla-tracker
sudo find /var/www/sla-tracker -type d -exec chmod 755 {} \;
sudo find /var/www/sla-tracker -type f -exec chmod 644 {} \;

# 6. Configure credentials
sudo cp /var/www/sla-tracker/config/config.sample.php /var/www/sla-tracker/config/config.php
sudo nano /var/www/sla-tracker/config/config.php   # fill in DB_NAME/DB_USER/DB_PASS from step 3
```

Create the Apache vhost:

```apache
# /etc/apache2/sites-available/sla-tracker.conf
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/sla-tracker

    <Directory /var/www/sla-tracker>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sla-tracker-error.log
    CustomLog ${APACHE_LOG_DIR}/sla-tracker-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite sla-tracker.conf
sudo a2dissite 000-default.conf   # optional, if this is the only site
sudo systemctl reload apache2

# 7. HTTPS via Let's Encrypt
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

`AllowOverride All` is what makes `.htaccess` actually take effect — without it, the `.sql`/`.md`/config-folder protections silently do nothing, so don't skip it.

---

## VPS — Ubuntu/Debian + Nginx + PHP-FPM

Nginx doesn't read `.htaccess` at all, so its equivalent rules are baked into the server block below.

```bash
sudo apt update
sudo apt install -y nginx mysql-server \
  php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml

sudo mysql_secure_installation
sudo mysql -u root -p
```
```sql
CREATE DATABASE sla_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sla_tracker_user'@'localhost' IDENTIFIED BY 'a-strong-password-here';
GRANT SELECT, INSERT, UPDATE, DELETE ON sla_tracker.* TO 'sla_tracker_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
```bash
mysql -u sla_tracker_user -p sla_tracker < database.sql

sudo mkdir -p /var/www/sla-tracker
# ... copy files into /var/www/sla-tracker ...
sudo chown -R www-data:www-data /var/www/sla-tracker
sudo find /var/www/sla-tracker -type d -exec chmod 755 {} \;
sudo find /var/www/sla-tracker -type f -exec chmod 644 {} \;
sudo cp /var/www/sla-tracker/config/config.sample.php /var/www/sla-tracker/config/config.php
sudo nano /var/www/sla-tracker/config/config.php
```

Server block:

```nginx
# /etc/nginx/sites-available/sla-tracker
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/sla-tracker;
    index index.php;

    # Block direct access to sensitive files and folders
    # (this is Nginx's equivalent of this project's .htaccess rules)
    location ~ \.(sql|md|log|env|ini)$ { deny all; }
    location ~ ^/(config|classes|includes|storage)/ { deny all; }
    location ~ /\. { deny all; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/sla-tracker /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# HTTPS
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

After deploying, verify the block rules work: `curl -I https://yourdomain.com/database.sql` should return `403`, not `200`.

---

## VPS control panels (aaPanel, CyberPanel, CloudPanel, Webmin)

These panels wrap the same Apache/Nginx/PHP-FPM stack above behind a web UI — the underlying steps (create database → import SQL → set PHP version + extensions → set credentials → verify `.htaccess`/Nginx rules → enable SSL) are identical, just triggered through the panel's menus instead of the command line. Two things to check regardless of panel:

1. **Which web server is actually running** (Nginx vs. Apache vs. OpenLiteSpeed) — this determines whether `.htaccess` is honored automatically (Apache/OpenLiteSpeed: yes: Nginx: no, use the Nginx block above).
2. **Which system user PHP-FPM/the web server runs as** (often `www-data`, `www`, or `nginx`) — file ownership needs to match that user, especially for `storage/logs/` to remain writable.

---

## Post-deployment checklist (all hosting types)

- [ ] `https://yourdomain.com/database.sql` returns 403/404, does not download.
- [ ] `https://yourdomain.com/README.md` returns 403/404.
- [ ] `https://yourdomain.com/config/config.php` returns a blank page (PHP executed, produced no output) — never raw PHP source.
- [ ] Logged in as `admin` / `Admin@12345`, then immediately changed the password and created a personal admin account.
- [ ] Disabled or deleted the seeded demo accounts (`sarah.khan`, `ahmed.ali`, `priya.sharma`) if not needed.
- [ ] HTTPS is enabled and enforced.
- [ ] Database user has only `SELECT, INSERT, UPDATE, DELETE` on its own database — not root/admin privileges.
- [ ] `storage/logs/` is writable by the web server process.
- [ ] A backup schedule (database dump + file backup) is in place.
