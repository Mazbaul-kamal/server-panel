#!/bin/sh
set -eu

REPO="${SERVER_PANEL_REPO:-Mazbaul-kamal/server-panel}"
BRANCH="${SERVER_PANEL_BRANCH:-master}"
ARCHIVE_URL="https://github.com/${REPO}/archive/refs/heads/${BRANCH}.tar.gz"
TMP_DIR="${TMPDIR:-/tmp}/server-panel-install.$$"

case "${1:-}" in
  -h|--help)
    cat <<'USAGE'
Usage:
  (curl -fsSL https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh || wget -qO- https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/install.sh) | sudo bash -s -- --domain panel.example.com --admin-email admin@example.com

Options are passed to deploy/install.sh:
  --domain DOMAIN                 Required.
  --admin-email EMAIL             Admin login email.
  --ssl                           Request Let's Encrypt SSL after install.
  --email EMAIL                   Let's Encrypt email.
  --install-dir PATH              Default: /var/www/server-panel
USAGE
    exit 0
    ;;
esac

cleanup() {
  rm -rf "$TMP_DIR"
}

trap cleanup EXIT INT TERM

download() {
  if command -v curl >/dev/null 2>&1; then
    curl -fsSL "$ARCHIVE_URL" -o "$TMP_DIR/source.tar.gz"
  elif command -v wget >/dev/null 2>&1; then
    wget -qO "$TMP_DIR/source.tar.gz" "$ARCHIVE_URL"
  else
    echo "curl or wget is required." >&2
    exit 1
  fi
}

mkdir -p "$TMP_DIR"
download
tar -xzf "$TMP_DIR/source.tar.gz" -C "$TMP_DIR"

SOURCE_DIR="$(find "$TMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)"

if [ -z "$SOURCE_DIR" ] || [ ! -f "$SOURCE_DIR/deploy/install.sh" ]; then
  echo "Could not find deploy/install.sh in downloaded archive." >&2
  exit 1
fi

bash "$SOURCE_DIR/deploy/install.sh" "$@"
