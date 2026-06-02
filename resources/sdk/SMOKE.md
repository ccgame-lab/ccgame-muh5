# Smoke Checklist — SDK Panel

## Before deploy

- [ ] `npm run build` succeeds (no errors)
- [ ] `php artisan route:list` shows `api/sdk/bootstrap` and `api/sdk/ranking`
- [ ] `curl /api/sdk/bootstrap` returns 200 with `server`, `player`, `tabs`, `features`, `changelog`
- [ ] `curl /api/sdk/ranking` returns 200 with `types` and `items`

## After deploy

- [ ] Open game page in browser
- [ ] "CC" floating button visible at bottom-right
- [ ] Click "CC" — panel opens without JS error
- [ ] Console has no `[Vue warn]`, `[Error]`, or `[Uncaught]`
- [ ] Overview tab shows server name, player name, wallet balances
- [ ] Feature buttons render (only active ones)
- [ ] Click "BXH" tab — sub-tabs ZS/Power appear
- [ ] Ranking cards render with #1/#2/#3 badges
- [ ] Click "Cập nhật" tab — changelog entries render
- [ ] Switch back to Overview — data still present (no refetch)
- [ ] Network panel: bootstrap fetched once, ranking fetched only on first BXH click
- [ ] Panel does not overlap game iframe in a broken way
- [ ] Close panel (X button) — panel animates out
- [ ] Reopen panel — no refetch of bootstrap (session cache optional, not required)
