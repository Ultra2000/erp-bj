<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\SaleItem;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchasedProductsRelationManager extends RelationManager
{
    // Relation requise par Filament ; la requête du tableau est surchargée
    // ci-dessous pour agréger les lignes de vente par produit.
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Articles achetés';

    protected static ?string $icon = 'heroicon-o-shopping-bag';

    public function isReadOnly(): bool
    {
        return true;
    }

    /**
     * Produits achetés par le client, agrégés (quantité, montant, dernier achat).
     */
    protected function getTableQuery(): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.customer_id', $this->getOwnerRecord()->getKey())
            ->where('sales.status', 'completed')
            ->where(fn ($q) => $q->whereNull('sales.type')->orWhere('sales.type', '!=', 'credit_note'))
            ->selectRaw(
                'MIN(sale_items.id) as id, sale_items.product_id, products.name as product_name, '
                . 'SUM(sale_items.quantity) as qty, SUM(sale_items.total_price) as montant, MAX(sales.created_at) as dernier'
            )
            ->groupBy('sale_items.product_id', 'products.name');
    }

    public function table(Table $table): Table
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Article')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Quantité achetée')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ')),
                Tables\Columns\TextColumn::make('montant')
                    ->label('Montant total')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' ' . $currency),
                Tables\Columns\TextColumn::make('dernier')
                    ->label('Dernier achat')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('qty', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('periode')
                    ->label('Période')
                    ->options([
                        'week' => 'Cette semaine',
                        'month' => 'Ce mois',
                        'year' => 'Cette année',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = match ($data['value'] ?? null) {
                            'week' => now()->startOfWeek(),
                            'month' => now()->startOfMonth(),
                            'year' => now()->startOfYear(),
                            default => null,
                        };

                        return $from
                            ? $query->where('sales.created_at', '>=', $from)
                            : $query;
                    }),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun achat')
            ->emptyStateDescription('Ce client n\'a pas encore acheté d\'article.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
