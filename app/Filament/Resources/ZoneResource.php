<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Models\Zone;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Zones';

    protected static ?string $modelLabel = 'Zone';

    protected static ?string $pluralModelLabel = 'Zones';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return Tontine::canAdminister();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('customers')
            ->withCount('tontiniers');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Zone')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom de la zone')->required()->maxLength(255)
                        ->placeholder('Ex : Cotonou - Akpakpa'),
                    Forms\Components\TextInput::make('city')
                        ->label('Ville / Commune')->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')->rows(2)->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Zone active')->default(true),
                ])->columns(2),
            Forms\Components\Section::make('Tontiniers affectés')
                ->description('Les agents de collecte qui couvrent cette zone (rôle Tontinier).')
                ->schema([
                    Forms\Components\Select::make('tontiniers')
                        ->label('Tontiniers')
                        ->multiple()
                        ->relationship(
                            'tontiniers',
                            'name',
                            fn (Builder $query) => $query->whereHas('roles', fn (Builder $r) => $r
                                ->where('roles.slug', 'tontinier')
                                ->where('model_has_roles.company_id', Filament::getTenant()?->id))
                        )
                        ->preload()
                        ->searchable()
                        ->helperText('Seuls les utilisateurs ayant le rôle « Tontinier » apparaissent ici.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Zone')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('tontiniers.name')
                    ->label('Tontiniers')->badge()->color('info')->placeholder('Aucun'),
                Tables\Columns\TextColumn::make('customers_count')
                    ->label('Épargnants')->alignCenter()->badge()->color('success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')->boolean(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }
}
