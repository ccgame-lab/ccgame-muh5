# AGENTS.md - MUH5

## Purpose

MUH5 is a Nuxt SSR game shell connected to CCGame.

CCGame owns:
- login/auth
- account identity
- session identity
- launch routing into games

MUH5 owns:
- game shell
- in-game SDK launcher
- SDK panel
- wallet display surface
- giftcode UI
- shop/catalog UI
- event/checkin/lucky-spin status UI

Do not move MUH5 business panels into CCGame.

## Stack

- Nuxt SSR
- TypeScript
- Bun
- Nuxt UI
- Tailwind CSS
- Drizzle later, not in Task 01

## Current phase

Task 01 is shell only.

Allowed:
- SSR pages
- route map
- mock/read-only data
- local MUH5 SDK launcher
- local MUH5 SDK panel
- health/bootstrap mock APIs

Forbidden:
- DB mutation
- payment
- topup settlement
- real giftcode redeem
- shop purchase
- lucky spin action
- reward grant
- Diamond/Monument logic
- legacy PHP write-path porting
- reusable SDK package extraction

## Static Client Boundaries

- Nuxt SSR shell serves the legacy Egret H5 static client under `public/muh5-client/`.
- Legacy PHP files, backend folders, `.env` files, server APIs, or `storage` cache logs MUST NOT be copied or loaded under `public/`.
- S1 business panels stay mock/read-only/locked. The Giftcode, PShop, LuckySpin, top-up/payment, game_mail, and Monument/Mining write paths are strictly sealed.
- Static assets are sourced from `reference/legacy/game` or remote CDNs. Any new asset copy must only import clean client-side static resources (images, audio, css, js).
- **Identity & Authentication**: Player identity must come from a verified, cryptographically signed CCGame `launch` token (HMAC-SHA256). Raw, unsigned query parameters (`user`, `userId`) are considered untrusted legacy/compatibility options and must not be used for authenticated operations.
- **WebSocket Server Address Normalization**: The legacy Egret H5 client expects `srvaddr` to be a pure host only (no protocols like `wss://` or `https://`, no slashes `/`, and no trailing path like `/s1/`). Any input `srvaddr` from verified CCGame launch tokens must be normalized (stripping protocols and paths) before passing it to the static iframe via query parameters.
- **Iframe & Framing Security Policy**: MUH5 is designed to be embedded in an iframe on the parent CCGame portal. The `/play` route must run under Server-Side Rendering (SSR) without crashing (ensuring no `500` errors on player metadata lookups). To allow framing while maintaining top-level security, `X-Frame-Options` must not be set to `DENY` or `SAMEORIGIN` on the main playable routes. Instead, a strict `Content-Security-Policy: frame-ancestors 'self' https://ccgame.org https://www.ccgame.org` must be used to restrict framing permissions exclusively to CCGame portal domains.

## Boundaries

Vue components must not decide:
- wallet balance
- paid state
- entitlement
- reward
- price
- settlement

Server/API/services own verification later.

Client JS may handle:
- iframe/game events
- SDK launcher drag/open state
- loading/error states
- visual effects

Do not query DB from Vue components.

Do not add Laravel, Adonis, Hono, or Elysia.

## UX Boundary

MUH5 shell must not become a portal/dashboard.

Business surfaces must stay inside the MUH5 in-game SDK panel.

Do not expose these as top-level navigation or standalone public pages in Task 01:
- Wallet
- Giftcode
- Shop
- Events
- Leaderboard

Allowed public routes in Task 01:
- /
- /play

Allowed API routes in Task 01:
- /api/health
- /api/bootstrap
- /api/wallet (mock/read-only)
- /api/leaderboard (mock/read-only)

The home page should only provide a minimal launch entry into /play.

## File conventions

Use Nuxt 4 structure:

- app/components
- app/pages
- app/assets
- server/api
- server/services
- server/utils
- config
- types

## Execution rules

Before editing:
- inspect existing files
- keep the patch small
- do not refactor unrelated code

After editing:
- run lint/typecheck if available
- stop on first real error
- report exact command output

Do not run migrations, import, restore, fresh, reset, or mutate production DB without explicit approval.