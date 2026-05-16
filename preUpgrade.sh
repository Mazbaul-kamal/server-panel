#!/bin/sh
set -eu

REPO="${SERVER_PANEL_REPO:-Mazbaul-kamal/server-panel}"
BRANCH="${SERVER_PANEL_BRANCH:-master}"
ARCHIVE_URL="https://github.com/${REPO}/archive/refs/heads/${BRANCH}.tar.gz"
TMP_DIR="${TMPDIR:-/tmp}/server-panel-upgrade.$$"

case "${1:-}" in
  -h|--help)
    cat <<'USAGE'
Usage:
  (curl -fsSL https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/preUpgrade.sh || wget -qO- https://raw.githubusercontent.com/Mazbaul-kamal/server-panel/master/preUpgrade.sh) | sudo bash

Options are passed to deploy/upgrade.sh:
  --install-dir PATH   Default: /var/www/server-panel
  --skip-build         Skip npm build.
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

if [ -z "$SOURCE_DIR" ] || [ ! -f "$SOURCE_DIR/deploy/upgrade.sh" ]; then
  echo "Could not find deploy/upgrade.sh in downloaded archive." >&2
  exit 1
fi

bash "$SOURCE_DIR/deploy/upgrade.sh" "$@"
