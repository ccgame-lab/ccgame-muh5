<?php

namespace App\Filament\Resources\SdkFeatures\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SdkFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->label('Feature Key')
                    ->options([
                        'wallet'   => 'wallet — Nạp / Ví',
                        'giftcode' => 'giftcode — Giftcode',
                        'shop'     => 'shop — Cửa hàng',
                        'spin'     => 'spin — Vòng quay',
                        'mining'   => 'mining — Đào KC',
                        'support'  => 'support — Hỗ trợ',
                    ])
                    ->required(),
                TextInput::make('label')
                    ->label('Tên hiển thị (title)')
                    ->helperText('Hiển thị trên button trong SDK overlay')
                    ->required(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active'      => '✅ Active — có thể click',
                        'soon'        => '🕐 Soon — chưa mở',
                        'maintenance' => '🔧 Maintenance — bảo trì',
                        'hidden'      => '👁 Hidden — ẩn khỏi grid',
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
}
