<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\HasWarehouseScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Sale extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity, HasWarehouseScope;

    protected $fillable = [
        'company_id',
        'cash_session_id',
        'invoice_number',
        'type',
        'parent_id',
        'customer_id',
        'warehouse_id',
        'bank_account_id',
        'total',
        'total_ht',
        'total_vat',
        // AIB (Acompte sur Impôt Bénéfices - Bénin)
        'aib_rate',
        'aib_amount',
        'aib_exempt',
        'status',
        'payment_status',
        'amount_paid',
        'paid_at',
        'payment_method',
        'payment_details',
        'discount_percent',
        'tax_percent',
        'security_hash',
        'previous_hash',
        'notes',
        'ppf_status',
        'ppf_id',
        'ppf_chorus_id',
        'ppf_synced_at',
        // e-MCeF (Bénin)
        'emcef_uid',
        'emcef_submitted_at',
        'emcef_nim',
        'emcef_code_mecef',
        'emcef_qr_code',
        'emcef_counters',
        'emcef_status',
        'emcef_certified_at',
        'emcef_error',
    ];

    protected $casts = [
        'status' => 'string',
        'payment_status' => 'string',
        'amount_paid' => 'decimal:2',
        'aib_amount' => 'decimal:2',
        'aib_exempt' => 'boolean',
        'paid_at' => 'datetime',
        'payment_details' => 'array',
        'ppf_synced_at' => 'datetime',
        'emcef_submitted_at' => 'datetime',
        'emcef_certified_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'customer_id', 'total', 'status', 'payment_method'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sales')
            ->setDescriptionForEvent(fn(string $eventName) => "Vente {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(Sale::class, 'parent_id')->where('type', 'credit_note');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Paiements reçus pour cette vente
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Écritures comptables liées à cette vente
     */
    public function accountingEntries()
    {
        return $this->morphMany(AccountingEntry::class, 'source');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->invoice_number)) {
                // Numérotation séquentielle par entreprise avec Année (Format FAC-YYYY-XXXXX)
                $year = date('Y');
                $prefix = ($sale->type === 'credit_note' ? 'AVR-' : 'FAC-') . $year . '-';
                
                // Compatible SQLite et MySQL
                $driver = config('database.default');
                $substringFn = $driver === 'sqlite' ? 'SUBSTR' : 'SUBSTRING';
                
                $lastNumber = self::withoutGlobalScopes()
                    ->where('company_id', $sale->company_id)
                    ->where('invoice_number', 'like', $prefix . '%')
                    ->selectRaw("MAX(CAST({$substringFn}(invoice_number, 10) AS INTEGER)) as max_num")
                    ->value('max_num') ?? 0;
                
                $sale->invoice_number = $prefix . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
            }
            
            // Assigner l'entrepôt par défaut si non spécifié
            if (empty($sale->warehouse_id)) {
                $defaultWarehouse = Warehouse::getDefault($sale->company_id);
                $sale->warehouse_id = $defaultWarehouse?->id;
            }

            // Génération du hash de sécurité (Chaînage NF525)
            $sale->generateSecurityHash();
        });

        // Gérer le cas d'une vente créée directement avec status = 'completed'
        // IMPORTANT: Pour les ventes POS, le total n'est pas encore calculé à ce stade
        // (les items sont ajoutés après). Les opérations post-création seront donc
        // déclenchées dans calculateTotal() une fois le total connu.
        static::created(function ($sale) {
            // Si la vente est créée avec le statut "completed" ET un total > 0
            // (cas des ventes Filament, pas POS qui ont un total = 0 à la création)
            if ($sale->status === 'completed' && $sale->total > 0) {
                $sale->executePostSaleOperations();
            }
        });

        static::updated(function ($sale) {
            // Si la vente passe à "completed" et qu'un compte bancaire est lié
            if ($sale->wasChanged('status') && $sale->status === 'completed' && $sale->bank_account_id) {
                // Vérifier si une transaction existe déjà pour éviter les doublons
                $exists = BankTransaction::where('reference', $sale->invoice_number)->exists();
                
                if (!$exists) {
                    BankTransaction::create([
                        'bank_account_id' => $sale->bank_account_id,
                        'date' => now(),
                        'amount' => $sale->total,
                        'type' => 'credit',
                        'label' => "Vente " . $sale->invoice_number,
                        'reference' => $sale->invoice_number,
                        'status' => 'pending',
                        'metadata' => ['sale_id' => $sale->id],
                    ]);
                }
            }

            // Générer les écritures comptables quand la vente passe à "completed"
            // DESACTIVE: Module comptabilité désactivé
            /*
            if ($sale->wasChanged('status') && $sale->status === 'completed') {
                try {
                    $accountingService = app(\App\Services\AccountingEntryService::class);
                    $accountingService->createEntriesForSale($sale);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur génération écritures comptables vente {$sale->invoice_number}: " . $e->getMessage()
                    );
                }
            }
            */

            // Générer les écritures de contre-passation pour un avoir
            // DESACTIVE: Module comptabilité désactivé
            /*
            if ($sale->wasChanged('status') && $sale->status === 'completed' && $sale->type === 'credit_note' && $sale->parent_id) {
                try {
                    $accountingService = app(\App\Services\AccountingEntryService::class);
                    $originalSale = Sale::find($sale->parent_id);
                    if ($originalSale) {
                        $accountingService->reverseEntries($originalSale, $sale);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur génération écritures avoir {$sale->invoice_number}: " . $e->getMessage()
                    );
                }
            }
            */
        });

        static::updating(function ($sale) {
            // Protection NF525 : Vérifier l'intégrité de la chaîne
            if ($sale->security_hash) {
                // EXCEPTION: Si le total original était 0 (vente POS pas encore finalisée),
                // on permet la mise à jour du total car la vente n'était pas réellement "scellée"
                $originalTotal = $sale->getOriginal('total');
                $isInitialTotalUpdate = ($originalTotal === null || $originalTotal == 0) && $sale->isDirty('total');
                
                // Vérifier s'il existe une facture postérieure scellée
                $nextSaleExists = self::where('company_id', $sale->company_id)
                    ->where('id', '>', $sale->id)
                    ->whereNotNull('security_hash')
                    ->exists();

                if ($nextSaleExists && !$isInitialTotalUpdate) {
                    // Si on essaie de modifier des données critiques d'une facture déjà chaînée
                    // (sauf si c'est la mise à jour initiale du total d'une vente POS)
                    if ($sale->isDirty(['total', 'invoice_number', 'created_at', 'customer_id'])) {
                        throw new \Exception("Opération illégale (NF525) : Impossible de modifier une facture déjà scellée et chaînée.");
                    }
                } else {
                    // C'est la dernière facture OU c'est une mise à jour initiale du total
                    // On peut mettre à jour le hash
                    if ($sale->isDirty(['total', 'invoice_number', 'created_at', 'customer_id'])) {
                        $sale->generateSecurityHash();
                    }
                }
            } else {
                // Si pas de hash (ex: brouillon ou ancienne facture), on le génère
                $sale->generateSecurityHash();
            }
        });

        static::saving(function ($sale) {
            if ($sale->isDirty('status') && $sale->status === 'completed') {
                $sale->processStockDeduction();
            }
        });

        static::deleting(function ($sale) {
            if ($sale->status === 'completed') {
                $sale->reverseStockDeduction();
            }
        });
    }

    /**
     * Déduire le stock de l'entrepôt lors de la vente
     */
    public function processStockDeduction(): void
    {
        $warehouse = $this->warehouse ?? Warehouse::getDefault($this->company_id);
        
        if (!$warehouse) {
            throw new \Exception("Aucun entrepôt disponible pour traiter la vente.");
        }

        foreach ($this->items as $item) {
            // Créer mouvement de stock via le système multi-entrepôt
            $warehouse->adjustStock(
                $item->product_id,
                -$item->quantity,
                'sale',
                "Vente {$this->invoice_number}",
                null // location_id
            );
        }
    }

    /**
     * Annuler la déduction de stock (suppression ou annulation)
     */
    public function reverseStockDeduction(): void
    {
        $warehouse = $this->warehouse ?? Warehouse::getDefault($this->company_id);
        
        if (!$warehouse) {
            return;
        }

        foreach ($this->items as $item) {
            $warehouse->adjustStock(
                $item->product_id,
                $item->quantity,
                'return_in',
                "Annulation vente {$this->invoice_number}",
                null
            );
        }
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function calculateTotal(): void
    {
        // Calculer les totaux à partir des lignes (TVA gérée par ligne)
        $totalHt = $this->items()->sum('total_price_ht');
        $totalVat = $this->items()->sum('vat_amount');
        
        \Log::info("Sale::calculateTotal id={$this->id}: items_count=" . $this->items()->count() . ", sum_ht={$totalHt}, sum_vat={$totalVat}");
        
        $subtotal = $totalHt + $totalVat; // Total TTC avant remise
        
        // Appliquer la remise globale (sur TTC)
        $discount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $discount;
        
        // Note: tax_percent est maintenant obsolète car la TVA est gérée par ligne
        // On le garde pour compatibilité mais il ne devrait plus être utilisé
        
        $hadNoTotal = !$this->total || $this->total <= 0;
        
        $this->total_ht = round($totalHt * (1 - $this->discount_percent / 100), 2);
        $this->total_vat = round($totalVat * (1 - $this->discount_percent / 100), 2);
        $this->total = round($afterDiscount, 2);
        
        // Utiliser saveQuietly() pour éviter de déclencher les events updating/updated
        // qui pourraient bloquer la sauvegarde (NF525, etc.)
        $this->saveQuietly();

        // Si c'est la première fois que le total est calculé (ventes POS),
        // exécuter les opérations post-vente maintenant
        if ($hadNoTotal && $this->total > 0 && $this->status === 'completed') {
            $this->executePostSaleOperations();
        }
    }

    /**
     * Exécute les opérations post-vente (transactions bancaires, écritures comptables, paiements)
     * Appelé soit dans created() si le total est déjà connu, soit dans calculateTotal() pour les ventes POS
     */
    public function executePostSaleOperations(): void
    {
        // Créer la transaction bancaire si compte bancaire lié
        if ($this->bank_account_id && $this->total > 0) {
            $exists = BankTransaction::where('reference', $this->invoice_number)->exists();
            
            if (!$exists) {
                BankTransaction::create([
                    'bank_account_id' => $this->bank_account_id,
                    'date' => now(),
                    'amount' => $this->total,
                    'type' => $this->type === 'credit_note' ? 'debit' : 'credit',
                    'label' => ($this->type === 'credit_note' ? "Avoir " : "Vente ") . $this->invoice_number,
                    'reference' => $this->invoice_number,
                    'status' => 'pending',
                    'metadata' => ['sale_id' => $this->id],
                ]);
            }
        }

        // Générer les écritures comptables
        // DESACTIVE: Module comptabilité désactivé
        /*
        try {
            $accountingService = app(\App\Services\AccountingEntryService::class);
            
            // Si c'est un avoir avec une facture parente, contre-passer les écritures
            if ($this->type === 'credit_note' && $this->parent_id) {
                $originalSale = Sale::find($this->parent_id);
                if ($originalSale) {
                    $accountingService->reverseEntries($originalSale, $this);
                }
            } else {
                // Vente normale : créer les écritures standard
                $accountingService->createEntriesForSale($this);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Erreur génération écritures comptables {$this->type} {$this->invoice_number}: " . $e->getMessage()
            );
        }
        */

        // Enregistrer le paiement POS si payé immédiatement
        // DESACTIVE: Module comptabilité désactivé
        /*
        if ($this->payment_method && $this->cash_session_id) {
            try {
                $accountingService = app(\App\Services\AccountingEntryService::class);
                $accountingService->registerPosPayment($this);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    "Erreur enregistrement paiement POS {$this->invoice_number}: " . $e->getMessage()
                );
            }
        }
        */
    }

    /**
     * Retourne la ventilation TVA par taux (pour Chorus Pro)
     * @return array [['rate' => 20, 'base' => 100, 'amount' => 20, 'category' => 'S'], ...]
     */
    public function getVatBreakdown(): array
    {
        $breakdown = [];
        
        foreach ($this->items as $item) {
            $rate = (string) $item->vat_rate;
            $category = $item->vat_category ?? 'S';
            $key = $rate . '_' . $category;
            
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'rate' => (float) $rate,
                    'category' => $category,
                    'base' => 0,
                    'amount' => 0,
                ];
            }
            
            // Appliquer la remise proportionnellement
            $discountFactor = 1 - ($this->discount_percent / 100);
            $breakdown[$key]['base'] += round($item->total_price_ht * $discountFactor, 2);
            $breakdown[$key]['amount'] += round($item->vat_amount * $discountFactor, 2);
        }
        
        return array_values($breakdown);
    }

    /**
     * Génère le hash de sécurité pour la facture (Conformité NF525)
     * Le hash inclut les données critiques de la facture actuelle + le hash de la facture précédente.
     */
    public function generateSecurityHash(): void
    {
        // 1. Récupérer la dernière facture de l'entreprise (N-1)
        // On cherche la facture précédente (ID inférieur) ayant un hash
        $query = self::where('company_id', $this->company_id)
            ->whereNotNull('security_hash');
            
        if ($this->id) {
            $query->where('id', '<', $this->id);
        }
            
        $previousSale = $query->orderBy('id', 'desc')->first();

        $previousHash = $previousSale ? $previousSale->security_hash : '';

        // 2. Construire la chaîne de données à signer
        // Format: [DateYYYYMMDDHHMMSS][Total][InvoiceNumber][PreviousHash]
        // On utilise created_at si dispo, sinon now()
        $dateStr = ($this->created_at ?? now())->format('YmdHis');
        
        $dataToSign = implode('', [
            $dateStr,
            $this->invoice_number,
            number_format($this->total, 2, '.', ''),
            $this->customer_id,
            $previousHash
        ]);

        // 3. Générer le hash SHA-256
        $this->previous_hash = $previousHash;
        $this->security_hash = hash('sha256', $dataToSign);
    }

    // ===========================================
    // AIB (Acompte sur Impôt Bénéfices - Bénin)
    // ===========================================

    /**
     * Détermine automatiquement le taux AIB en fonction du client
     * - A (1%) : Client avec IFU valide
     * - B (5%) : Client sans IFU
     * - null : Exonéré (vente au détail si configuré)
     */
    public function determineAibRate(): ?string
    {
        // Si exonération manuelle
        if ($this->aib_exempt) {
            return null;
        }

        $company = $this->company ?? Company::find($this->company_id);
        
        // Si AIB désactivé
        if (!$company || $company->aib_mode === 'disabled') {
            return null;
        }

        // Mode manuel : ne pas calculer automatiquement
        if ($company->aib_mode === 'manual' && !$this->aib_rate) {
            return null;
        }

        $customer = $this->customer;

        // Si pas de client ou client sans IFU
        if (!$customer || empty($customer->tax_number)) {
            // Si vente au détail exonérée
            if ($company->aib_exempt_retail) {
                return null;
            }
            return 'B'; // 5%
        }

        // Client avec IFU valide
        return 'A'; // 1%
    }

    /**
     * Calcule le montant AIB
     * Base de calcul : Montant HT
     */
    public function calculateAibAmount(): float
    {
        if (!$this->aib_rate) {
            return 0;
        }

        $rate = $this->getAibPercentage();
        return round($this->total_ht * ($rate / 100), 2);
    }

    /**
     * Retourne le pourcentage AIB selon le taux
     */
    public function getAibPercentage(): float
    {
        return match ($this->aib_rate) {
            'A' => 1,    // 1% pour client avec IFU
            'B' => 5,    // 5% pour client sans IFU
            default => 0,
        };
    }

    /**
     * Applique automatiquement l'AIB
     */
    public function applyAib(): void
    {
        $this->aib_rate = $this->determineAibRate();
        $this->aib_amount = $this->calculateAibAmount();
    }

    /**
     * Retourne le total avec AIB
     * Total TTC + AIB
     */
    public function getTotalWithAibAttribute(): float
    {
        return $this->total + ($this->aib_amount ?? 0);
    }

    /**
     * Label AIB pour affichage
     */
    public function getAibLabelAttribute(): string
    {
        return match ($this->aib_rate) {
            'A' => 'AIB (1%)',
            'B' => 'AIB (5%)',
            default => '',
        };
    }
}
