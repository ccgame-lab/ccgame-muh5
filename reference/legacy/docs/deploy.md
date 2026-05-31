# CCGame MUH5 - Deploy Guide

> **Runtime:** Production dùng `bun` làm interpreter. Không dùng `node` để chạy `.output/server/index.mjs`.

---

## Tổng quan

Deploy theo mô hình **local build → artifact upload → PM2 reload**. VPS không build, không cần `node_modules`.

```
local:
  bash scripts/package.sh          → build + tar .output/

vps:
  extract vào /opt/ccgame-muh5/releases/<timestamp>-<sha>/
  symlink  /opt/ccgame-muh5/current -> release mới
  pm2 reload từ /opt/ccgame-muh5/shared/ecosystem.config.cjs
```

Cấu trúc VPS:

```
/opt/ccgame-muh5/
  current -> releases/20260529-062330-5d2b5d4   ← symlink mới nhất
  releases/
    20260529-062330-5d2b5d4/
      .output/
      ecosystem.config.cjs
      .env -> ../../shared/.env                  ← symlink
  shared/
    .env                                         ← secrets, không commit
    ecosystem.config.cjs                         ← được copy mỗi lần deploy
```

> MUH5 chỉ đọc DB (read-only: portal wallet, game leaderboard) nên **không có `.data/`** runtime như ccgame-web.

---

## Prerequisites

| Tool | Ghi chú |
|------|---------|
| `bun` | Build local và runtime trên VPS |
| `pm2` | Process manager trên VPS |
| SSH alias `ccgame-prod` | Cấu hình trong `~/.ssh/config` |
| `/opt/ccgame-muh5/shared/.env` | Tạo thủ công lần đầu trên VPS |

---

## Deploy thông thường

### 1. Package (build local)

```bash
bash scripts/package.sh
```

Output cuối cùng sẽ in ra dòng deploy, ví dụ:

```
[package] Deploy: CCGAME_RELEASE=20260529-062330-5d2b5d4 bash scripts/deploy.sh artifacts/ccgame-muh5-20260529-062330-5d2b5d4.tar.gz
```

### 2. Deploy (ship lên VPS)

Copy dòng đó và chạy:

```bash
CCGAME_RELEASE=20260529-062330-5d2b5d4 bash scripts/deploy.sh artifacts/ccgame-muh5-20260529-062330-5d2b5d4.tar.gz
```

Script sẽ:
- `scp` artifact + ecosystem lên VPS
- Extract vào `releases/<name>/`
- Tạo symlink `.env` từ `shared/` vào release mới
- Cập nhật symlink `current` → release mới
- Khởi động hoặc restart PM2 tương thích với PM2 7.x (dùng `pm2 restart ccgame-muh5 --update-env` theo tên app)
- Smoke test `https://muh5.ccgame.org`

---

## Rollback

```bash
# Xem releases hiện có
bash scripts/rollback.sh

# Rollback về release cụ thể
bash scripts/rollback.sh 20260529-062330-5d2b5d4
```

Rollback chỉ đổi symlink + pm2 reload. Nhanh, không ảnh hưởng DB.

---

## Lần đầu tiên (First-time setup VPS)

```bash
# Trên VPS - chạy một lần duy nhất
mkdir -p /opt/ccgame-muh5/releases /opt/ccgame-muh5/shared

# Copy .env hiện tại vào shared (đổi HOST về 127.0.0.1 - nginx proxy nội bộ)
sed 's/^HOST=0.0.0.0/HOST=127.0.0.1/' \
  /www/wwwroot/ccgame/ccgame-muh5/.env > /opt/ccgame-muh5/shared/.env
chmod 600 /opt/ccgame-muh5/shared/.env
```

> **Cut over từ deploy cũ:** nếu PM2 process `ccgame-muh5` đang chạy với `cwd` cũ (`/www/...`), `pm2 restart` **không** đổi cwd. Phải `pm2 delete ccgame-muh5` rồi `pm2 start /opt/ccgame-muh5/shared/ecosystem.config.cjs` để cwd trỏ về `current`. Lần deploy sau dùng `restart` bình thường.

Sau đó chạy `package.sh` + `deploy.sh` như bình thường.

---

## Useful PM2 Commands

```bash
pm2 list
pm2 logs ccgame-muh5 --lines 50
pm2 status ccgame-muh5
```

---

## Environment Variables

File `/opt/ccgame-muh5/shared/.env` - không commit, không push.

```dotenv
# ── Process ──────────────────────────────────────────────
HOST=127.0.0.1
PORT=4100

# ── MU H5 Launch (signed token, shared với ccgame-web) ────
MUH5_LAUNCH_SECRET=<shared-secret-with-ccgame-web>

# ── SDK read-only DB (portal: wallet; game: leaderboard) ──
# SELECT only trong code. Prefix NUXT_ để runtimeConfig nhận lúc runtime
# (Nuxt bakes runtimeConfig lúc build → local build không có biến DB).
NUXT_MUH5_PORTAL_DB_HOST=127.0.0.1
NUXT_MUH5_PORTAL_DB_PORT=3306
NUXT_MUH5_PORTAL_DB_NAME=muh5_ccgame
NUXT_MUH5_PORTAL_DB_USER=<readonly-user>
NUXT_MUH5_PORTAL_DB_PASSWORD=<readonly-password>
NUXT_MUH5_GAME_DB_HOST=127.0.0.1
NUXT_MUH5_GAME_DB_PORT=3306
NUXT_MUH5_GAME_DB_NAME=actor_s1
NUXT_MUH5_GAME_DB_USER=<readonly-user>
NUXT_MUH5_GAME_DB_PASSWORD=<readonly-password>
```

> [!NOTE]
> MUH5 không ghi DB và không có `.data/` runtime. Mọi verification (wallet, entitlement, reward, settlement) thuộc về server-side later, không phải Task 01.

---

## Nginx

App nghe tại `127.0.0.1:4100`:

```nginx
location / {
    proxy_pass http://127.0.0.1:4100;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
```

Vhost: `muh5.ccgame.org` (+ `muh5-ws.ccgame.org` cho game client). Không cần đổi khi deploy - port giữ nguyên 4100.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Bun is not defined` at startup | PM2 chưa dùng `interpreter: bun` - kiểm tra `shared/ecosystem.config.cjs` |
| Port 4100 not responding | `pm2 logs ccgame-muh5` - xem lỗi boot |
| `cwd` vẫn trỏ `/www/...` sau deploy | `pm2 restart` không đổi cwd. `pm2 delete` + `pm2 start` từ ecosystem mới |
| Shell unstyled / `_nuxt/*.js` 404 | Build chưa xong đã restart. Chạy lại `package.sh` + `deploy.sh` |
| ENV vars not picked up | `--update-env` đã có trong script. Kiểm tra `shared/.env` |
| `NUXT_MUH5_*` thay đổi nhưng không có hiệu lực | Nuxt bakes `runtimeConfig` lúc build - cần rebuild + redeploy |
| `/report` 404 trong logs | Game client gửi telemetry tới route shell không có. Vô hại, ngoài phạm vi Task 01 |

---

## Validation (sau deploy)

```bash
ssh ccgame-prod
pm2 status
pm2 env ccgame-muh5 | grep -E "NODE_ENV|HOST|PORT" || true
ss -lntp | grep -E "3100|4100"
curl -I -H "Host: muh5.ccgame.org" http://127.0.0.1:4100/play
curl http://127.0.0.1:4100/api/health
curl "http://127.0.0.1:4100/api/leaderboard?tab=power"
curl http://127.0.0.1:4100/api/hall-of-fame
curl http://127.0.0.1:4100/api/social
# Đảm bảo build production, không dính vite dev:
curl -s -H "Host: muh5.ccgame.org" http://127.0.0.1:4100/play | grep -E "@vite/client|hmr|vite" || true
```

Browser smoke:

- `https://ccgame.org/play/muh5`
- guest enters game
- GreenJade enters game

## Asset smoke

```bash
curl -sI "https://muh5.ccgame.org/_nuxt/entry.DYe-K-kI.css" | head -5
# Expect: HTTP 200, content-type text/css
```

## Static client cache (muh5-client)

Egret bundles dưới `/muh5-client/h5/*.js` **không content-hash**. Không dùng `immutable` hay cache nhiều ngày nếu chưa có kế hoạch version bump.

Loopback check trên VPS:

```bash
H='-H Host: muh5.ccgame.org'
curl -sI $H http://127.0.0.1:4100/muh5-client/index.html | grep -i cache-control
curl -sI $H http://127.0.0.1:4100/muh5-client/h5/egret.min.js | grep -i cache-control
```

| Path | Cache-Control |
|------|----------------|
| `/play` | `no-store` |
| `index.html`, `config.js`, `ccgame-entrance.js`, `manifest.json` | `no-cache` (index: `no-store`) |
| `h5/*.min.js` (non-hashed) | `public, max-age=86400, must-revalidate` |
| Cloudflare `cdn.ccgame.org` | Separate CDN policy; purge sau client deploy |

Sau khi đổi `h5/*.js`, giả định browser có thể giữ bản cũ tới 24h nếu chưa purge CDN/HTML entry.
