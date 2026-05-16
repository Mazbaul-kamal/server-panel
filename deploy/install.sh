#!/usr/bin/env bash
set -Eeuo pipefail

INSTALL_DIR="/var/www/server-panel"
DOMAIN=""
ADMIN_EMAIL=""
LETSENCRYPT_EMAIL=""
APP_DB="server_panel"
APP_DB_USER="server_panel"
SERVER_DB_USER="server_panel_admin"
APP_DB_PASSWORD=""
SERVER_DB_PASSWORD=""
ADMIN_PASSWORD=""
MYSQL_ROOT_PASSWORD=""
PHP_FPM_SOCKET=""
ENABLE_SSL=0
SKIP_APT=0

SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

trap 'echo "Install failed at line $LINENO. Check the message above." >&2' ERR

usage() {
  cat <<'USAGE'
Usage:
  sudo bash deploy/install.sh --domain panel.example.com [options]

Options:
  --domain DOMAIN                 Required. Nginx server_name and public app host.
  --admin-email EMAIL             Admin login email. Defaults to admin@DOMAIN.
  --email EMAIL                   Let's Encrypt email. Defaults to admin email.
  --ssl                           Run certbot after Nginx is configured. DNS must already point here.
  --install-dir PATH              Default: /var/www/server-panel
  --mysql-root-password PASSWORD  Optional. Uses unix_socket root login when omitted.
  --app-db NAME                   Default: server_panel
  --app-db-user USER              Default: server_panel
  --app-db-password PASSWORD      Generated when omitted.
  --server-db-user USER           Default: server_panel_admin
  --server-db-password PASSWORD   Generated when omitted.
  --admin-password PASSWORD       Generated when omitted.
  --php-fpm-socket PATH           Default: detected from installed PHP version.
  --skip-apt                      Do not install OS packages.
  -h, --help                      Show this help.
USAGE
}

log() {
  printf '\n==> %s\n' "$1"
}

die() {
  echo "Error: $1" >&2
  exit 1
}

random_secret() {
  openssl rand -hex 24
}

sql_quote() {
  printf "%s" "$1" | sed "s/'/''/g"
}

require_identifier() {
  local value="$1"
  local label="$2"

  [[ "$value" =~ ^[A-Za-z0-9_]{1,64}$ ]] || die "$label must contain only letters, numbers, and underscores."
}

require_host() {
  local value="$1"

  [[ "$value" =~ ^[A-Za-z0-9][A-Za-z0-9.-]{0,252}$ ]] || die "Invalid --domain value."
}

mysql_root() {
  if [[ -n "$MYSQL_ROOT_PASSWORD" ]]; then
    mysql -uroot "-p${MYSQL_ROOT_PASSWORD}" "$@"
  else
    mysql --protocol=socket -uroot "$@"
  fi
}

install_composer() {
  if command -v composer >/dev/null 2>&1; then
    return
  fi

  log "Installing Composer"
  php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
}

install_packages() {
  if [[ "$SKIP_APT" -eq 1 ]]; then
    return
  fi

  log "Installing OS packages"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update
  apt-get install -y \
    ca-certificates curl unzip git rsync openssl \
    nginx mariadb-server redis-server supervisor certbot python3-certbot-nginx \
    php-cli php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-bcmath php-gd php-intl php-readline \
    nodejs npm
}

copy_application() {
  log "Copying application to ${INSTALL_DIR}"
  mkdir -p "$INSTALL_DIR"

  if [[ "$(readlink -f "$SOURCE_DIR")" != "$(readlink -f "$INSTALL_DIR" 2>/dev/null || printf "%s" "$INSTALL_DIR")" ]]; then
    rsync -a --delete \
      --exclude='.env' \
      --exclude='.git' \
      --exclude='node_modules' \
      --exclude='vendor' \
      --exclude='storage/app/*' \
      --exclude='storage/framework/*' \
      --exclude='storage/logs/*' \
      --exclude='bootstrap/cache/*.php' \
      "$SOURCE_DIR"/ "$INSTALL_DIR"/
  fi
}

configure_mysql() {
  log "Configuring MariaDB/MySQL users"
  require_identifier "$APP_DB" "Application database"
  require_identifier "$APP_DB_USER" "Application database user"
  require_identifier "$SERVER_DB_USER" "Server database bridge user"

  mysql_root -e "SELECT 1" >/dev/null

  local app_db_password_sql
  local server_db_password_sql
  app_db_password_sql="$(sql_quote "$APP_DB_PASSWORD")"
  server_db_password_sql="$(sql_quote "$SERVER_DB_PASSWORD")"

  mysql_root <<SQL
CREATE DATABASE IF NOT EXISTS \`${APP_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${APP_DB_USER}'@'localhost' IDENTIFIED BY '${app_db_password_sql}';
ALTER USER '${APP_DB_USER}'@'localhost' IDENTIFIED BY '${app_db_password_sql}';
GRANT ALL PRIVILEGES ON \`${APP_DB}\`.* TO '${APP_DB_USER}'@'localhost';

CREATE USER IF NOT EXISTS '${SERVER_DB_USER}'@'localhost' IDENTIFIED BY '${server_db_password_sql}';
ALTER USER '${SERVER_DB_USER}'@'localhost' IDENTIFIED BY '${server_db_password_sql}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES, CREATE USER ON *.* TO '${SERVER_DB_USER}'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL
}

detect_php_fpm() {
  local php_version
  local php_fpm_service
  php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
  php_fpm_service="php${php_version}-fpm"

  PHP_FPM_SOCKET="${PHP_FPM_SOCKET:-/run/php/php${php_version}-fpm.sock}"

  if systemctl list-unit-files --type=service | awk '{print $1}' | grep -qx "${php_fpm_service}.service"; then
    systemctl enable --now "$php_fpm_service"
  elif systemctl list-unit-files --type=service | awk '{print $1}' | grep -qx "php-fpm.service"; then
    systemctl enable --now php-fpm || true
  else
    echo "Warning: no PHP-FPM systemd service found. Nginx will use ${PHP_FPM_SOCKET}." >&2
  fi
}

write_env() {
  log "Writing production .env"
  local scheme="http"
  local reverb_port="80"
  local reverb_scheme="http"

  if [[ "$ENABLE_SSL" -eq 1 ]]; then
    scheme="https"
    reverb_port="443"
    reverb_scheme="https"
  fi

  if [[ -f "$INSTALL_DIR/.env" ]]; then
    cp "$INSTALL_DIR/.env" "$INSTALL_DIR/.env.backup.$(date -u +%Y%m%dT%H%M%SZ)"
  fi

  cat > "$INSTALL_DIR/.env" <<ENV
APP_NAME="Server Panel"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${scheme}://${DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${APP_DB}
DB_USERNAME=${APP_DB_USER}
DB_PASSWORD=${APP_DB_PASSWORD}

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
CACHE_STORE=file

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="${ADMIN_EMAIL}"
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"

PANEL_MANAGED_ROOT=/var/www/vhosts
PANEL_NGINX_HELPER=/usr/local/sbin/panel-nginx-site
PANEL_PHP_FPM_SOCKET=unix:${PHP_FPM_SOCKET#unix:}

SERVER_DB_DRIVER=mysql
SERVER_DB_HOST=127.0.0.1
SERVER_DB_PORT=3306
SERVER_DB_DATABASE=mysql
SERVER_DB_USERNAME=${SERVER_DB_USER}
SERVER_DB_PASSWORD=${SERVER_DB_PASSWORD}

REVERB_APP_ID=$(date +%s)
REVERB_APP_KEY=$(random_secret)
REVERB_APP_SECRET=$(random_secret)
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_HOST=${DOMAIN}
REVERB_PORT=${reverb_port}
REVERB_SCHEME=${reverb_scheme}

VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="\${REVERB_HOST}"
VITE_REVERB_PORT="\${REVERB_PORT}"
VITE_REVERB_SCHEME="\${REVERB_SCHEME}"
ENV
}

install_php_dependencies() {
  log "Installing PHP dependencies"
  cd "$INSTALL_DIR"
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
}

build_assets() {
  cd "$INSTALL_DIR"

  if [[ -f public/build/manifest.json ]]; then
    log "Frontend build already exists"
    return
  fi

  log "Building frontend assets"

  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi

  npm run build
  rm -rf node_modules
}

install_privileged_helpers() {
  log "Installing privileged helper scripts and sudoers"
  install -o root -g root -m 0750 "$INSTALL_DIR/deploy/bin/panel-nginx-site" /usr/local/sbin/panel-nginx-site
  install -o root -g root -m 0750 "$INSTALL_DIR/deploy/bin/panel-backup-sites" /usr/local/sbin/panel-backup-sites
  install -o root -g root -m 0750 "$INSTALL_DIR/deploy/bin/panel-system" /usr/local/sbin/panel-system
  install -o root -g root -m 0440 "$INSTALL_DIR/deploy/sudoers/server-panel" /etc/sudoers.d/server-panel
  visudo -cf /etc/sudoers.d/server-panel
  install -d -o root -g www-data -m 0750 /etc/server-panel /etc/server-panel/deploy-keys
  printf '%s\n' "$INSTALL_DIR" > /etc/server-panel/root
  printf '%s\n' "/var/www/vhosts" > /etc/server-panel/managed-root
  chmod 0644 /etc/server-panel/root /etc/server-panel/managed-root
  install -d -o www-data -g www-data -m 0750 /var/www/vhosts
  install -d -o root -g root -m 0750 /var/backups/server-panel
}

write_nginx_config() {
  log "Writing Nginx site"
  local php_fpm_pass="unix:${PHP_FPM_SOCKET#unix:}"

  cat > /etc/nginx/sites-available/server-panel <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root ${INSTALL_DIR}/public;
    index index.php;

    access_log /var/log/nginx/server-panel.access.log;
    error_log /var/log/nginx/server-panel.error.log warn;

    client_max_body_size 64m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass ${php_fpm_pass};
    }

    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header Scheme \$scheme;
        proxy_set_header SERVER_PORT \$server_port;
        proxy_set_header REMOTE_ADDR \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_pass http://127.0.0.1:8080;
    }

    location /apps {
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header Scheme \$scheme;
        proxy_set_header SERVER_PORT \$server_port;
        proxy_set_header REMOTE_ADDR \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_pass http://127.0.0.1:8080;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

  ln -sfn /etc/nginx/sites-available/server-panel /etc/nginx/sites-enabled/server-panel
  nginx -t
  systemctl enable --now nginx
  systemctl reload nginx
}

write_supervisor_config() {
  log "Writing Supervisor programs"
  install -d -o www-data -g adm -m 0750 /var/log/server-panel

  cat > /etc/supervisor/conf.d/server-panel-horizon.conf <<SUPERVISOR
[program:server-panel-horizon]
command=/usr/bin/php ${INSTALL_DIR}/artisan horizon
directory=${INSTALL_DIR}
user=www-data
autostart=true
autorestart=true
stopwaitsecs=7200
redirect_stderr=true
stdout_logfile=/var/log/server-panel/horizon.log
SUPERVISOR

  cat > /etc/supervisor/conf.d/server-panel-reverb.conf <<SUPERVISOR
[program:server-panel-reverb]
command=/usr/bin/php ${INSTALL_DIR}/artisan reverb:start --host=127.0.0.1 --port=8080
directory=${INSTALL_DIR}
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/server-panel/reverb.log
SUPERVISOR

  systemctl enable --now supervisor
  supervisorctl reread
  supervisorctl update
  supervisorctl restart server-panel-horizon || true
  supervisorctl restart server-panel-reverb || true
}

run_laravel_setup() {
  log "Running Laravel setup"
  cd "$INSTALL_DIR"
  php artisan key:generate --force
  php artisan migrate --force
  php artisan panel:sync-catalog
  php artisan storage:link || true
  php artisan panel:admin "$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"
  php artisan optimize
}

fix_permissions() {
  log "Fixing permissions"
  mkdir -p \
    "$INSTALL_DIR/storage/app/private" \
    "$INSTALL_DIR/storage/app/public" \
    "$INSTALL_DIR/storage/framework/cache/data" \
    "$INSTALL_DIR/storage/framework/sessions" \
    "$INSTALL_DIR/storage/framework/testing" \
    "$INSTALL_DIR/storage/framework/views" \
    "$INSTALL_DIR/storage/logs" \
    "$INSTALL_DIR/bootstrap/cache"

  chown -R root:www-data "$INSTALL_DIR"
  find "$INSTALL_DIR" -type d -exec chmod 0755 {} +
  find "$INSTALL_DIR" -type f -exec chmod 0644 {} +
  chmod 0755 "$INSTALL_DIR/artisan"
  chmod 0640 "$INSTALL_DIR/.env"
  chmod 0755 "$INSTALL_DIR/deploy/bin/panel-nginx-site" "$INSTALL_DIR/deploy/bin/panel-backup-sites" "$INSTALL_DIR/deploy/bin/panel-system"
  chown -R www-data:www-data "$INSTALL_DIR/storage" "$INSTALL_DIR/bootstrap/cache"
}

run_certbot() {
  if [[ "$ENABLE_SSL" -ne 1 ]]; then
    return
  fi

  log "Requesting Let's Encrypt certificate"
  certbot --nginx \
    -d "$DOMAIN" \
    -m "$LETSENCRYPT_EMAIL" \
    --agree-tos \
    --no-eff-email \
    --redirect \
    --non-interactive
}

parse_args() {
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --domain)
        DOMAIN="${2:-}"
        shift 2
        ;;
      --admin-email)
        ADMIN_EMAIL="${2:-}"
        shift 2
        ;;
      --email)
        LETSENCRYPT_EMAIL="${2:-}"
        shift 2
        ;;
      --ssl)
        ENABLE_SSL=1
        shift
        ;;
      --install-dir)
        INSTALL_DIR="${2:-}"
        shift 2
        ;;
      --mysql-root-password)
        MYSQL_ROOT_PASSWORD="${2:-}"
        shift 2
        ;;
      --app-db)
        APP_DB="${2:-}"
        shift 2
        ;;
      --app-db-user)
        APP_DB_USER="${2:-}"
        shift 2
        ;;
      --app-db-password)
        APP_DB_PASSWORD="${2:-}"
        shift 2
        ;;
      --server-db-user)
        SERVER_DB_USER="${2:-}"
        shift 2
        ;;
      --server-db-password)
        SERVER_DB_PASSWORD="${2:-}"
        shift 2
        ;;
      --admin-password)
        ADMIN_PASSWORD="${2:-}"
        shift 2
        ;;
      --php-fpm-socket)
        PHP_FPM_SOCKET="${2:-}"
        shift 2
        ;;
      --skip-apt)
        SKIP_APT=1
        shift
        ;;
      -h|--help)
        usage
        exit 0
        ;;
      *)
        die "Unknown option: $1"
        ;;
    esac
  done
}

main() {
  parse_args "$@"

  [[ -n "$DOMAIN" ]] || {
    usage
    exit 1
  }

  [[ "${EUID}" -eq 0 ]] || die "Run this installer with sudo/root."

  require_host "$DOMAIN"

  ADMIN_EMAIL="${ADMIN_EMAIL:-admin@${DOMAIN}}"
  LETSENCRYPT_EMAIL="${LETSENCRYPT_EMAIL:-$ADMIN_EMAIL}"
  APP_DB_PASSWORD="${APP_DB_PASSWORD:-$(random_secret)}"
  SERVER_DB_PASSWORD="${SERVER_DB_PASSWORD:-$(random_secret)}"
  ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(random_secret)}"

  install_packages
  install_composer
  systemctl enable --now mariadb redis-server
  detect_php_fpm
  copy_application
  configure_mysql
  write_env
  install_php_dependencies
  build_assets
  install_privileged_helpers
  run_laravel_setup
  fix_permissions
  write_nginx_config
  write_supervisor_config
  run_certbot

  local scheme="http"
  [[ "$ENABLE_SSL" -eq 1 ]] && scheme="https"

  cat <<SUMMARY

Install complete.

URL:          ${scheme}://${DOMAIN}/admin
Admin email:  ${ADMIN_EMAIL}
Admin pass:   ${ADMIN_PASSWORD}

App DB:       ${APP_DB}
App DB user:  ${APP_DB_USER}
App DB pass:  ${APP_DB_PASSWORD}

Server DB bridge user: ${SERVER_DB_USER}
Server DB bridge pass: ${SERVER_DB_PASSWORD}

Save these passwords now. They are also written to ${INSTALL_DIR}/.env except the admin password hash.
SUMMARY
}

main "$@"
