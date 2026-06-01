# CODEX — ccgame-muh5 Port Context

> File này hướng dẫn craft PHP code cho ccgame-muh5.
> Đọc trước khi viết bất kỳ dòng code nào.

---

## 1. Kiến trúc tổng thể

```
ccgame-web (Nuxt 4)          → public launcher, guest surface, AI MC
ccgame-muh5 (Laravel 12 + Blade + Vite SDK)  → MUH5 game surface (auth, game bridge, dashboard)
muh5.ccgame (Laravel 12)     → legacy auth ID cũ, sẽ port dần sang ccgame-muh5
```

**Quan hệ:**
- `ccgame-web` = guest-first, signed launch token → `ccgame-muh5`
- `ccgame-muh5` = auth, game bridge, dashboard, admin
- `muh5.ccgame` = legacy Laravel — đọc reference, không sửa

---

## 2. Legacy `muh5.ccgame` — Cấu trúc cần port

### 2.1 Routes (web.php)

| Method | URI | Controller | Mô tả |
|--------|-----|-----------|-------|
| GET | `/` | Closure | Home → redirect nếu đã login |
| GET | `/login` | GreenJadeAuthController@loginBridge | Redirect sang GreenJade ID |
| GET | `/register` | GreenJadeAuthController@loginBridge | Redirect sang GreenJade ID |
| GET | `/auth/login/perform` | GreenJadeAuthController@redirect | PKCE OAuth redirect |
| GET | `/auth/greenjade/callback` | GreenJadeAuthController@callback | OAuth callback |
| GET/POST | `/logout` | GreenJadeAuthController@logout | Logout bridge |
| GET | `/auth/logout/perform` | GreenJadeAuthController@performLogout | Logout + session destroy |
| GET | `/dashboard` | view('app-shell') | TS app shell |
| GET | `/dashboard/stats` | DashboardController@stats | JSON stats |
| GET | `/dashboard/checkin-status` | DashboardController@checkinStatus | JSON checkin |
| GET | `/play` | PlayController@entry | Game entry |
| GET | `/play-test` | PlayController@playTest | Restricted test |
| GET | `/playgame/{server}` | PlayController@game | Game launch |
| GET | `/game-launcher` | PlayController@launcher | Game client launcher |
| GET | `/hall-of-fame/rankings` | HallOfFameController@rankings | JSON rankings |
| GET | `/hall-of-fame/my-rank` | HallOfFameController@myRank | JSON my rank |
| GET | `/announcements/latest` | AnnouncementController@latest | JSON |
| POST | `/announcements/ack` | AnnouncementController@acknowledge | JSON |
| POST | `/giftcode/redeem` | GiftcodeController@redeem | JSON |
| GET | `/status.php` | Closure | Server status |
| GET | `/playgame/status.php` | Closure | Server status |

**Auth-protected group** (`auth`, `jetstream.auth_session`, `verified`):
- `/mining/*` — DiamondGeneratorController (claim, upgrade, unlock, checkin, leaderboard, boost, ascend, wpoint purchase, servers)
- `/lucky-spin/*` — LuckySpinController (checkin, login-status, spin, buy-fruit)
- `/pshop/*` — PShopController (purchase, claim-daily, claim-milestone, craft-crystal)
- `/s1-shop/*` — S1ShopController (index, purchase)
- `/history/*` — TransactionHistoryController (wpoint, wcoin)
- `/player-tool/*` — PlayerToolController (charge, send-mail, items) — restricted to quocquoc

### 2.2 Auth Flow (GreenJade ID OAuth)

```
User → /login → /auth/login/perform
  → redirect GreenJade ID /oauth/authorize (PKCE S256)
  → callback → /auth/greenjade/callback
    → exchange code for token
    → fetch /api/oauth/userinfo
    → User::updateOrCreate(portal_uid)
    → Auth::login
    → redirect /dashboard

Logout:
  → /logout → view logout-loading → /auth/logout/perform
    → Auth::logout, session invalidate
    → redirect GreenJade ID /oauth/logout
```

**User model fields:** `portal_uid`, `username`, `password` ('greenjade-sso'), `name`, `email`, `tier`, `wcoin`, `wpoint`, `last_login_ip`, `last_login_at`, `checkin_boost_expires_at`, `last_seen_announcement_id`

### 2.3 Game Bridge (PlayController)

**Launch token generation:**
```php
$sign = md5($user->username.$time.config('portal.game_secret'));
$ps = hash_hmac('sha256', $user->username.'|'.$server->id.'|'.$time, config('portal.game_secret'));
```

**Launcher (game-launcher):**
- Receives `user`, `srvid`, `spverify`, `srvaddr`, `srvport` as query params
- Updates `globaldata_bt.global_user` and `{db_name}.globaluser` with password
- Renders game client HTML (Egret engine, manifest.json, WebGL)

### 2.4 Game DB Operations (GmApiService)

| Method | Mô tả |
|--------|-------|
| `sendItemMail` | Insert into `gmcmd` table (cmd: sendMail) |
| `sendGlobalMail` | Loop all actors, send individual mails |
| `chargeCurrency` | Insert into `feecallback` (amount * 1000) |
| `kickPlayer` | Insert into `gmcmd` (cmd: kick) |
| `mutePlayer` | Insert into `gmcmd` (cmd: shutup) |
| `banPlayer` | Kick + rename accountname |
| `findActor` | Query `actors` table |
| `deductDiamond` | Update `actors.yuanbao` + gmcmd |
| `setTitleSlots` | Update `actors.title_slots` |

### 2.5 Dashboard Stats

**`/dashboard/stats` returns JSON:**
- `wpoint_balance`, `wcoin_balance`, `lifetime_diamond_mined`, `unclaimed_diamond`
- `rank`, `rate_per_hour`, `server_online`, `mined_today`, `max_daily_cap`

**`/dashboard/checkin-status` returns JSON:**
- `checked_in_today`, `streak`, `day_in_cycle`, `reward_amount`, `streak_bonus`
- `wcoin_cycle`, `wcoin_day`, `checkin_boost_active`, `checkin_boost_expires_at`

### 2.6 Models (30 models)

Key models:
- `User` — portal_uid, username, wcoin, wpoint, tier, checkin_boost
- `Server` — id (manual), name, host, port, db_name, db_connection_name, status, visible
- `DiamondWallet` — user_id, lifetime_mined, ascension_level, diamond_blocks, max_active_boosts
- `DiamondMachine` — user_id, level, last_claim_at, slot
- `DiamondBoost` — user_id, type, multiplier, expires_at
- `Giftcode` — code, type, rewards, max_uses, used_count, expires_at
- `PShopOrder` — user_id, item_id, amount, status, test_order
- `HallOfFameLegend` — user_id, season_id, rank, score
- `Season` — name, start_time, end_time, is_active
- `S1ShopItem` / `S1ShopPurchase` / `S1PlayerBoost`
- `SocialEvent` — template, metadata, is_active
- `WebWallet` — user_id, balance
- `WPointTransaction` / `WCoinTransaction` — user_id, type, amount, reference
- `SpinLog` / `FruitPurchaseLog` / `CheckinLog`
- `Announcement` / `Admin` / `GmAction`

### 2.7 Services (14 services)

| Service | Mô tả |
|---------|-------|
| `PortalAuthService` | Login, consumeToken, issueToken, spend, reward — gọi Portal API |
| `GmApiService` | Game DB operations (gmcmd, feecallback, actors) |
| `DiamondMiningService` | Mining calculation engine |
| `WCoinService` | WCoin balance management |
| `WPointService` | WPoint balance management |
| `SpinService` | Lucky spin logic |
| `DailyLoginService` | Daily login tracking |
| `PShopEventService` | PShop events/races |
| `SeasonService` | Season management |
| `TopDonateService` | Top donor rankings (Redis) |
| `S1ShopService` | S1 shop logic |
| `SocialEventService` | Social feed events |
| `NarrativeService` | Story/narrative |
| `WCoinShopService` | WCoin shop |

### 2.8 Config files

- `config/portal.php` — PORTAL_URL, PORTAL_API_URL, GAME_CODE, GAME_SECRET, API_SECRET, exchange_rate
- `config/muh5.php` — server_open, allowed_usernames, game info, website meta, facebook, portal, game_db
- `config/services.php` — greenjade_id (base_url, client_id, client_secret, redirect_uri)
- `config/economy.php` — max_diamond_per_day, wpoint_checkin_amount, wpoint_streak_bonus
- `config/pshop.php` — PShop item configs
- `config/game_items.php` — Item definitions

### 2.9 Database (36 migrations)

Key tables:
- `users` — portal_uid, username, password, name, email, tier, wcoin, wpoint, checkin_boost_expires_at
- `servers` — id, name, host, port, db_name, db_connection_name, status, visible, opened_at
- `diamond_wallets` — user_id, lifetime_mined, ascension_level, diamond_blocks, max_active_boosts
- `diamond_machines` — user_id, level, last_claim_at, slot
- `diamond_claim_logs`, `diamond_upgrades`, `diamond_daily_logs`, `diamond_boosts`
- `diamond_transactions` — user_id, type, amount, reference, status
- `wpoint_transactions`, `wcoin_transactions` — user_id, type, amount, reference
- `web_wallets` — user_id, balance
- `giftcodes`, `giftcode_redemptions`
- `pshop_orders` — user_id, item_id, amount, status, test_order, amount_spent
- `hall_of_fame_legends` — user_id, season_id, rank, score
- `seasons`, `top_spend_logs`, `p_shop_events`
- `s1_shop_items`, `s1_shop_purchases`, `s1_player_boosts`
- `social_events` — template, metadata, is_active
- `spin_logs`, `fruit_purchase_logs`, `checkin_logs`
- `announcements`, `admins`, `gm_actions`
- `failed_transactions`

### 2.10 Frontend — hai legacy

**muh5.ccgame:** TypeScript SPA (không Nuxt) render trong `app-shell.blade.php`:
- `resources/js/app-shell/boot.ts` — bootstrap từ data attributes
- `resources/js/app-shell/router.ts` — client-side routing
- `resources/js/app-shell/pages/` — mỗi page render HTML string + mount()
- `resources/js/public/home.ts` — public home page (login card)

**ccgame-muh5 reference/legacy:** Nuxt 4 app dùng làm FE tham khảo cho `/play` shell + SDK overlay. **Không deploy.**

---

## 3. Stack: Laravel 12 lean + Blade + Vite SDK

- Không FlightPHP, không Slim 4, không PHP custom
- Chỉ `laravel/framework` + `laravel/tinker` (production deps)
- Không Filament, Jetstream, Sanctum, TypeScript app-shell
- **No Nuxt production.** Nuxt legacy/reference (`reference/legacy`) chỉ dùng để inspect logic cũ, không deploy.
- **Production frontend là Blade + Vite SDK** inside Laravel repo.
- Legacy `muh5.ccgame` là reference, code mới lean hơn (bỏ abstraction thừa)

### 3.1 Isolation rules

- **Mỗi game isolated** — mỗi game có repo, VPS, DB/runtime riêng.
- **`ccgame-muh5` không share runtime** với GreenJade main portal, `ccgame-web`, hay game khác.
- **Không deploy chung** — không chạy MUH5 backend trong cùng process/vm với app khác.

---

## 4. Hướng dẫn craft PHP

### 4.1 Style

- `declare(strict_types=1)` ở mọi file PHP
- PHP return types, parameter types rõ ràng
- Controller gọi Service, Service không gọi Controller
- Patch nhỏ, đúng chỗ, không rewrite nguyên file
- Không thêm abstraction khi chưa có pain thật

### 4.2 Database

- App DB: Eloquent ORM + Laravel migrations (giữ nguyên convention legacy)
- Game DB: `DB::connection(...)->table(...)`, không Eloquent model
- Collation: `utf8mb4_unicode_ci` (không `utf8mb4_0900_ai_ci`)
- Không chạy migrate tự động trên production — luôn `--pretend` trước

### 4.3 Auth

- GreenJade ID OAuth (PKCE S256) là primary auth
- Session-based (Laravel session), không JWT, không Sanctum
- User mapping: `portal_uid` là key chính
- Không trust client-side state cho account/payment logic

### 4.4 Game Bridge

- Signed launch token: HMAC-SHA256
- Game DB: `gmcmd` table cho commands, `feecallback` cho nạp
- Account provisioning: `globaldata_bt.global_user`, `{db_name}.globaluser`

### 4.5 Routing

- Giữ URI + method + behavior như legacy (`routes/web.php`)
- Cho phép tách thành nhiều file nhỏ hơn (VD: `routes/auth.php`, `routes/game.php`, `routes/dashboard.php`)
- Auth-protected routes dùng `auth` middleware (session-based)
- Game/public routes (play, game-launcher, status) không require auth
- `throttle` middleware trên payment/wallet/claim endpoints

### 4.6 Safety

- Không chạy `migrate:fresh`, reset, import, restore
- Không flush Redis
- Không touch game server Java/Cross/S1/S2 processes
- Không paste secrets vào chat/commits/docs
- Không thay đổi payment/wallet/shop/economy từ UI task
- Luôn dùng `--pretend` trước khi migrate production

---

## 5. Quy trình port (theo phase)

### Phase 0 — Setup skeleton
1. Scaffold Laravel vào thư mục tạm, copy file cần vào `ccgame-muh5`
2. `composer install` với `laravel/framework` + `tinker`
3. Xóa Filament/Jetstream/Sanctum khỏi composer.json
4. Giữ nguyên `.git/`, `AGENTS.md`, `CODEX.md`, `opencode.json`

### Phase 1 — Core data layer
1. Copy migrations từ legacy (36 files) → sửa namespace
2. Copy config files (portal, muh5, economy, game_items, services, pshop)
3. Thêm game DB connections vào `config/database.php`
4. Kiểm tra collation, dry-run migrate (`--pretend`)

### Phase 2 — Models + Services
1. Copy 30 Eloquent models → sửa namespace
2. Copy 14 services → sửa use/namespace
3. Copy helper functions (`app/Support/gm.php`)

### Phase 3 — Auth + Middleware
1. Port GreenJadeAuthController (PKCE OAuth flow)
2. Thiết lập routes auth (login/register/callback/logout)
3. Middleware: auth, admin-only, throttle

### Phase 4 — Game Bridge
1. Port PlayController (launcher, game entry, launch token)
2. Port GmApiService (game DB operations)

### Phase 5 — Dashboard + Features
Port từng module theo thứ tự ưu tiên:
1. Dashboard stats + checkin-status
2. Mining (DiamondGeneratorController)
3. Lucky Spin
4. PShop
5. S1Shop
6. Giftcode
7. Hall of Fame
8. Announcement
9. Transaction History
10. Player Tool (restricted)

### Phase 6 — Testing
1. Thiết lập Pest
2. Feature tests cho từng module
3. Auth flow test (login → callback → dashboard → logout)
4. Wallet/payment test với throttle

### Phase 7 — Deploy
1. Setup `.env` production (không copy từ local)
2. `php artisan config:cache`
3. `php artisan route:cache`
4. Point web server → `public/`

---

## 5. Tham khảo

- Legacy Laravel (đọc reference, không sửa): `D:\10_Projects\CCGame\muh5.ccgame`
- Nuxt reference (tham khảo logic, không deploy): `D:\10_Projects\CCGame\ccgame-muh5\reference\legacy`
- CCGame Web (guest launcher / AI MC): `D:\10_Projects\CCGame\ccgame-web`
