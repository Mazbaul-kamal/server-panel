#!/usr/bin/env bash
set -Eeuo pipefail

INSTALL_DIR="/var/www/server-panel"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SKIP_BUILD=0

trap 'echo "Upgrade failed at line $LINENO. Check the message above." >&2' ERR

usage() {
  cat <<'USAGE'
Usage:
  sudo bash deploy/upgrade.sh [options]

Options:
  --install-dir PATH   Default: /var/www/server-panel
  --skip-build         Skip npm build.
  -h, --help           Show this help.
USAGE
}

log() {
  printf '\n==> %s\n' "$1"
}

die() {
  echo "Error: $1" >&2
  exit 1
}

parse_args() {
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --install-dir)
        INSTALL_DIR="${2:-}"
        shift 2
        ;;
      --skip-build)
        SKIP_BUILD=1
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

copy_application() {
  log "Updating application files in ${INSTALL_DIR}"
  [[ -f "$INSTALL_DIR/.env" ]] || die "${INSTALL_DIR}/.env not found. Run install.sh first."

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
}

install_dependencies() {
  log "Installing PHP dependencies"
  cd "$INSTALL_DIR"
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

  if [[ "$SKIP_BUILD" -eq 0 ]]; then
    log "Building frontend assets"

    if [[ -f package-lock.json ]]; then
      npm ci
    else
      npm install
    fi

    npm run build
    rm -rf node_modules
  fi
}

install_privileged_helpers() {
  log "Refreshing helper scripts and sudoers"
  install -o root -g root -m 0750 "$INSTALL_DIR/deploy/bin/panel-nginx-site" /usr/local/sbin/panel-nginx-site
  install -o root -g root -m 0750 "$INSTALL_DIR/deploy/bin/panel-backup-sites" /usr/local/sbin/panel-backup-sites
  install -o root -g root -m 0750 "$INSTALL_DIR/deploy/bin/panel-system" /usr/local/sbin/panel-system
  install -o root -g root -m 0440 "$INSTALL_DIR/deploy/sudoers/server-panel" /etc/sudoers.d/server-panel
  visudo -cf /etc/sudoers.d/server-panel
  install -d -o root -g www-data -m 0750 /etc/server-panel /etc/server-panel/deploy-keys
  printf '%s\n' "$INSTALL_DIR" > /etc/server-panel/root
  printf '%s\n' "/var/www/vhosts" > /etc/server-panel/managed-root
  chmod 0644 /etc/server-panel/root /etc/server-panel/managed-root
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

run_laravel_upgrade() {
  log "Running Laravel upgrade tasks"
  cd "$INSTALL_DIR"
  php artisan down --render='errors::503' || true
  php artisan migrate --force
  php artisan panel:sync-catalog
  php artisan storage:link || true
  php artisan optimize:clear
  php artisan optimize
  php artisan up || true
}

restart_services() {
  log "Restarting services"
  supervisorctl reread || true
  supervisorctl update || true
  supervisorctl restart server-panel-horizon || true
  supervisorctl restart server-panel-reverb || true
  nginx -t
  systemctl reload nginx
}

main() {
  parse_args "$@"

  [[ "${EUID}" -eq 0 ]] || die "Run this upgrader with sudo/root."

  copy_application
  install_dependencies
  install_privileged_helpers
  fix_permissions
  run_laravel_upgrade
  restart_services

  echo
  echo "Upgrade complete."
}

main "$@"
