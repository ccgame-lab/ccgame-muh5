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