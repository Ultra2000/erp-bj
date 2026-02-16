<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Filament\Traits\HasModuleCheck;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class SaleResource extends Resource
{
    use HasModuleCheck;

    protected static ?string $model = Sale::class;
    protected static ?string $requiredModule = 'sales';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Ventes';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Ventes';
    protected static ?string $modelLabel = 'Vente';
    protected static ?string $pluralModelLabel = 'Ventes';

    /**
     * Optimisation: Eager loading des relations pour éviter N+1
     * Le scope WarehouseScope est appliqué automatiquement via le modèle Sale
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'warehouse', 'bankAccount']);
    }

    /**
     * Récupère les entrepôts accessibles pour l'utilisateur courant
     */
    protected static function getAccessibleWarehouses(): \Illuminate\Database\Eloquent\Builder
    {
        $companyId = Filament::getTenant()?->id;
        $user = auth()->user();
        
        $query = Warehouse::where('company_id', $companyId)
            ->where('is_active', true);
        
        // Si l'utilisateur est restreint à des entrepôts spécifiques
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseIds = $user->accessibleWarehouseIds();
            if (!empty($warehouseIds)) {
                $query->whereIn('id', $warehouseIds);
            } else {
                $query->whereRaw('1 = 0'); // Aucun entrepôt
            }
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        $companyId = Filament::getTenant()?->id;
        $user = auth()->user();

        return $form
            ->disabled(fn (?Sale $record) => $record?->status === 'completed')
            ->schema([
                Forms\Components\Section::make('Informations de la vente')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Numéro de facture')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'name', fn ($query) => $query->where('company_id', $companyId))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Entrepôt source')
                            ->options(fn () => static::getAccessibleWarehouses()->pluck('name', 'id'))
                            ->default(fn () => $user?->defaultWarehouse()?->id ?? Warehouse::getDefault($companyId)?->id)
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('items', [])),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'completed' => 'Terminée',
                                'cancelled' => 'Annulée',
                            ])
                            ->required()
                            ->default('pending')
                            ->live(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de paiement')
                            ->options(function () {
                                $company = Filament::getTenant();
                                // Si e-MCeF est activé, utiliser les modes de paiement compatibles
                                if ($company?->emcef_enabled) {
                                    return [
                                        'cash' => 'Espèces',
                                        'card' => 'Carte bancaire',
                                        'transfer' => 'Virement bancaire',
                                        'mobile_money' => 'Mobile Money (MTN/Moov)',
                                        'check' => 'Chèque',
                                        'credit' => 'Crédit',
                                        'other' => 'Autre',
                                    ];
                                }
                                // Modes par défaut
                                return [
                                    'cash' => 'Espèces',
                                    'card' => 'Carte bancaire',
                                    'transfer' => 'Virement bancaire',
                                    'check' => 'Chèque',
                                    'mobile_money' => 'Mobile Money',
                                    'credit' => 'Crédit',
                                    'other' => 'Autre',
                                ];
                            })
                            ->required(),
                    ])->columns(2),

                // Section e-MCeF (Bénin) - Affichée uniquement si certifiée
                Forms\Components\Section::make('Certification e-MCeF')
                    ->description('Informations de certification électronique DGI Bénin')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('emcef_status_display')
                                    ->label('Statut')
                                    ->content(fn (?Sale $record) => match ($record?->emcef_status) {
                                        'certified' => '✅ Certifiée',
                                        'submitted' => '🔄 Soumise',
                                        'pending' => '⏳ En attente',
                                        'error' => '❌ Erreur',
                                        'cancelled' => '🚫 Annulée',
                                        default => '-',
                                    }),
                                Forms\Components\Placeholder::make('emcef_nim_display')
                                    ->label('NIM')
                                    ->content(fn (?Sale $record) => $record?->emcef_nim ?? '-'),
                                Forms\Components\Placeholder::make('emcef_code_display')
                                    ->label('Code MECeF DGI')
                                    ->content(fn (?Sale $record) => $record?->emcef_code_mecef ?? '-'),
                                Forms\Components\Placeholder::make('emcef_certified_at_display')
                                    ->label('Certifiée le')
                                    ->content(fn (?Sale $record) => $record?->emcef_certified_at?->format('d/m/Y à H:i') ?? '-'),
                            ]),
                        Forms\Components\Placeholder::make('emcef_counters_display')
                            ->label('Compteurs')
                            ->content(fn (?Sale $record) => $record?->emcef_counters ?? '-')
                            ->visible(fn (?Sale $record) => !empty($record?->emcef_counters)),
                        Forms\Components\Placeholder::make('emcef_error_display')
                            ->label('Erreur')
                            ->content(fn (?Sale $record) => $record?->emcef_error)
                            ->visible(fn (?Sale $record) => $record?->emcef_status === 'error'),
                    ])
                    ->visible(function (?Sale $record) {
                        $company = Filament::getTenant();
                        return $company?->emcef_enabled && $record && $record->emcef_status !== null && $record->emcef_status !== 'pending';
                    })
                    ->collapsed(fn (?Sale $record) => $record?->emcef_status === 'certified'),

                Forms\Components\Section::make('Paramètres financiers')
                    ->schema([
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Remise globale %')
                            ->numeric()->minValue(0)->maxValue(100)->default(0)
                            ->live(onBlur: true)
                            ->helperText('Appliquée sur le total TTC'),
                        Forms\Components\Placeholder::make('total_ht_display')
                            ->label('Total HT')
                            ->content(fn (?Sale $record) => $record ? number_format($record->total_ht ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'XOF') : '-'),
                        Forms\Components\Placeholder::make('total_vat_display')
                            ->label('Total TVA')
                            ->content(fn (?Sale $record) => $record ? number_format($record->total_vat ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'XOF') : '-'),
                        Forms\Components\TextInput::make('total')
                            ->label('Total TTC')
                            ->disabled()
                            ->prefix(fn () => Filament::getTenant()->currency ?? 'XOF'),
                    ])->columns(4),

                // Section AIB (Bénin uniquement)
                Forms\Components\Section::make('AIB (Acompte sur Impôt Bénéfices)')
                    ->description('Prélèvement fiscal obligatoire au Bénin')
                    ->schema([
                        Forms\Components\Select::make('aib_rate')
                            ->label('Taux AIB')
                            ->options([
                                'A' => 'Taux A (1%) - Client avec IFU',
                                'B' => 'Taux B (5%) - Client sans IFU',
                            ])
                            ->placeholder('Aucun (exonéré)')
                            ->live()
                            ->afterStateUpdated(function ($state, ?Sale $record) {
                                if ($record) {
                                    $record->aib_rate = $state;
                                    $record->aib_amount = $record->calculateAibAmount();
                                    $record->save();
                                }
                            })
                            ->helperText(function () {
                                $company = Filament::getTenant();
                                return match ($company?->aib_mode ?? 'auto') {
                                    'auto' => '🔄 Mode automatique : calculé selon l\'IFU du client',
                                    'manual' => '✋ Mode manuel : sélectionnez le taux applicable',
                                    'disabled' => '⛔ AIB désactivé pour cette entreprise',
                                };
                            }),
                        Forms\Components\Toggle::make('aib_exempt')
                            ->label('Exonérer cette vente de l\'AIB')
                            ->live()
                            ->afterStateUpdated(function ($state, ?Sale $record) {
                                if ($record) {
                                    $record->aib_exempt = $state;
                                    if ($state) {
                                        $record->aib_rate = null;
                                        $record->aib_amount = 0;
                                    } else {
                                        $record->applyAib();
                                    }
                                    $record->save();
                                }
                            }),
                        Forms\Components\Placeholder::make('aib_amount_display')
                            ->label('Montant AIB')
                            ->content(fn (?Sale $record) => $record && $record->aib_amount > 0 
                                ? number_format($record->aib_amount, 0, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'XOF')
                                : '-'),
                        Forms\Components\Placeholder::make('total_with_aib_display')
                            ->label('Net à payer (TTC + AIB)')
                            ->content(fn (?Sale $record) => $record 
                                ? number_format($record->total_with_aib, 0, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'XOF')
                                : '-')
                            ->extraAttributes(['class' => 'font-bold text-lg']),
                    ])
                    ->columns(4)
                    ->visible(fn () => Filament::getTenant()?->aib_mode !== 'disabled')
                    ->collapsed(fn (?Sale $record) => !$record || $record->aib_amount == 0),

                Forms\Components\Section::make('Articles')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Articles')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(function (Forms\Get $get) {
                                        $warehouseId = $get('../../warehouse_id');
                                        if (!$warehouseId) {
                                            // Fallback: tous les produits avec stock > 0
                                            return Product::whereHas('warehouses', fn ($q) => $q->where('quantity', '>', 0))
                                                ->pluck('name', 'id');
                                        }
                                        
                                        // Produits avec stock dans l'entrepôt sélectionné
                                        return \DB::table('product_warehouse')
                                            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                                            ->where('product_warehouse.warehouse_id', $warehouseId)
                                            ->where('product_warehouse.quantity', '>', 0)
                                            ->pluck('products.name', 'products.id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $company = Filament::getTenant();
                                                // Taux TVA par défaut: 18% si e-MCeF, sinon celui du produit
                                                $defaultVatRate = $company?->emcef_enabled ? 18 : 20;
                                                $defaultVatCategory = $company?->emcef_enabled ? 'A' : 'S';
                                                
                                                // Utiliser le prix de vente HT du produit (quantité initiale = 1)
                                                $set('unit_price', $product->sale_price_ht);
                                                $set('vat_rate', $product->vat_rate_sale ?? $defaultVatRate);
                                                $set('vat_category', $product->vat_category ?? $defaultVatCategory);
                                                $set('tax_specific_amount', $product->tax_specific_amount);
                                                $set('quantity', 1);
                                                $set('is_wholesale', false);
                                                $set('retail_unit_price', null);
                                                
                                                // Calculer le total
                                                $vatRate = $product->vat_rate_sale ?? $defaultVatRate;
                                                $totalHt = $product->sale_price_ht;
                                                // Groupe E: taxe spécifique = montant fixe × quantité
                                                if (($product->vat_category ?? '') === 'E' && $product->tax_specific_amount > 0) {
                                                    $vat = round($product->tax_specific_amount * 1, 2); // qty=1
                                                } else {
                                                    $vat = round($totalHt * ($vatRate / 100), 2);
                                                }
                                                $set('total_price', $totalHt + $vat);
                                                
                                                // Info prix de gros si disponible
                                                if ($product->hasWholesalePrice()) {
                                                    $set('wholesale_info', "Prix gros: " . number_format($product->wholesale_price_ht, 0, ',', ' ') . " (≥{$product->min_wholesale_qty})");
                                                } else {
                                                    $set('wholesale_info', null);
                                                }
                                                
                                                // Récupérer le stock disponible dans l'entrepôt
                                                $warehouseId = $get('../../warehouse_id');
                                                if ($warehouseId) {
                                                    $stock = \DB::table('product_warehouse')
                                                        ->where('product_id', $state)
                                                        ->where('warehouse_id', $warehouseId)
                                                        ->value('quantity') ?? 0;
                                                    $set('available_stock', $stock);
                                                }
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('available_stock')
                                    ->label('Dispo.')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qté')
                                    ->required()
                                    ->numeric()
                                    ->step(0.001)
                                    ->default(1)
                                    ->minValue(0.001)
                                    ->maxValue(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        $warehouseId = $get('../../warehouse_id');
                                        if ($productId && $warehouseId) {
                                            return \DB::table('product_warehouse')
                                                ->where('product_id', $productId)
                                                ->where('warehouse_id', $warehouseId)
                                                ->value('quantity') ?? 1;
                                        }
                                        return 999999;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $quantity = floatval($state);
                                        $productId = $get('product_id');
                                        $vatRate = $get('vat_rate') ?? 18;
                                        
                                        if ($quantity && $productId) {
                                            $product = Product::find($productId);
                                            if ($product) {
                                                // Vérifier si le prix de gros s'applique
                                                $priceData = SaleItem::calculatePriceForQuantity($product, $quantity);
                                                
                                                $unitPrice = $priceData['unit_price'];
                                                $set('unit_price', $unitPrice);
                                                $set('is_wholesale', $priceData['is_wholesale']);
                                                $set('retail_unit_price', $priceData['retail_unit_price']);
                                                
                                                // Calculer le total
                                                $totalHt = $quantity * $unitPrice;
                                                // Groupe E: taxe spécifique
                                                $vatCategory = $get('vat_category');
                                                $taxSpecific = (float) ($get('tax_specific_amount') ?? 0);
                                                if ($vatCategory === 'E' && $taxSpecific > 0) {
                                                    $vat = round($taxSpecific * $quantity, 2);
                                                } else {
                                                    $vat = round($totalHt * ($vatRate / 100), 2);
                                                }
                                                $set('total_price', $totalHt + $vat);
                                                
                                                // Message prix de gros
                                                if ($priceData['is_wholesale']) {
                                                    $savings = ($priceData['retail_unit_price'] - $unitPrice) * $quantity;
                                                    $set('wholesale_info', "✅ Prix GROS appliqué! Économie: " . number_format($savings, 0, ',', ' '));
                                                } elseif ($product->hasWholesalePrice()) {
                                                    $remaining = $product->min_wholesale_qty - $quantity;
                                                    $set('wholesale_info', "Encore {$remaining} unité(s) pour le prix gros (-" . $product->getWholesaleDiscountPercent() . "%)");
                                                }
                                            }
                                        } else {
                                            $unitPrice = $get('unit_price');
                                            if ($quantity && $unitPrice) {
                                                $totalHt = $quantity * $unitPrice;
                                                $vatCategory = $get('vat_category');
                                                $taxSpecific = (float) ($get('tax_specific_amount') ?? 0);
                                                if ($vatCategory === 'E' && $taxSpecific > 0) {
                                                    $vat = round($taxSpecific * $quantity, 2);
                                                } else {
                                                    $vat = round($totalHt * ($vatRate / 100), 2);
                                                }
                                                $set('total_price', $totalHt + $vat);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('P.U. HT')
                                    ->required()
                                    ->numeric()
                                    ->suffix(fn () => Filament::getTenant()->currency ?? 'XOF')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $company = Filament::getTenant();
                                        $defaultVatRate = $company?->emcef_enabled ? 18 : 20;
                                        $quantity = $get('quantity');
                                        $unitPrice = $state;
                                        $vatRate = $get('vat_rate') ?? $defaultVatRate;
                                        if ($quantity && $unitPrice) {
                                            $totalHt = $quantity * $unitPrice;
                                            $vatCategory = $get('vat_category');
                                            $taxSpecific = (float) ($get('tax_specific_amount') ?? 0);
                                            if ($vatCategory === 'E' && $taxSpecific > 0) {
                                                $vat = round($taxSpecific * floatval($quantity), 2);
                                            } else {
                                                $vat = round($totalHt * ($vatRate / 100), 2);
                                            }
                                            $set('total_price', $totalHt + $vat);
                                        }
                                    }),
                                Forms\Components\Select::make('vat_rate')
                                    ->label('TVA')
                                    ->options(Product::getCommonVatRates())
                                    ->default(fn () => Filament::getTenant()?->emcef_enabled ? 18.00 : 20.00)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $company = Filament::getTenant();
                                        $defaultVatRate = $company?->emcef_enabled ? 18 : 20;
                                        $quantity = $get('quantity');
                                        $unitPrice = $get('unit_price');
                                        $vatRate = $state ?? $defaultVatRate;
                                        if ($quantity && $unitPrice) {
                                            $totalHt = $quantity * $unitPrice;
                                            $vatCategory = $get('vat_category');
                                            $taxSpecific = (float) ($get('tax_specific_amount') ?? 0);
                                            if ($vatCategory === 'E' && $taxSpecific > 0) {
                                                $vat = round($taxSpecific * floatval($quantity), 2);
                                            } else {
                                                $vat = round($totalHt * ($vatRate / 100), 2);
                                            }
                                            $set('total_price', $totalHt + $vat);
                                        }
                                    }),
                                Forms\Components\Hidden::make('vat_category')
                                    ->default(fn () => Filament::getTenant()?->emcef_enabled ? 'A' : 'S'),
                                Forms\Components\Hidden::make('tax_specific_amount')
                                    ->default(null),
                                Forms\Components\Hidden::make('is_wholesale')
                                    ->default(false),
                                Forms\Components\Hidden::make('retail_unit_price'),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total TTC')
                                    ->required()
                                    ->numeric()
                                    ->suffix(fn () => Filament::getTenant()->currency ?? 'XOF')
                                    ->disabled(),
                                Forms\Components\Placeholder::make('wholesale_info')
                                    ->label('')
                                    ->content(fn (Forms\Get $get) => $get('wholesale_info') 
                                        ? new \Illuminate\Support\HtmlString("<span class='text-xs text-green-600'>" . $get('wholesale_info') . "</span>")
                                        : null)
                                    ->hidden(fn (Forms\Get $get) => empty($get('wholesale_info'))),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->hidden(fn (Forms\Get $get) => !$get('warehouse_id')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Numéro de facture')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit_note' => 'danger',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_note' => 'Avoir',
                        default => 'Facture',
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Entrepôt')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        default => 'En attente',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paid' => 'Payé',
                        'partial' => 'Partiel',
                        default => 'Non payé',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Payé')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                // e-MCeF (Bénin)
                Tables\Columns\TextColumn::make('emcef_status')
                    ->label('e-MCeF')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'certified' => 'success',
                        'submitted' => 'info',
                        'pending' => 'warning',
                        'error' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'certified' => '✅ Certifiée',
                        'submitted' => '🔄 Soumise',
                        'pending' => '⏳ En attente',
                        'error' => '❌ Erreur',
                        'cancelled' => '🚫 Annulée',
                        null => '-',
                        default => $state,
                    })
                    ->toggleable()
                    ->visible(fn () => Filament::getTenant()?->emcef_enabled ?? false),
                Tables\Columns\TextColumn::make('emcef_nim')
                    ->label('NIM')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Filament::getTenant()?->emcef_enabled ?? false)
                    ->copyable(),
                Tables\Columns\TextColumn::make('emcef_code_mecef')
                    ->label('Code MECeF')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Filament::getTenant()?->emcef_enabled ?? false)
                    ->copyable(),
                Tables\Columns\TextColumn::make('aib_rate')
                    ->label('AIB')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'A' => '1%',
                        'B' => '5%',
                        default => '-',
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Filament::getTenant()?->aib_mode !== 'disabled'),
                Tables\Columns\TextColumn::make('aib_amount')
                    ->label('Montant AIB')
                    ->money(fn () => Filament::getTenant()?->currency ?? 'XOF')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Filament::getTenant()?->aib_mode !== 'disabled'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Boutique')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->is_super_admin),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Statut paiement')
                    ->options([
                        'pending' => 'Non payé',
                        'partial' => 'Paiement partiel',
                        'paid' => 'Payé',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut vente')
                    ->options([
                        'pending' => 'En attente',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ]),
            ])
            ->deferLoading() // Optimisation: Chargement différé via AJAX
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),
                Tables\Actions\EditAction::make()
                    ->label('Modifier')
                    ->hidden(fn (Sale $record) => $record->status === 'completed'),
                Tables\Actions\Action::make('add_payment')
                    ->label('Paiement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->hidden(fn (Sale $record) => $record->payment_status === 'paid')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de paiement')
                            ->options(\App\Models\Payment::METHODS)
                            ->required()
                            ->default('cash'),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->numeric()
                            ->required()
                            ->prefix('FCFA')
                            ->default(fn (Sale $record) => $record->total - $record->amount_paid),
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes'),
                    ])
                    ->action(function (Sale $record, array $data) {
                        $record->payments()->create([
                            'company_id' => $record->company_id,
                            'amount' => $data['amount'],
                            'payment_method' => $data['payment_method'],
                            'payment_date' => $data['payment_date'],
                            'reference' => $data['reference'] ?? null,
                            'account_number' => \App\Models\Payment::ACCOUNTS[$data['payment_method']] ?? '512000',
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Paiement enregistré')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->hidden(fn (Sale $record) => $record->status === 'completed'),
                Tables\Actions\Action::make('invoice')
                    ->label('Facture')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Sale $record): string => route('sales.invoice', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('preview')
                    ->label('Prévisualiser')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->url(fn (Sale $record): string => route('sales.invoice.preview', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_email')
                    ->label('Envoyer email')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Destinataire')
                            ->email()
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Message (optionnel)')
                            ->rows(3),
                    ])
                    ->action(function (array $data, Sale $record) {
                        \Mail::to($data['email'])->send(new \App\Mail\InvoiceMail('sale', $record, $data['message'] ?? ''));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la facture par email')
                    ->modalButton('Envoyer')
                    ->color('success'),
                // e-MCeF Actions (Bénin)
                Tables\Actions\Action::make('certify_emcef')
                    ->label('Certifier (e-MCeF)')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Certifier la facture')
                    ->modalDescription('Voulez-vous certifier cette facture auprès de la DGI Bénin (e-MCeF) ?')
                    ->action(function (Sale $record) {
                        $company = \Filament\Facades\Filament::getTenant();
                        if (!$company->emcef_enabled) {
                            \Filament\Notifications\Notification::make()
                                ->title('e-MCeF non configuré')
                                ->body('Veuillez d\'abord configurer e-MCeF dans les paramètres.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $emcefService = new \App\Services\EmcefService($company);
                        $result = $emcefService->submitInvoice($record);
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facture certifiée !')
                                ->body('NIM: ' . $record->fresh()->emcef_nim . ' | Code: ' . $record->fresh()->emcef_code_mecef)
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur de certification')
                                ->body($result['error'] ?? 'Erreur inconnue')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (Sale $record) {
                        $company = \Filament\Facades\Filament::getTenant();
                        return $company?->emcef_enabled 
                            && $record->status === 'completed' 
                            && !in_array($record->emcef_status, ['certified', 'submitted']);
                    }),
                Tables\Actions\Action::make('view_emcef_details')
                    ->label('Détails e-MCeF')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->modalHeading('Détails de certification e-MCeF')
                    ->modalContent(fn (Sale $record) => view('filament.modals.emcef-details', ['sale' => $record]))
                    ->modalSubmitAction(false)
                    ->visible(fn (Sale $record) => $record->emcef_status === 'certified'),
                Tables\Actions\Action::make('retry_emcef')
                    ->label('Réessayer e-MCeF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Réessayer la certification')
                    ->modalDescription(function (Sale $record) {
                        if ($record->emcef_status === 'submitted' && $record->emcef_submitted_at) {
                            $minutes = now()->diffInMinutes($record->emcef_submitted_at);
                            if ($minutes < 2) {
                                return "La facture a été soumise il y a {$minutes} minute(s). La confirmation sera retentée.";
                            }
                            return "Le délai de 2 minutes est dépassé ({$minutes} min). La facture sera re-soumise entièrement.";
                        }
                        return 'Voulez-vous réessayer la certification de cette facture ?';
                    })
                    ->action(function (Sale $record) {
                        $company = \Filament\Facades\Filament::getTenant();
                        if (!$company->emcef_enabled) {
                            \Filament\Notifications\Notification::make()
                                ->title('e-MCeF non configuré')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Exécution immédiate (pas de job) pour le retry manuel
                        $emcefService = new \App\Services\EmcefService($company);
                        $result = $emcefService->submitInvoice($record);
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facture certifiée !')
                                ->body('NIM: ' . $record->fresh()->emcef_nim . ' | Code: ' . $record->fresh()->emcef_code_mecef)
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur de certification')
                                ->body($result['error'] ?? 'Erreur inconnue')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record) => in_array($record->emcef_status, ['error', 'submitted'])),
                // Action pour confirmer uniquement (facture déjà soumise)
                Tables\Actions\Action::make('confirm_emcef_only')
                    ->label('Confirmer (2 min)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer la facture')
                    ->modalDescription(function (Sale $record) {
                        if ($record->emcef_submitted_at) {
                            $seconds = now()->diffInSeconds($record->emcef_submitted_at);
                            $remaining = max(0, 120 - $seconds);
                            return "⏱️ Temps restant estimé : " . floor($remaining / 60) . "min " . ($remaining % 60) . "s. Confirmer maintenant ?";
                        }
                        return 'Confirmer cette facture auprès de la DGI ?';
                    })
                    ->action(function (Sale $record) {
                        $company = \Filament\Facades\Filament::getTenant();
                        $emcefService = new \App\Services\EmcefService($company);
                        $result = $emcefService->confirmInvoice($record);
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facture confirmée !')
                                ->body('NIM: ' . $record->fresh()->emcef_nim)
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur de confirmation')
                                ->body($result['error'] ?? 'Délai peut-être dépassé. Utilisez "Réessayer" pour resoumettre.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record) => $record->emcef_status === 'submitted' && !empty($record->emcef_uid)),
                Tables\Actions\Action::make('credit_note')
                    ->label('Générer un avoir')
                    ->icon('heroicon-o-receipt-refund')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Générer un avoir')
                    ->modalDescription(function (Sale $record) {
                        $company = \Filament\Facades\Filament::getTenant();
                        $desc = 'Voulez-vous vraiment générer un avoir pour cette facture ? Cela créera une nouvelle facture négative et réintégrera le stock.';
                        
                        if ($company?->emcef_enabled) {
                            if ($record->emcef_status !== 'certified' || empty($record->emcef_code_mecef)) {
                                return '⚠️ ATTENTION : Cette facture n\'est pas certifiée e-MCeF. L\'avoir ne pourra pas être envoyé à la DGI.';
                            }
                            $desc .= "\n\n✅ L'avoir sera automatiquement certifié e-MCeF avec référence à la facture " . $record->emcef_code_mecef;
                        }
                        return $desc;
                    })
                    ->action(function (Sale $record) {
                        $company = \Filament\Facades\Filament::getTenant();
                        
                        // Vérifier la certification e-MCeF si activé
                        if ($company?->emcef_enabled && ($record->emcef_status !== 'certified' || empty($record->emcef_code_mecef))) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facture non certifiée')
                                ->body('Vous devez d\'abord certifier la facture originale avant de créer un avoir e-MCeF.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 1. Dupliquer la vente en avoir
                        $creditNote = $record->replicate([
                            'invoice_number', 
                            'security_hash', 
                            'previous_hash', 
                            'created_at', 
                            'updated_at',
                            // Reset e-MCeF fields
                            'emcef_uid',
                            'emcef_submitted_at',
                            'emcef_nim',
                            'emcef_code_mecef',
                            'emcef_qr_code',
                            'emcef_counters',
                            'emcef_status',
                            'emcef_certified_at',
                            'emcef_error',
                        ]);
                        $creditNote->type = 'credit_note';
                        $creditNote->parent_id = $record->id;
                        $creditNote->status = 'completed';
                        $creditNote->notes = "Avoir annulant la facture n°{$record->invoice_number}";
                        $creditNote->total = -abs($record->total);
                        $creditNote->total_ht = -abs($record->total_ht ?? 0);
                        $creditNote->total_vat = -abs($record->total_vat ?? 0);
                        $creditNote->emcef_status = $company?->emcef_enabled ? 'pending' : null;
                        $creditNote->save();

                        // 2. Dupliquer les articles avec les mêmes taux TVA
                        foreach ($record->items as $item) {
                            $creditNote->items()->create([
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price, // Prix positif
                                'total_price' => $item->quantity * $item->unit_price, // Total positif (le type credit_note indique l'inversion)
                                'vat_rate' => $item->vat_rate ?? 18,
                                'vat_category' => $item->vat_category ?? 'A',
                                'tax_specific_amount' => $item->tax_specific_amount,
                            ]);
                        }
                        
                        // 3. Certifier automatiquement l'avoir si e-MCeF activé
                        if ($company?->emcef_enabled) {
                            $emcefService = new \App\Services\EmcefService($company);
                            $result = $emcefService->submitInvoice($creditNote);
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Avoir créé et certifié !')
                                    ->body('NIM: ' . $creditNote->fresh()->emcef_nim . ' | Réf. facture: ' . $record->emcef_code_mecef)
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Avoir créé mais erreur e-MCeF')
                                    ->body($result['error'] ?? 'Erreur inconnue. Vous pouvez réessayer manuellement.')
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Avoir créé')
                                ->body('L\'avoir ' . $creditNote->invoice_number . ' a été créé.')
                                ->success()
                                ->send();
                        }
                        
                        // Redirection vers l'avoir créé
                        return redirect()->to(SaleResource::getUrl('edit', ['record' => $creditNote]));
                    })
                    ->visible(fn (Sale $record) => $record->status === 'completed' && $record->type === 'invoice' && !$record->creditNotes()->exists()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            if ($records->contains('status', 'completed')) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Action refusée')
                                    ->body('Impossible de supprimer des ventes terminées.')
                                    ->send();
                                
                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}

