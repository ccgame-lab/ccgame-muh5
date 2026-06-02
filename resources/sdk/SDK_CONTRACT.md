# SDK Contract — ccgame H5 Panel

## API Endpoints

### GET /api/sdk/bootstrap

Fetch once when panel first opens. Lightweight, no ranking data.

```json
{
  "server": { "id": "1", "name": "S1 MUH5" },
  "player": { "id": 123, "name": "PlayerName", "level": 0, "vip": 0 },
  "wallet": { "tom": 1000, "wcoin": 50, "wpoint": 0 },
  "tabs": [
    { "key": "overview", "label": "Tổng quan" },
    { "key": "ranking", "label": "BXH" },
    { "key": "changelog", "label": "Cập nhật" }
  ],
  "features": [
    { "key": "topup", "label": "Nạp", "active": true, "href": "/topup", "note": "" }
  ],
  "changelog": [
    { "date": "2026-06-02", "title": "Title", "body": "line1\nline2" }
  ]
}
```

### GET /api/sdk/ranking

Lazy fetch only when BXH tab is first opened.

```json
{
  "types": [
    { "key": "zs", "label": "Chuyển Sinh", "metric": "zs", "secondary_metric": "level", "secondary_label": "Cấp" },
    { "key": "power", "label": "Lực Chiến", "metric": "power", "secondary_metric": "zs", "secondary_label": "ZS" }
  ],
  "items": {
    "zs": [{ "name": "PlayerName", "level": 88, "zs": 3, "power": 12345, "vip": 2 }],
    "power": [{ "name": "PlayerName", "level": 88, "zs": 3, "power": 12345, "vip": 2 }]
  }
}
```

## Rules

- Frontend is a dumb renderer. Backend is the single source of truth.
- SDK endpoints are in `routes/web.php` because the iframe page uses web middleware for consistency.
- No wallet mutation, payment logic, or settlement in SDK code.
- No hardcoded server name or game title — all from API.
- No hardcoded tabs — tabs array drives navigation.
- No hardcoded features — features array drives grid.
- Only render features where `active: true`.
- Ranking is fetched lazily on first BXH tab click and cached in memory.
- Sub-tab switch does not refetch ranking.
- No component library. No Vue Router. No Pinia.
- CSS prefix: `ccgame-sdk-`.
- Build output: `public/assets/sdk/ccgame-sdk.js` + `ccgame-sdk.css`.

## Clone new game

```bash
cp -r resources/sdk new-game/resources/sdk
cd new-game/resources/sdk && npm ci && npm run build
```

Then customize: `routes/web.php` API adapters, `resources/sdk/src/styles/sdk.css` theme variables.
