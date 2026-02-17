<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\TutorialVideoResource\Pages;
use App\Models\TutorialVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class TutorialVideoResource extends Resource
{
    protected static ?string $model = TutorialVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'Contenu';
    protected static ?int $navigationSort = 50;
    protected static ?string $navigationLabel = 'Vidéos Tutoriels';
    protected static ?string $modelLabel = 'Vidéo tutoriel';
    protected static ?string $pluralModelLabel = 'Vidéos tutoriels';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la vidéo')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre de la vidéo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Comment créer une facture de vente'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brève description du contenu de la vidéo'),
                        Forms\Components\Select::make('section')
                            ->label('Section du guide')
                            ->options(TutorialVideo::getSections())
                            ->required()
                            ->searchable(),
                    ])->columns(1),

                Forms\Components\Section::make('Vidéo YouTube')
                    ->description('Collez le lien YouTube de votre vidéo tutoriel')
                    ->schema([
                        Forms\Components\TextInput::make('video_url')
                            ->label('URL de la vidéo YouTube')
                            ->required()
                            ->url()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Formats acceptés : youtube.com/watch?v=..., youtu.be/..., youtube.com/embed/...')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $videoId = TutorialVideo::extractYoutubeId($state);
                                if ($videoId) {
                                    $set('video_id', $videoId);
                                    $set('thumbnail_url', "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg");
                                }
                            }),
                        Forms\Components\Hidden::make('video_id'),
                        Forms\Components\Placeholder::make('preview')
                            ->label('Aperçu')
                            ->content(function (Forms\Get $get) {
                                $videoId = $get('video_id') ?: TutorialVideo::extractYoutubeId($get('video_url'));
                                if (!$videoId) {
                                    return new HtmlString('<span class="text-gray-400 text-sm">Collez un lien YouTube pour voir l\'aperçu</span>');
                                }
                                return new HtmlString(
                                    '<div style="max-width:480px;"><div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px;">'
                                    . '<iframe src="https://www.youtube.com/embed/' . $videoId . '" '
                                    . 'style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" '
                                    . 'allowfullscreen></iframe></div></div>'
                                );
                            }),
                    ]),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Durée (en secondes)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('Ex: 180 pour 3 minutes')
                            ->helperText('Optionnel — affiché comme "3:00" dans le guide'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Les vidéos avec un ordre plus petit apparaissent en premier'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Vidéo active')
                            ->default(true)
                            ->helperText('Décocher pour masquer la vidéo du guide sans la supprimer'),
                        Forms\Components\TextInput::make('thumbnail_url')
                            ->label('Miniature personnalisée (URL)')
                            ->url()
                            ->placeholder('Laissez vide pour utiliser la miniature YouTube')
                            ->helperText('Optionnel — par défaut la miniature YouTube est utilisée'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_display')
                    ->label('')
                    ->getStateUsing(fn (TutorialVideo $record) => $record->thumbnail)
                    ->width(80)
                    ->height(45),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->weight('bold')
                    ->limit(50),
                Tables\Columns\TextColumn::make('section')
                    ->label('Section')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => TutorialVideo::getSections()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'overview' => 'gray',
                        'sales' => 'primary',
                        'pos' => 'success',
                        'stock' => 'warning',
                        'accounting' => 'danger',
                        'hr' => 'info',
                        'invoicing' => 'gray',
                        'admin' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Durée')
                    ->getStateUsing(fn (TutorialVideo $record) => $record->formatted_duration ?? '-'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ajoutée le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('section')
                    ->label('Section')
                    ->options(TutorialVideo::getSections()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->trueLabel('Actives')
                    ->falseLabel('Masquées'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTutorialVideos::route('/'),
            'create' => Pages\CreateTutorialVideo::route('/create'),
            'edit' => Pages\EditTutorialVideo::route('/{record}/edit'),
        ];
    }
}
