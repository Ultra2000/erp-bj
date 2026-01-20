<?php

namespace App\Filament\Superadmin\Widgets;

use App\Filament\Superadmin\Pages\FeaturesManagement;
use App\Models\Company;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;

class FeaturesOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $globalFeatures = Cache::get('global_features_settings', []);
        $categories = FeaturesManagement::getFeatureCategories();
        $features = FeaturesManagement::getAvailableFeatures();

        // Grouper les fonctionnalités par catégorie
        $data = [];
        foreach ($categories as $categoryKey => $category) {
            $categoryFeatures = collect($features)->filter(fn($f) => $f['category'] === $categoryKey);
            $enabledCount = $categoryFeatures->filter(fn($f, $key) => $globalFeatures[$key] ?? true)->count();
            
            $data[] = [
                'category' => $category['label'],
                'icon' => $category['icon'],
                'total' => $categoryFeatures->count(),
                'enabled' => $enabledCount,
                'disabled' => $categoryFeatures->count() - $enabledCount,
            ];
        }

        return $table
            ->heading('Aperçu des Fonctionnalités par Catégorie')
            ->query(
                Company::query()->limit(0) // Hack pour avoir une table vide qu'on remplit manuellement
            )
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->badge(),
                Tables\Columns\TextColumn::make('enabled')
                    ->label('Activées')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('disabled')
                    ->label('Désactivées')
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Configuration des fonctionnalités')
            ->emptyStateDescription('Utilisez la page de gestion des fonctionnalités pour configurer les modules.')
            ->emptyStateIcon('heroicon-o-puzzle-piece')
            ->emptyStateActions([
                Tables\Actions\Action::make('configure')
                    ->label('Configurer les fonctionnalités')
                    ->url(route('filament.superadmin.pages.features-management'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);
    }
}
