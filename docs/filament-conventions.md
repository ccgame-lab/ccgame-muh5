# Filament Conventions — ccgame-muh5

## Inline vs Tách file

| Tiêu chí | Inline trong Resource | Tách file riêng |
|---|---|---|
| Form/Table/Infolist ngắn (<30 dòng) | ✅ Bắt buộc | ❌ |
| Form/Table/Infolist dài (>50 dòng) | Có thể | ✅ Nếu phức tạp |
| Có callback/logic riêng (getItemsOptions, etc.) | ✅ `private static` trong Resource | ❌ |

**Quy tắc chung:** Mặc định inline. Chỉ tách file nếu `configure()` > 50 dòng hoặc có logic nghiệp vụ riêng biệt cần test độc lập.

### Resources đã inline (mẫu tham chiếu)

Tất cả resource hiện tại đều inline, trừ `GmActions`:
- `GmActions/Schemas/GmActionForm.php` — form 7 fields
- `GmActions/Schemas/GmActionInfolist.php` — infolist 12 entries
- `GmActions/Tables/GmActionsTable.php` — table 10 columns + 2 record actions

## Users/Actions/ — Static Factory Pattern

```php
class GmBanAction
{
    public static function make(): Action { ... }
}
```

Dùng trong `->recordActions([...])` của table:

```php
GmLookupAction::make(),
SendItemMailAction::make(),
AddPointSilentAction::make(),
GmKickAction::make(),
GmBanAction::make(),
```

**Không extends** `Filament\Actions\Action` — giữ static factory. Lý do: Filament không hỗ trợ extends Action class cho record actions một cách trực tiếp khi cần custom modal/form phức tạp.

## Cấu trúc thư mục

```
app/Filament/
├── Auth/
│   └── Login.php                           # Custom login page
├── Pages/
│   └── GMOperations.php                    # Standalone page (HasForms + blade)
├── Resources/
│   ├── Changelogs/
│   │   ├── ChangelogResource.php           # form + table inline
│   │   └── Pages/
│   ├── Giftcodes/
│   │   └── ... (Pages/ + Resource inline)
│   ├── GmActions/
│   │   ├── GmActionResource.php
│   │   ├── Pages/
│   │   ├── Schemas/                        # EXCEPTION: kept separate
│   │   └── Tables/                         # EXCEPTION: kept separate
│   ├── SdkDailyCheckins/
│   │   └── ... (Table inline, có Widgets/)
│   ├── SdkFeatures/
│   │   └── ... (inline)
│   ├── Servers/
│   │   └── ... (inline)
│   └── Users/
│       ├── UserResource.php
│       ├── Actions/                        # Static factory actions
│       └── Pages/
└── Widgets/
    ├── SdkStatsWidget.php                  # Global widget
    └── Users/                              # Subdirectory OK
        ├── GmActionLogWidget.php
        └── PointTransactionWidget.php
```

## Thêm Resource mới — Checklist

1. Tạo thư mục `app/Filament/Resources/{Name}/`
2. Tạo `{Name}Resource.php` extends `Resource`
3. Tạo `Pages/` với List + Create + Edit (Edit là optional nếu readonly)
4. Viết `form()`, `table()`, `infolist()` inline trong Resource
5. Đăng ký route trong `getPages()`
6. Kiểm tra: `php artisan route:list --path=admin`
7. Kiểm tra: `php artisan test --compact`
8. Chạy: `composer run pint:dirty`

## Naming

| What | Convention | Ví dụ |
|---|---|---|
| Resource class | PascalCase + Resource | `GiftcodeResource` |
| Page class | PascalCase + verb | `CreateGiftcode`, `ListGiftcodes` |
| Route name | snake_case verb | `/create`, `/{record}/edit` |
| Navigation label | `protected static ?string $navigationLabel` | |
| Model property | `protected static ?string $model` | `Giftcode::class` |

## Hard Constraints

- `declare(strict_types=1)` in every PHP file
- Return types + parameter types on all methods
- `BackedEnum|null` type for `$navigationIcon` using `Heroicon::*`
- Giữ `use` imports gọn, xoá unused
