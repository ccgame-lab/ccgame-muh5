# AGENTS.md — ccgame-muh5 Agent Rules

## Stack

- **Framework**: Laravel 12 lean (only `laravel/framework` + `laravel/tinker`)
- **Auth**: GreenJade OAuth PKCE, session-based
- **DB app**: Eloquent ORM + Laravel migrations
- **DB game**: `DB::connection(...)->table(...)` (no Eloquent for game DB)
- **Admin UI**: Filament 3.x, single panel `admin`, guard `admin`, path `/admin`
- **Frontend**: Not ported. Left to `ccgame-web` (Nuxt 4) later.
- **Not used**: FlightPHP, Slim 4, PHP custom, Filament, Jetstream, Sanctum, TypeScript app-shell

## Project docs (đọc trước khi làm task liên quan)

- `docs/filament-conventions.md` — convention Filament: inline pattern, naming, thêm resource
- `docs/deploy-checklist.md` — checklist deploy, verify, rollback
- `docs/agent-handoff.md` — handoff guide, prompt template, safety boundaries

## Setup commands

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Code style

- `declare(strict_types=1)` in every PHP file
- PHP return types + parameter types on all methods
- No over-engineering: flat services, minimal abstraction
- Follow existing Laravel controller/service patterns

## Filament conventions (tóm tắt)

- **Inline mặc định**: form/table/infolist viết trong Resource, KHÔNG tách Schemas/Tables riêng (trừ GmActions)
- **Users/Actions/**: static factory `make(): Action`, không extends class
- **Widgets**: có thể nằm trong subdirectory (VD: `Widgets/Users/`)
- Chi tiết: xem `docs/filament-conventions.md`

## Hard boundaries

- No `migrate:fresh`, `db:wipe`, reset, import, restore
- No touching production DB or Redis
- No reading or committing `.env` / secrets
- No touching game server processes
- No changing payment/wallet/economy from a UI task
- No pasting secrets into chat, commits, or docs
- Deploy using `./deploy.ps1` or `bash deploy.sh`. These scripts handle the remote sync and explicitly use `--pretend` for migrations. Scripts must NEVER run real migrate.

## Safe work

Usually safe:
- Route/controller port from legacy
- Migration port (dry-run first)
- Service refactor (pure logic)
- Testing with in-memory SQLite

Needs extra care:
- GreenJade OAuth callback and logout
- Game bridge (GmApiService, launch tokens)
- Wallet/wcoin/wpoint transaction logic
- Player tool (charge/send-mail — restricted)

## Verification

- `php artisan route:list --path=admin` — route sanity (expect ~23 routes)
- `php artisan migrate --pretend` — review SQL before running
- `composer run pint:dirty` — style check
- `php artisan test --compact` — test suite
