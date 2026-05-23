<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Articles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                $price = $product->sale_price_ht ?? $product->price;
                                $set('unit_price', $price);
                                $set('vat_rate', $product->vat_rate_sale ?? 18);
                                $set('tax_specific_amount', $product->tax_specific_amount ?? 0);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(0.001)
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => $this->updateTotalPrice($set, $get)),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Prix unitaire HT')
                    ->required()
                    ->numeric()
                    ->prefix(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => $this->updateTotalPrice($set, $get)),
                Forms\Components\TextInput::make('vat_rate')
                    ->label('TVA (%)')
                    ->numeric()
                    ->default(18)
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => $this->updateTotalPrice($set, $get)),
                Forms\Components\Hidden::make('tax_specific_amount')
                    ->default(0),
                Forms\Components\TextInput::make('total_price')
                    ->label('Prix total TTC')
                    ->numeric()
                    ->prefix(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Prix unitaire HT')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency),
                Tables\Columns\TextColumn::make('vat_rate')
                    ->label('TVA')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total TTC')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un article'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }

    protected function updateTotalPrice(Forms\Set $set, Forms\Get $get): void
    {
        $quantity = floatval($get('quantity'));
        $unitPrice = floatval($get('unit_price'));
        $vatRate = floatval($get('vat_rate') ?? 18);
        $taxSpecific = floatval($get('tax_specific_amount') ?? 0);

        if ($quantity && $unitPrice) {
            $totalHt = $quantity * $unitPrice;
            $vat = round($totalHt * ($vatRate / 100), 2);
            $taxSpecTotal = $taxSpecific > 0 ? round($taxSpecific * $quantity, 2) : 0;
            $set('total_price', round($totalHt + $vat + $taxSpecTotal));
        }
    }
}
