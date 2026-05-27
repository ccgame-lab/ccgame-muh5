# MUH5 - Production Deploy

> Runtime: `bun` + PM2 (`ccgame-muh5`, port `4100`).

## Checklist cố định

**Quy tắc bắt buộc:** `bun run build` phải **hoàn tất** trước khi PM2 restart. Không restart trước khi build xong.

Restart sớm -> HTML SSR trỏ chunk `_nuxt/*` mới nhưng static chưa có -> 404 JSON -> shell không có CSS.

```bash
cd /www/wwwroot/ccgame/ccgame-muh5
git pull --ff-only
bun run build
pm2 startOrRestart ecosystem.config.cjs --env production --update-env
pm2 save
```

Deploy kèm ccgame-web: xem `ccgame-web/docs/deploy.md` mục Checklist deploy cố định.

## PM2 runtime

`ecosystem.config.cjs` pin runtime production cho `ccgame-muh5`:

- `NODE_ENV=production`
- `HOST=0.0.0.0`
- `PORT=4100`

File có cả `env` và `env_production` để tránh lệ thuộc ambient `PORT` khi operator restart bằng PM2.

## Validation

```bash
bun run lint
bun run typecheck
bun run build
pm2 startOrRestart ecosystem.config.cjs --env production --update-env
pm2 status
ss -lntp | grep -E "3100|4100"
curl https://muh5.ccgame.org/api/health
curl https://muh5.ccgame.org/api/notices
curl "https://muh5.ccgame.org/api/leaderboard?tab=power"
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

Loopback check on VPS (dev HMR or production):

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
