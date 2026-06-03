<?php

namespace App\Filament\Resources\SdkFeatures;

use App\Filament\Resources\SdkFeatures\Pages\CreateSdkFeature;
use App\Filament\Resources\SdkFeatures\Pages\EditSdkFeature;
use App\Filament\Resources\SdkFeatures\Pages\ListSdkFeatures;
use App\Models\SdkFeature;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SdkFeatureResource extends Resource
{
    protected static ?string $model = SdkFeature::class;

    protected static string|\UnitEnum|null $navigationGroup = 'SDK';

    protected static ?string $navigationLabel = 'Tính năng SDK';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->label('Feature Key')
                    ->options([
                        'wallet' => 'wallet — Nạp / Ví',
                        'giftcode' => 'giftcode — Giftcode',
                        'shop' => 'shop — Cửa hàng',
                        'spin' => 'spin — Vòng quay',
                        'mining' => 'mining — Đào KC',
                        'support' => 'support — Hỗ trợ',
                    ])
                    ->required(),
                TextInput::make('label')
                    ->label('Tên hiển thị (title)')
                    ->helperText('Hiển thị trên button trong SDK overlay')
                    ->required(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => '✅ Active — có thể click',
                        'soon' => '🕐 Soon — chưa mở',
                        'maintenance' => '🔧 Maintenance — bảo trì',
                        'hidden' => '👁 Hidden — ẩn khỏi grid',
                    ])
                    ->required(),
                TextInput::make('url')
                    ->label('URL (để trống nếu dùng mặc định)')
                    ->nullable(),
                TextInput::make('note')
                    ->label('Note / Sublabel')
                    ->helperText('Dòng chữ nhỏ bên dưới tên (VD: Sắp mở, Đang bảo trì)')
                    ->nullable(),
                Toggle::make('is_active')
                    ->label('Hiển thị trong SDK')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'soon' => 'warning',
                        'maintenance' => 'danger',
                        'hidden' => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdkFeatures::route('/'),
            'create' => CreateSdkFeature::route('/create'),
            'edit' => EditSdkFeature::route('/{record}/edit'),
        ];
    }
}
