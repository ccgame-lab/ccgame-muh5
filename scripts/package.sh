#!/usr/bin/env bash
# scripts/package.sh
# Build local + tạo artifact. Không SSH, không deploy.
# Chạy từ root dự án: bash scripts/package.sh
#
# Output: artifacts/ccgame-muh5-<timestamp>-<sha>.tar.gz

set -euo pipefail

readonly REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
readonly ARTIFACTS_DIR="${REPO_ROOT}/artifacts"
readonly STAGE_ROOT="${ARTIFACTS_DIR}/stage"
readonly BUN="${BUN:-bun}"

log() { printf '[package] %s\n' "$*" >&2; }
die() { printf '[package] ERROR: %s\n' "$*" >&2; exit 1; }

require_cmd() { command -v "$1" >/dev/null 2>&1 || die "Missing command: $1"; }

verify_output() {
  [[ -f "${REPO_ROOT}/.output/server/index.mjs" ]] || die "Missing .output/server/index.mjs — build failed?"
  [[ -d "${REPO_ROOT}/.output/public/_nuxt" ]]     || die "Missing .output/public/_nuxt — build failed?"
}

build_local() {
  log "bun install..."
  (cd "${REPO_ROOT}" && "${BUN}" install --frozen-lockfile)
  log "bun run build..."
  (cd "${REPO_ROOT}" && NODE_ENV=production "${BUN}" run build)
  verify_output
}

stage_and_package() {
  local ts sha name stage tarball
  ts="$(date -u +%Y%m%d-%H%M%S)"
  sha="$(git -C "${REPO_ROOT}" rev-parse --short HEAD)"
  name="${ts}-${sha}"
  stage="${STAGE_ROOT}/${name}"
  tarball="${ARTIFACTS_DIR}/ccgame-muh5-${name}.tar.gz"

  [[ ! -e "${stage}" ]] || die "Stage already exists: ${stage}"
  mkdir -p "${stage}"

  # Copy .output/, bỏ cache (không cần thiết trên VPS, giảm artifact size)
  rsync -a --exclude='cache/' "${REPO_ROOT}/.output/" "${stage}/.output/" 2>/dev/null \
    || { mkdir -p "${stage}/.output"; cp -a "${REPO_ROOT}/.output/." "${stage}/.output/"; rm -rf "${stage}/.output/cache"; }
  cp -a "${REPO_ROOT}/ecosystem.config.cjs" "${stage}/ecosystem.config.cjs"

  mkdir -p "${ARTIFACTS_DIR}"
  tar -C "${STAGE_ROOT}" -czf "${tarball}" "${name}"

  printf '%s\n' "${name}" "${tarball}" "${stage}"
}

main() {
  for cmd in git "${BUN}" tar; do
    require_cmd "$cmd"
  done

  build_local
  mapfile -t out < <(stage_and_package)
  log "Release name : ${out[0]}"
  log "Tarball      : ${out[1]}"
  log "Stage dir    : ${out[2]} (giữ lại để inspect)"
  log "Deploy: CCGAME_RELEASE=${out[0]} bash scripts/deploy.sh ${out[1]}"
}

main "$@"
