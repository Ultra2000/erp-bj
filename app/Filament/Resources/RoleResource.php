<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Traits\RestrictedForCashier;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    use RestrictedForCashier;
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Rôles';
    protected static ?string $modelLabel = 'Rôle';
    protected static ?string $pluralModelLabel = 'Rôles';

    // Access control handled by RestrictedForCashier trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du rôle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du rôle')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('Identifiant')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
                            })
                            ->helperText('Identifiant unique pour ce rôle'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_default')
                            ->label('Rôle par défaut')
                            ->helperText('Les nouveaux utilisateurs invités recevront ce rôle par défaut')
                            ->default(false),
                    ])
                    ->columns(2),

                ...static::buildPermissionSections(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('slug')
                    ->label('Identifiant')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Par défaut')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Rôle par défaut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record) {
                        if ($record->slug === 'admin') {
                            throw new \Exception('Le rôle Admin ne peut pas être supprimé.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->slug === 'admin') {
                                    throw new \Exception('Le rôle Admin ne peut pas être supprimé.');
                                }
                            }
                        }),
                ]),
            ]);
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['permissions', 'users']);
    }

    protected static function buildPermissionSections(): array
    {
        $modules = Permission::modules();
        $groups = Permission::moduleGroups();
        $allPermissions = Permission::all();

        $sections = [];

        foreach ($groups as $groupLabel => $groupModules) {
            $checkboxes = [];

            foreach ($groupModules as $module) {
                $moduleName = $modules[$module] ?? $module;
                $modulePermissions = $allPermissions->where('module', $module);

                if ($modulePermissions->isEmpty()) {
                    continue;
                }

                $options = $modulePermissions->pluck('name', 'id')->toArray();
                $descriptions = $modulePermissions->pluck('description', 'id')->toArray();

                $checkboxes[] = Forms\Components\CheckboxList::make("perm_{$module}")
                    ->label($moduleName)
                    ->relationship('permissions', 'name', fn ($query) => $query->where('module', $module))
                    ->options($options)
                    ->descriptions($descriptions)
                    ->columns(2)
                    ->bulkToggleable();
            }

            if (!empty($checkboxes)) {
                $sections[] = Forms\Components\Section::make($groupLabel)
                    ->schema($checkboxes)
                    ->collapsible()
                    ->icon(match ($groupLabel) {
                        'Ventes & Clients' => 'heroicon-o-shopping-cart',
                        'Stocks & Achats' => 'heroicon-o-cube',
                        'Comptabilité & Finance' => 'heroicon-o-calculator',
                        'Ressources Humaines' => 'heroicon-o-user-group',
                        'Administration' => 'heroicon-o-cog-6-tooth',
                        default => 'heroicon-o-squares-2x2',
                    });
            }
        }

        return $sections;
    }
}
