<?php

declare(strict_types=1);

namespace App\Filament\Resources\Giftcodes;

use App\Filament\Resources\Giftcodes\Pages\CreateGiftcode;
use App\Filament\Resources\Giftcodes\Pages\EditGiftcode;
use App\Filament\Resources\Giftcodes\Pages\ListGiftcodes;
use App\Filament\Resources\Giftcodes\Pages\ViewGiftcode;
use App\Models\Giftcode;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GiftcodeResource extends Resource
{
    protected static ?string $model = Giftcode::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Game';

    protected static ?string $navigationLabel = 'Giftcode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('server_id')
                    ->numeric(),
                TextInput::make('limit_usage')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('used_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('reward_type')
                    ->options([
                        'portal_credit' => 'Nạp xu Portal',
                        'game_mail' => 'Gửi thư ingame',
                    ])
                    ->default('portal_credit')
                    ->required()
                    ->live(),

                Select::make('reward_currency')
                    ->label('Loại tiền thưởng')
                    ->options([
                        'points' => 'POINT',
                    ])
                    ->default('points')
                    ->visible(fn (Get $get): bool => $get('reward_type') === 'portal_credit'),
                TextInput::make('reward_amount')
                    ->label('Số lượng')
                    ->numeric()
                    ->default(0)
                    ->visible(fn (Get $get): bool => $get('reward_type') === 'portal_credit'),

                TextInput::make('mail_title')
                    ->label('Tiêu đề thư')
                    ->maxLength(50)
                    ->default('Giftcode Reward')
                    ->visible(fn (Get $get): bool => $get('reward_type') === 'game_mail'),
                Textarea::make('mail_body')
                    ->label('Nội dung thư')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('reward_type') === 'game_mail'),
                Repeater::make('items_list')
                    ->label('Vật phẩm đính kèm')
                    ->schema([
                        Select::make('id')
                            ->label('Vật phẩm')
                            ->options(self::getItemsOptions())
                            ->searchable()
                            ->required(),
                        TextInput::make('count')
                            ->label('Số lượng')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->maxItems(5)
                    ->visible(fn (Get $get): bool => $get('reward_type') === 'game_mail'),

                DateTimePicker::make('expires_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('server_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('limit_usage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('used_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reward_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'portal_credit' => 'success',
                        'game_mail' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    private static function getItemsOptions(): array
    {
        $items = config('game_items', []);

        return collect($items)
            ->mapWithKeys(fn (array $item, string $key): array => [$key => "{$key} - {$item['name']}"])
            ->toArray();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGiftcodes::route('/'),
            'create' => CreateGiftcode::route('/create'),
            'view' => ViewGiftcode::route('/{record}'),
            'edit' => EditGiftcode::route('/{record}/edit'),
        ];
    }
}
