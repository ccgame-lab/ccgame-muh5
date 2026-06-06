<?php

declare(strict_types=1);

namespace App\Filament\Resources\Seasons;

use App\Filament\Resources\Seasons\Pages\CreateSeason;
use App\Filament\Resources\Seasons\Pages\EditSeason;
use App\Filament\Resources\Seasons\Pages\ListSeasons;
use App\Filament\Resources\Seasons\Pages\ViewSeason;
use App\Models\Season;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;

    protected static bool $shouldSkipAuthorization = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop & Mùa';

    protected static ?string $navigationLabel = 'Mùa (Season)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên mùa')
                    ->required(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Pending',
                        'active'  => 'Active',
                        'ended'   => 'Ended',
                    ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('start_time')
                    ->label('Bắt đầu')
                    ->required(),
                DateTimePicker::make('end_time')
                    ->label('Kết thúc')
                    ->required(),
                KeyValue::make('rewards_config')
                    ->label('Cấu hình phần thưởng')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Tên mùa')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'  => 'success',
                        'ended'   => 'info',
                        default   => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Bắt đầu')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Kết thúc')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Pending',
                        'active'  => 'Active',
                        'ended'   => 'Ended',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSeasons::route('/'),
            'create' => CreateSeason::route('/create'),
            'view'   => ViewSeason::route('/{record}'),
            'edit'   => EditSeason::route('/{record}/edit'),
        ];
    }
}
