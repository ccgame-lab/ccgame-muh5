# CCGame MUH5

Game web MU Online H5 (`muh5.ccgame.org`), portal + SDK panel. Laravel 12 + PHP 8.4 + Vue 3 (Vite) + Filament v4 admin + MySQL. Cắm ví GreenJade để bán Tôm (tiền nạp thật).
Kiến trúc: nginx -> php-fpm; queue qua supervisor `ccgame-muh5-worker`; SDK panel Vue build riêng ra `public/assets/sdk/`. KHÁC `ccgame-web` (gateway Nuxt) và `muh5.ccgame` (repo legacy, KHÔNG phải prod).

## Game server MU H5 (CORE, sống trên VPS, NGOÀI repo này)

- Repo Laravel này = PORTAL (cổng + ví + SDK + GM). KHÔNG chứa logic game (level, chuyển sinh, drop, skill). Portal chỉ ĐỌC bảng `actors` (level/zhuansheng_lv/totalpower) + tác động GIÁN TIẾP qua đúng 5 GM cmd trong `GmApiService::executeCommand()`: kick / ban / send_mail / send_global_mail / charge_yuanbao. KHÔNG có lệnh set level/exp.
- Game server thật: VPS `ccgame-prod` (ssh, root), path `/opt/muh5/server1` = **S1 LIVE** (process gameworld_24_5/dbserver/loggerserver/gateway_qiji; `/opt/ops.md` = port map). **`/opt/muh5/server99` = DEV SHARD** (dựng 2026-06-15, ports 5099/6099/7099/9099, DB actor_s99 fresh + globaldata_bt shared; control qua `cluster.sh s99 start|stop|status`). Dev shard để test gameplay (ZS/cap level...) trước khi đẩy lên S1. Core = C++ binary + Lua 5.1. Mặt sửa được = Lua + config dưới `gameworld/data/`. Cluster manager: `/opt/muh5/scripts/cluster.sh` (status/start/stop/restart + deploy-lua an toàn).
- Game server có LUẬT RIÊNG: `/opt/muh5/server1/AGENTS.md` (Locked Mode: S1-only, chỉ patch Lua/config, KHÔNG sửa binary/DB schema/stored procedure/cross-server, rollback = file restore, ghi changelog sau mỗi đổi). Đọc `docs/README.md` (AI route map) trước khi sửa hệ nào. TÔN TRỌNG luật server đó khi đụng vào.
- Level cap + chuyển sinh (ZhuanSheng): `gameworld/data/config/zhuansheng/zhuanshenglevel.lua` (12 tier, level mốc 400..3400) + `functions/systems/actor/zhuanshengsystem.lua` + `checkLevelLimit` trong `functions/systems/actor/actorexp.lua`. EXP table `config/actor/exp.lua` (cap 3600). Client Egret `config1.json` CŨNG hardcode 12 tier ZS -> đổi tier/cap phải sync server + client, có validator `checkexcel/ckzhuansheng.lua`.

## Lệnh

- Branch flow: `feature/*` -> `dev` -> smoke -> `master` (protected). Hotfix: `hotfix/*` -> master + dev. Rollback = `git revert`, KHÔNG `reset --hard` trên master.
- Test gate: `vendor\bin\pint` + `php artisan test` (suite chạy sqlite :memory; migration MySQL-only đã guard theo `DB::getDriverName()`). larastan không cài local.
- Build SDK Vue: `cd resources/sdk && npm run build` -> `public/assets/sdk/ccgame-sdk.{js,css}`. Commit SDK build TÁCH RIÊNG khỏi commit PHP.
- Deploy (origin = `ccgame-lab/ccgame-muh5` @ master, ssh alias `ccgame-prod`, path `/www/wwwroot/ccgame/ccgame-muh5`):
  ```bash
  git push origin master
  ssh ccgame-prod 'cd /www/wwwroot/ccgame/ccgame-muh5 && git pull && php artisan route:clear && php artisan route:cache && php artisan config:cache'
  # nếu đổi code PHP dùng bởi job:
  ssh ccgame-prod 'supervisorctl restart ccgame-muh5-worker'
  ```
- Migration KHÔNG auto-run khi deploy. Chỉ `--pretend`, review tay rồi chạy có chủ đích.

## Tiền tệ (đọc trước khi đụng bất kỳ flow nào liên quan)

| Tiền | Cột / nguồn | Ghi chú |
|---|---|---|
| POINT | `users.points` | Currency portal nội bộ, duy nhất active. `PointService.credit($user, ...)` truyền OBJECT user, không phải id. |
| TÔM | ví GreenJade (ngoài DB này) | TIỀN THẬT. Read-only display. Mutation CHỈ qua `GreenJadeClient::spend()`. |
| WCoin | `users.wcoin` | Dormant, giữ cột, KHÔNG dùng trong code mới. |

## GreenJade wallet (vùng nhạy cảm: MODE safety-first, hỏi trước khi sửa)

- Balance: `GET /api/internal/services/{serviceCode}/wallet-balance?user_id=` -> `data.tom_balance`. Bootstrap cache `gj_bal_{uid}` 60s. Render gate `v-if="wallet.tom != null"`.
- Spend: `POST /api/internal/services/{serviceCode}/wallet-spend`. Header auth `X-Service-Secret` so với `ServiceClient.metadata.service_secret`.
- **ServiceClient slug = `muh5`** (KHÔNG phải `ccgame`).
- Shop Tôm: `GET /api/pshop/items`, `POST /api/pshop/buy-tom` (qua `GreenJadeClient.spend()`).
- **NEVER auto-refund.** Delivery fail -> RE-DELIVER (tiền đã settle đúng), không hoàn. Refund chỉ qua nút admin Filament.
- Delivery self-heal: `GmAction` mark `dispatched` (KHÔNG optimistic `delivered`). Command `tom:reconcile-deliveries` (schedule mỗi 2 phút) reconcile + auto re-deliver + dup-guard + alert. CẦN root cron `* * * * * php artisan schedule:run` trên prod, mất cron = self-heal chết âm thầm. Alert qua env `TELEGRAM_BOT_TOKEN` + `TELEGRAM_OPS_CHAT_ID`.

## Style khác chuẩn

- `declare(strict_types=1)` mọi file PHP.
- `config:cache` active trên prod -> dùng `config()`, KHÔNG `env()` trong app code.
- Routes là closure trong `routes/web.php`, pattern cố định.
- KHÔNG ký tự em-dash ở bất kỳ đâu (code, nội dung, seed).
- GmApiService: thêm method mới PHẢI wire vào `executeCommand()` match ngay.

## Gotcha đã trả giá

- `resources/sdk/node_modules` bị git-TRACK (commit trước .gitignore) và là binary Linux. Sau `npm install`/build trên Windows: `git checkout -- resources/sdk/node_modules` TRƯỚC khi commit.
- Spin yuanbao -> `GmAction::create()` -> queue -> `GmApiService::chargeCurrency()` (itemid = prize_value * 1000). Sau deploy code job phải `supervisorctl restart ccgame-muh5-worker`.
- Quà Online (type 17): game.min.js không có handler, không sửa được (no Egret source). Dùng type 11.
- spin-wheel v5: `overlayImage` phải là `HTMLImageElement`, không phải string URL.
- Feed (SocialEvent): đọc `Redis::lrange('social_feed:global',0,29)`, model KHÔNG có cột `is_active`, flood-guard 1 event/user/5s.
- Bug từng phát hiện từ ccgame-web (đã fix): `PORTAL_URL` phải `https://ccgame.org`. `PORTAL_API_URL` còn localhost trên prod = việc cần dọn.

## Vùng cấm

- KHÔNG touch: payment/wallet/settlement logic, `migrate:fresh`, flush Redis production.
- KHÔNG auto-refund (xem trên).
- KHÔNG hardcode secret. Repo chỉ chứa TÊN env (`TELEGRAM_BOT_TOKEN`, service secret...), giá trị nằm trong `.env` prod / Bitwarden.
- KHÔNG xóa cron self-heal / đổi GmAction sang optimistic delivered.

## Đội agent (.claude/agents) - subagents, 3 đội theo domain

Mặc định Claude chính tự làm; chỉ triệu agent đúng cửa. Claude chính = bàn tay DUY NHẤT (Edit/Write/Commit/Deploy) + tổng hợp.

- **Portal**: `muh5-scout` (read-only, map code Laravel/Vue/Filament ra `file:line`) + `economy-architect` (phản biện kinh tế/bán Tôm qua deepseek-v4-pro).
- **Game server** (core Lua/C++ VPS): `muh5-gameserver-ops` (patch Lua/config + restart shard qua SSH, backup/verify/rollback, bám `/opt/muh5/server1/AGENTS.md`).
- **Game client** (Egret config + assets): `muh5-client-ops` (việt hoá/sửa config1.json, pipeline `tools/cfg-i18n`, phát hành R2/CDN qua `tools/r2-release`, lưu ý cache 31d).
- Server vs Client TÁCH BẠCH: đụng ZS tier/level cap phải sync cả 2 phía. Không đội nào đụng ví GreenJade. Chi tiết: `.claude/agents/README.md`.
