# Server Panel

Laravel 12 self-hosted server management panel for the same Linux server it manages.

## What Is Included

- Filament dark-mode admin panel at `/admin`
- Enterprise dashboard with command-center health cards, throughput chart, service health, recent tasks, and module lifecycle widgets
- Start-here dashboard workflow for creating websites, uploading files, connecting Git, and checking task logs
- Horizon `system` queue for privileged/long-running tasks
- Reverb broadcasting plus SSE task console streaming
- Whitelisted `sudo -n` process execution through `ServerAction`
- Nginx virtual host deployment through a root-owned helper script
- MariaDB/MySQL database and user provisioning
- CyberPanel-style module catalog for websites, DNS, databases, FTP, mail, webmail, file manager, PHP, firewall, backups/S3, Docker, monitoring, bandwidth, server tuning, plugins, and web-terminal foundations
- Enterprise navigation groups for Operations, Web Hosting, Data & DNS, Messaging, Runtime, Backups, and Security
- Deployment examples for sudoers, Supervisor, Nginx Reverb proxying, and backup helper scripts

## Local Setup

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Configure `.env` with the application database, Redis, and the privileged server database connection:

```env
DB_CONNECTION=mysql
DB_DATABASE=server_panel
DB_USERNAME=server_panel
DB_PASSWORD=...

QUEUE_CONNECTION=redis
REDIS_CLIENT=predis

SERVER_DB_DATABASE=mysql
SERVER_DB_USERNAME=root
SERVER_DB_PASSWORD=...
```

Then run:

```bash
php artisan migrate
php artisan panel:admin admin@example.com --password='change-this-password'
```

## One-Command Server Install

Fresh Ubuntu/Debian server install directly from GitHub:

```bash
(curl -fsSL https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh || wget -qO- https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh) | sudo bash -s -- --domain panel.example.com --admin-email admin@example.com
```

With Let's Encrypt SSL, make sure the domain already points to the server, then run:

```bash
(curl -fsSL https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh || wget -qO- https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh) | sudo bash -s -- --domain panel.example.com --admin-email admin@example.com --ssl --email admin@example.com
```

If you prefer the CyberPanel-style process substitution form:

```bash
sudo bash -c 'bash <(curl -fsSL https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh || wget -qO- https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh) --domain panel.example.com --admin-email admin@example.com'
```

Update an existing install:

```bash
(curl -fsSL https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/preUpgrade.sh || wget -qO- https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/preUpgrade.sh) | sudo bash
```

The installer will:

- install Nginx, MariaDB, Redis, Supervisor, PHP-FPM, Composer dependencies, and frontend assets;
- create the application database/user and a database bridge user for provisioning site databases;
- write `.env`, run migrations, create the Filament admin user, and cache Laravel config;
- sync the hosting module/service catalog with `php artisan panel:sync-catalog`;
- install `/usr/local/sbin/panel-nginx-site`, `/usr/local/sbin/panel-backup-sites`, `/usr/local/sbin/panel-system`, and `/etc/sudoers.d/server-panel`;
- configure Nginx for the panel and Reverb WebSockets;
- configure Supervisor for Horizon and Reverb.

At the end, it prints the panel URL, admin password, and generated database passwords. Save that output.

## Server Install Notes

Install privileged helpers:

```bash
sudo install -o root -g root -m 0750 deploy/bin/panel-nginx-site /usr/local/sbin/panel-nginx-site
sudo install -o root -g root -m 0750 deploy/bin/panel-backup-sites /usr/local/sbin/panel-backup-sites
sudo install -o root -g root -m 0750 deploy/bin/panel-system /usr/local/sbin/panel-system
sudo install -o root -g root -m 0440 deploy/sudoers/server-panel /etc/sudoers.d/server-panel
sudo visudo -cf /etc/sudoers.d/server-panel
```

Install daemons:

```bash
sudo install -o root -g root -m 0644 deploy/supervisor/server-panel-horizon.conf /etc/supervisor/conf.d/server-panel-horizon.conf
sudo install -o root -g root -m 0644 deploy/supervisor/server-panel-reverb.conf /etc/supervisor/conf.d/server-panel-reverb.conf
sudo supervisorctl reread
sudo supervisorctl update
```

## Website Workflow

Create a website from `Admin -> Server Sites -> New site`. The panel creates the Nginx virtual host and document root when you run the row action `Deploy`.

The public web root is:

```text
/var/www/vhosts/example.com/public
```

Use the row action `Upload ZIP` to upload static/PHP files into that `public` folder. The ZIP contents are synced into the public root with `rsync --delete`, so upload a ZIP containing the files that should be served directly.

For Git deployment, edit the site and fill:

- `Repository`: `https://github.com/user/repo.git` for public repositories, or `git@github.com:user/repo.git` for SSH.
- `Branch`: usually `main` or `master`.
- `Deploy key path`: optional path under `/etc/server-panel/deploy-keys/`.

Then run the row action `Deploy Git`.

For a private repository, create a deploy key on the server:

```bash
sudo install -d -o root -g www-data -m 0750 /etc/server-panel/deploy-keys
sudo install -o root -g www-data -m 0640 /path/to/private-key /etc/server-panel/deploy-keys/example.com
```

Add the matching public key to the Git host as a read-only deploy key.

## Verification

```bash
php artisan test
./vendor/bin/pint --test
npm run build
php artisan route:list --except-vendor
```

## Security Model

Controllers and Filament actions never accept raw shell commands. They create `Task` records and dispatch queued jobs. Jobs call `ServerAction`, which uses array-based commands and a `CommandWhitelist` that rejects unapproved actions or argument shapes before `sudo` is invoked.

The sudoers file intentionally grants only specific binaries and exact argument forms where possible. Nginx writes and symlink operations go through `/usr/local/sbin/panel-nginx-site`; module install, service control, firewall, and backup path operations go through `/usr/local/sbin/panel-system`. This avoids giving `www-data` generic access to shell, `tee`, package managers, or arbitrary filesystem commands.
