#!/usr/bin/env bash
# scripts/rollback.sh
# Rollback symlink-only - không xóa release, không DB rollback.
#
# Usage:
#   bash scripts/rollback.sh               # list releases + current trên VPS
#   bash scripts/rollback.sh <release-name> # rollback sang release đó

set -euo pipefail

readonly SSH_HOST="${CCGAME_SSH_HOST:-ccgame-prod}"
readonly OPT_BASE="/opt/ccgame-muh5"
readonly REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
readonly ECOSYSTEM_REMOTE="${OPT_BASE}/shared/ecosystem.config.cjs"

log() { printf '[rollback] %s\n' "$*" >&2; }
die() { printf '[rollback] ERROR: %s\n' "$*" >&2; exit 1; }

list_releases() {
  ssh "${SSH_HOST}" bash -s <<'REMOTE'
OPT="/opt/ccgame-muh5"
echo "current -> $(readlink -f "${OPT}/current" 2>/dev/null || echo MISSING)"
echo ""
echo "releases:"
ls -1dt "${OPT}/releases"/* 2>/dev/null | head -10 || echo "(none)"
REMOTE
}

smoke() {
  local code
  code="$(curl -fsS -o /dev/null -w '%{http_code}' --max-time 20 \
    "https://muh5.ccgame.org" 2>/dev/null || echo "000")"
  log "smoke muh5.ccgame.org -> HTTP ${code}"
  [[ "${code}" =~ ^(200|301|302)$ ]] || log "WARN: smoke HTTP ${code}"
}

rollback_to() {
  local target="$1"
  ssh "${SSH_HOST}" bash -s -- "${target}" "${ECOSYSTEM_REMOTE}" <<'REMOTE'
set -euo pipefail
TARGET="$1"
ECOSYSTEM="$2"
REL="/opt/ccgame-muh5/releases/${TARGET}"
CURRENT="/opt/ccgame-muh5/current"

[[ -d "${REL}/.output" ]] || { echo "Invalid release: ${REL}" >&2; exit 1; }

ln -sfn "${REL}" "${CURRENT}"
cd "${CURRENT}"
if pm2 describe ccgame-muh5 > /dev/null 2>&1; then
  pm2 restart ccgame-muh5 --update-env
else
  pm2 start "${ECOSYSTEM}" --env production
fi
pm2 save

echo "current -> $(readlink -f "${CURRENT}")"
REMOTE
}

main() {
  if [[ $# -eq 0 ]]; then
    list_releases
    exit 0
  fi
  rollback_to "$1"
  smoke
  log "Rollback done -> $1"
}

main "$@"
