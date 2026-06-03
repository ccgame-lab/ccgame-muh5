# Agent Handoff Guide — ccgame-muh5

## Khi nào cần handoff

Handoff cần thiết khi:
- Công việc kéo dài >1 session (hết context)
- Cần chuyển task sang AI agent khác (DeepSeek ↔ Claude)
- Cần review lại architectural decision sau nhiều ngày

## Handoff Prompt Template

```markdown
## Context

Project: ccgame-muh5 (Laravel 12 + Filament)
Recent work: [tóm tắt 1-2 câu]
Current branch: master (last commit: HASH)

## State

- Filament resources: 7 resources, đã inline schemas/tables (trừ GmActions)
- Users/Actions/: 5 static factory files, untouched
- Models: User, Changelog, Giftcode, GmAction, SdkDailyCheckin, SdkFeature, Server
- Auth: GreenJade OAuth PKCE, guard `admin`
- DB app: Eloquent ORM, DB game: DB::connection(...)->table(...)

## Files changed recently

[liệt kê file và status — M: modified, D: deleted]

## Task

[mô tả task cụ thể]
```

## Critical Context cho Agent

### Kiến trúc Filament

```php
// Inline pattern — mọi resource trừ GmActions
public static function form(Schema $schema): Schema
{
    return $schema->components([...]);
}

// Static factory pattern cho Actions
public static function make(): Action { ... }
// Dùng: GmLookupAction::make() trong ->recordActions([...])
```

### Panel config

- Single panel `admin`, path `/admin`, guard `admin`
- Custom Login page: `App\Filament\Auth\Login`
- Custom page: `GMOperations` (HasForms + blade view)
- Discovery: discoverResources + discoverPages + discoverWidgets
- Widgets có thể nằm trong subdirectory (VD: Widgets/Users/)

### Safety Boundaries

```
✅ Always safe:
  - Route/controller port từ legacy
  - Migration port (dry-run --pretend trước)
  - Service refactor (pure logic)
  - Testing with in-memory SQLite

⚠️ Extra care:
  - GreenJade OAuth callback / logout
  - Game bridge (GmApiService, launch tokens)
  - Wallet/wcoin/wpoint transaction logic
  - Player tool (charge / send-mail — restricted)

❌ Never:
  - migrate:fresh, db:wipe, reset, import, restore
  - Touching production DB or Redis
  - Reading or committing .env / secrets
  - Touching game server processes
  - Changing payment/wallet/economy from a UI task
  - Force push
```

### Verify Commands

```bash
php artisan route:list --path=admin  # route sanity (~23 routes)
php artisan migrate --pretend        # SQL preview
composer run pint:dirty              # style check
php artisan test --compact           # test suite
```

## Khi prompt DeepSeek

1. **Luôn kèm `cd /www/wwwroot/ccgame/ccgame-muh5`** đầu prompt
2. **Specify read-only hay write** — tránh agent tự ý sửa
3. **Luôn verify bằng route:list / test** sau mỗi task
4. **Nếu task liên quan game DB** — nhắc agent dùng `DB::connection(...)` không dùng Eloquent
5. **Nếu task liên quan Filament** — tham chiếu `docs/filament-conventions.md`

## Khi Claude Code

- Claude có context lớn hơn, có thể load toàn bộ file cần sửa
- Claude thường tự ý refactor — cần nhấn mạnh "KHÔNG đổi logic, KHÔNG đổi UI"
- Claude hiểu Filament 3.x tốt, có thể gợi ý pattern mới
