#!/usr/bin/env bash
# scripts/deploy.sh
# Ship artifact lên VPS: extract + symlink current + pm2 reload.
# Không build trên VPS. Không upload node_modules.
#
# Usage:
#   CCGAME_RELEASE=<name> bash scripts/deploy.sh <path/to/ccgame-muh5-<name>.tar.gz>
#
# Thường chạy sau package.sh — copy dòng "Deploy:" từ output của package.sh.

set -euo pipefail

readonly SSH_HOST="${CCGAME_SSH_HOST:-ccgame-prod}"
readonly OPT_BASE="/opt/ccgame-muh5"
readonly REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
readonly ECOSYSTEM_SRC="${REPO_ROOT}/ecosystem.config.cjs"
readonly ECOSYSTEM_REMOTE="${OPT_BASE}/shared/ecosystem.config.cjs"

log() { printf '[deploy] %s\n' "$*" >&2; }
die() { printf '[deploy] ERROR: %s\n' "$*" >&2; exit 1; }

require_cmd() { command -v "$1" >/dev/null 2>&1 || die "Missing command: $1"; }

smoke_url() {
  local url="$1"
  local code
  code="$(curl -fsS -o /dev/null -w '%{http_code}' --max-time 20 \
    "${url}" 2>/dev/null || echo "000")"
  log "smoke ${url} -> HTTP ${code}"
  [[ "${code}" =~ ^(200|301|302)$ ]] || log "WARN: smoke ${url} HTTP ${code} (kiểm tra pm2 logs)"
}

smoke() {
  smoke_url "https://muh5.ccgame.org"
}

main() {
  local tarball="${1:-}"
  local release_name="${CCGAME_RELEASE:-}"

  for cmd in ssh scp; do require_cmd "$cmd"; done
  [[ -f "${ECOSYSTEM_SRC}" ]] || die "Missing ${ECOSYSTEM_SRC}"
  [[ -n "${tarball}" && -f "${tarball}" ]] || die "Usage: CCGAME_RELEASE=<name> $0 <tarball>"
  [[ -n "${release_name}" ]] || die "Set CCGAME_RELEASE (từ output của package.sh)"

  local remote_tar="/tmp/ccgame-muh5-${release_name}.tar.gz"
  log "Upload artifact + ecosystem..."
  scp "${tarball}" "${SSH_HOST}:${remote_tar}"
  scp "${ECOSYSTEM_SRC}" "${SSH_HOST}:${ECOSYSTEM_REMOTE}"

  ssh "${SSH_HOST}" bash -s -- "${release_name}" "${remote_tar}" "${ECOSYSTEM_REMOTE}" <<'REMOTE'
set -euo pipefail
NAME="$1"
TAR="$2"
ECOSYSTEM="$3"
OPT_BASE="/opt/ccgame-muh5"
REL="${OPT_BASE}/releases/${NAME}"
SHARED="${OPT_BASE}/shared"
CURRENT="${OPT_BASE}/current"

die_r() { printf '[deploy-remote] ERROR: %s\n' "$*" >&2; exit 1; }

[[ -f "${SHARED}/.env" ]] || die_r "Missing ${SHARED}/.env — copy thủ công trước deploy lần đầu"
[[ ! -d "${REL}" ]]        || die_r "Release already exists: ${REL}"

mkdir -p "${OPT_BASE}/releases"
tar -xzf "${TAR}" -C "${OPT_BASE}/releases"
[[ -f "${REL}/.output/server/index.mjs" ]] || die_r "Missing .output/server/index.mjs trong release"

# Symlink shared .env vào release (muh5 dùng DB read-only, không có .data)
ln -sfn "${SHARED}/.env" "${REL}/.env"

# Swap current
ln -sfn "${REL}" "${CURRENT}"

# PM2 restart (PM2 7.x: dùng stop+start hoặc restart by name)
cd "${CURRENT}"
if pm2 describe ccgame-muh5 > /dev/null 2>&1; then
  pm2 restart ccgame-muh5 --update-env
else
  pm2 start "${ECOSYSTEM}" --env production
fi
pm2 save

# Dọn artifact
rm -f "${TAR}"

# Giữ tối đa 5 releases
ls -1dt "${OPT_BASE}/releases"/* 2>/dev/null | tail -n +6 | xargs -r rm -rf

echo "current -> $(readlink -f "${CURRENT}")"
REMOTE

  smoke
  log "Done. Release: ${release_name}"
}

main "$@"
