# MUH5 - Production Deploy

> Runtime: `bun` + PM2 (`ccgame-muh5`, port `4100`).

## Checklist cố định

**Quy tắc bắt buộc:** `bun run build` phải **hoàn tất** trước khi PM2 restart. Không restart trước khi build xong.

Restart sớm -> HTML SSR trỏ chunk `_nuxt/*` mới nhưng static chưa có -> 404 JSON -> shell không có CSS.

```bash
cd /www/wwwroot/ccgame/ccgame-muh5
git pull --ff-only
bun run build
pm2 delete ccgame-muh5 2>/dev/null || true
pm2 start ecosystem.config.json --env production --update-env
pm2 save
```

Deploy kèm ccgame-web: xem `ccgame-web/docs/deploy.md` mục Checklist deploy cố định.

## PM2 runtime

Dùng `ecosystem.config.json` (PM2 trên host này parse JSON đúng; file `.cjs` có thể bị chạy như script thay vì `apps[]`).

- `cwd`: `/www/wwwroot/ccgame/ccgame-muh5` (absolute, tránh lệch cwd)
- `script`: `.output/server/index.mjs`
- `interpreter`: `bun`
- `NODE_ENV=production`
- `HOST=0.0.0.0`
- `PORT=4100`

`env` và `env_production` cùng giá trị production.

**Dev HMR (local only):** `bun run dev -- --host 0.0.0.0 --port 4100` — không dùng PM2 production config.

**SDK UI preview (dev only):** tái dùng `SdkButton` / `SdkPanel`, không iframe Egret / `muh5-client`. Route `404` trên production build (`import.meta.dev`).

Workflow SDK/UI (mặc định — **không** bật HMR trên `muh5.ccgame.org`):

```bash
cd /www/wwwroot/ccgame/ccgame-muh5
bun run dev -- --host 0.0.0.0 --port 4101
# open http://127.0.0.1:4101/sdk-preview
```

Dùng port `4101` để tránh đụng production PM2 trên `4100`.

Smoke mobile qua domain/proxy (tạm thời, không phải default):

- Có thể chạy dev/HMR qua domain khi cần, nhưng **không `pm2 save`**, không đánh giá performance bằng dev runtime.
- Xong phải `bun run build` + restart production (`ecosystem.config.json`) ngay.

## Validation

```bash
bun run lint
bun run typecheck
bun run build
pm2 delete ccgame-muh5 2>/dev/null || true
pm2 start ecosystem.config.json --env production --update-env
pm2 save
pm2 status
pm2 env ccgame-muh5 | grep -E "NODE_ENV|HOST|PORT" || true
ss -lntp | grep -E "3100|4100"
curl -I -H "Host: muh5.ccgame.org" http://127.0.0.1:4100/play
curl http://127.0.0.1:4100/api/health
curl http://127.0.0.1:4100/api/notices
curl "http://127.0.0.1:4100/api/leaderboard?tab=power"
curl -s -H "Host: muh5.ccgame.org" http://127.0.0.1:4100/play | grep -E "@vite/client|hmr|vite" || true
```

Browser smoke:

- `https://ccgame.org/play/muh5?v=ecosystem1`
- guest enters game
- GreenJade enters game

## Asset smoke

```bash
curl -sI "https://muh5.ccgame.org/_nuxt/entry.DYe-K-kI.css" | head -5
# Expect: HTTP 200, content-type text/css
```

## Static client cache (muh5-client)

Egret bundles under `/muh5-client/h5/*.js` are **not content-hashed**. Do not use `immutable` or multi-day cache without a deploy/version bump plan.

Loopback check on VPS (production):

```bash
H='-H Host: muh5.ccgame.org'
curl -sI $H http://127.0.0.1:4100/muh5-client/index.html | grep -i cache-control
curl -sI $H http://127.0.0.1:4100/muh5-client/config.js | grep -i cache-control
curl -sI $H http://127.0.0.1:4100/muh5-client/h5/ccgame-entrance.js | grep -i cache-control
curl -sI $H http://127.0.0.1:4100/muh5-client/h5/egret.min.js | grep -i cache-control
```

Expected intent:

| Path | Cache-Control |
|------|----------------|
| `/play` | `no-store` |
| `index.html`, `config.js`, `ccgame-entrance.js`, `manifest.json` | `no-cache` (index: `no-store`) |
| `h5/*.min.js` (non-hashed) | `public, max-age=86400, must-revalidate` |
| Cloudflare `cdn.ccgame.org` | Separate CDN policy; purge after client deploy |

After changing `h5/*.js`, assume browsers may keep old copies up to 24h unless CDN/HTML entry is purged or cache-busted.
