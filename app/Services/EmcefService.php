<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmcefService
{
    /**
     * URL de base de l'API e-MCeF
     */
    protected string $baseUrl;
    protected Company $company;
    protected ?string $token;

    /**
     * URLs de l'API
     */
    const PRODUCTION_URL = 'https://sygmef.impots.bj/sygmef-emcf';
    const SANDBOX_URL = 'https://developper.impots.bj/sygmef-emcf';

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->baseUrl = $company->emcef_sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        $this->token = $company->emcef_token;
    }

    /**
     * Vérifie si e-MCeF est activé pour l'entreprise
     */
    public function isEnabled(): bool
    {
        return $this->company->emcef_enabled 
            && !empty($this->company->emcef_token) 
            && !empty($this->company->emcef_nim);
    }

    /**
     * Récupère le client HTTP configuré
     */
    protected function getHttpClient()
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Vérifie le statut de la connexion e-MCeF
     */
    public function getStatus(): array
    {
        try {
            $response = $this->getHttpClient()->get('/api/info/status');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur de connexion au serveur e-MCeF',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF Status Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifie un IFU auprès de la DGI et retourne les informations du contribuable
     */
    public function verifyIfu(string $ifu): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'e-MCeF n\'est pas activé pour cette entreprise',
            ];
        }

        // Validation du format IFU (13 chiffres)
        if (!preg_match('/^\d{13}$/', $ifu)) {
            return [
                'success' => false,
                'error' => 'L\'IFU doit contenir exactement 13 chiffres',
            ];
        }

        try {
            $response = $this->getHttpClient()->get('/api/info/taxpayer/' . $ifu);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            // Tentative endpoint alternatif
            $response2 = $this->getHttpClient()->get('/api/taxpayer/' . $ifu);
            if ($response2->successful()) {
                $data = $response2->json();
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'IFU non trouvé (code ' . $response->status() . ')',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF IFU Lookup Error', ['ifu' => $ifu, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Erreur de connexion au serveur DGI: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Récupère les groupes de taxes
     */
    public function getTaxGroups(): array
    {
        try {
            $response = $this->getHttpClient()->get('/api/info/taxGroups');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des groupes de taxes',
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF TaxGroups Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Récupère les types de factures disponibles
     */
    public function getInvoiceTypes(): array
    {
        return Cache::remember('emcef_invoice_types', 3600, function () {
            try {
                $response = $this->getHttpClient()->get('/api/info/invoiceTypes');
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                return [];
            } catch (\Exception $e) {
                Log::error('e-MCeF InvoiceTypes Error', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Récupère les types de paiement disponibles
     */
    public function getPaymentTypes(): array
    {
        return Cache::remember('emcef_payment_types', 3600, function () {
            try {
                $response = $this->getHttpClient()->get('/api/info/paymentTypes');
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                return [];
            } catch (\Exception $e) {
                Log::error('e-MCeF PaymentTypes Error', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Soumet une facture à e-MCeF
     */
    public function submitInvoice(Sale $sale): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'e-MCeF n\'est pas activé pour cette entreprise',
            ];
        }

        // Si déjà soumise mais pas encore confirmée, tenter uniquement la confirmation
        if ($sale->emcef_status === 'submitted' && !empty($sale->emcef_uid)) {
            // Vérifier si le délai de 2 minutes n'est pas dépassé
            if ($sale->emcef_submitted_at && now()->diffInMinutes($sale->emcef_submitted_at) < 2) {
                Log::info('e-MCeF: Facture déjà soumise, tentative de confirmation uniquement', [
                    'sale_id' => $sale->id,
                    'emcef_uid' => $sale->emcef_uid,
                    'minutes_since_submit' => now()->diffInMinutes($sale->emcef_submitted_at),
                ]);
                return $this->confirmInvoice($sale);
            } else {
                // Délai dépassé, remettre en mode pending pour resoumettre
                Log::warning('e-MCeF: Délai de 2 minutes dépassé, ressoumission nécessaire', [
                    'sale_id' => $sale->id,
                    'emcef_uid' => $sale->emcef_uid,
                    'submitted_at' => $sale->emcef_submitted_at,
                ]);
                $sale->update([
                    'emcef_uid' => null,
                    'emcef_status' => 'pending',
                    'emcef_submitted_at' => null,
                ]);
            }
        }

        try {
            $invoiceData = $this->prepareInvoiceData($sale);
            
            Log::info('e-MCeF Submit Invoice', ['data' => $invoiceData]);
            
            $response = $this->getHttpClient()->post('/api/invoice', $invoiceData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Sauvegarder l'UID de la facture et la date de soumission
                $sale->update([
                    'emcef_uid' => $responseData['uid'] ?? null,
                    'emcef_status' => 'submitted',
                    'emcef_submitted_at' => now(),
                ]);
                
                // Si pas d'erreur, confirmer directement la facture
                if (empty($responseData['errorCode'])) {
                    return $this->confirmInvoice($sale);
                }
                
                return [
                    'success' => false,
                    'error' => $responseData['errorDesc'] ?? 'Erreur lors de la soumission',
                    'error_code' => $responseData['errorCode'] ?? null,
                    'data' => $responseData,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur de connexion au serveur e-MCeF',
                'status_code' => $response->status(),
                'body' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF Submit Error', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
            
            $sale->update([
                'emcef_status' => 'error',
                'emcef_error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirme une facture soumise (doit être fait dans les 2 minutes)
     */
    public function confirmInvoice(Sale $sale): array
    {
        if (empty($sale->emcef_uid)) {
            return [
                'success' => false,
                'error' => 'La facture n\'a pas été soumise à e-MCeF',
            ];
        }

        try {
            $response = $this->getHttpClient()->put("/api/invoice/{$sale->emcef_uid}/confirm");
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (empty($responseData['errorCode'])) {
                    // Mettre à jour la vente avec les données de certification
                    $sale->update([
                        'emcef_nim' => $responseData['nim'] ?? null,
                        'emcef_code_mecef' => $responseData['codeMECeFDGI'] ?? null,
                        'emcef_qr_code' => $responseData['qrCode'] ?? null,
                        'emcef_counters' => $responseData['counters'] ?? null,
                        'emcef_status' => 'certified',
                        'emcef_certified_at' => now(),
                        'emcef_error' => null,
                    ]);
                    
                    Log::info('e-MCeF Invoice Certified', [
                        'sale_id' => $sale->id,
                        'nim' => $responseData['nim'] ?? null,
                        'code_mecef' => $responseData['codeMECeFDGI'] ?? null,
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $responseData,
                    ];
                }
                
                $sale->update([
                    'emcef_status' => 'error',
                    'emcef_error' => $responseData['errorDesc'] ?? 'Erreur de confirmation',
                ]);
                
                return [
                    'success' => false,
                    'error' => $responseData['errorDesc'] ?? 'Erreur de confirmation',
                    'error_code' => $responseData['errorCode'] ?? null,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la confirmation',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF Confirm Error', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
            
            $sale->update([
                'emcef_status' => 'error',
                'emcef_error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Annule une facture
     */
    public function cancelInvoice(Sale $sale): array
    {
        if (empty($sale->emcef_uid)) {
            return [
                'success' => false,
                'error' => 'La facture n\'a pas été soumise à e-MCeF',
            ];
        }

        try {
            $response = $this->getHttpClient()->put("/api/invoice/{$sale->emcef_uid}/cancel");
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (empty($responseData['errorCode'])) {
                    $sale->update([
                        'emcef_status' => 'cancelled',
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $responseData,
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $responseData['errorDesc'] ?? 'Erreur d\'annulation',
                    'error_code' => $responseData['errorCode'] ?? null,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'annulation',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF Cancel Error', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Récupère les détails d'une facture
     */
    public function getInvoiceDetails(Sale $sale): array
    {
        if (empty($sale->emcef_uid)) {
            return [
                'success' => false,
                'error' => 'La facture n\'a pas été soumise à e-MCeF',
            ];
        }

        try {
            $response = $this->getHttpClient()->get("/api/invoice/{$sale->emcef_uid}");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des détails',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF GetDetails Error', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Récupère les factures en attente
     */
    public function getPendingInvoices(): array
    {
        try {
            $response = $this->getHttpClient()->get('/api/invoice');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des factures en attente',
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF GetPending Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prépare les données de la facture pour l'API e-MCeF
     */
    protected function prepareInvoiceData(Sale $sale): array
    {
        $sale->load(['items.product', 'customer', 'company']);
        
        // Déterminer le type de facture
        $invoiceType = $this->getInvoiceType($sale);
        
        // Préparer les articles
        $items = [];
        foreach ($sale->items as $item) {
            // Si taxe spécifique présente → Groupe E pour e-MCeF, sinon groupe TVA classique
            $hasSpecificTax = $item->tax_specific_amount > 0;
            $taxGroup = $hasSpecificTax ? 'E' : $this->getTaxGroup($item->vat_rate ?? 18, $item->vat_category);
            
            $itemData = [
                'code' => $item->product->sku ?? $item->product->id,
                'name' => $item->product->name,
                'price' => (int) round($item->unit_price), // Prix en unités entières
                'quantity' => (float) $item->quantity,
                'taxGroup' => $taxGroup,
                'originalPrice' => $item->original_price ? (float) $item->original_price : null,
                'priceModification' => $item->discount_percent > 0 ? "Remise {$item->discount_percent}%" : null,
            ];
            
            // Groupe E: ajouter le montant de taxe spécifique
            if ($hasSpecificTax) {
                $itemData['taxSpecific'] = (int) round($item->tax_specific_amount);
            }
            
            $items[] = $itemData;
        }
        
        // Préparer les données client
        $client = null;
        if ($sale->customer) {
            $client = [
                'ifu' => $sale->customer->tax_number ?? null,
                'name' => $sale->customer->name,
                'contact' => $sale->customer->phone ?? $sale->customer->email,
                'address' => $sale->customer->address,
            ];
        }
        
        // Préparer l'opérateur avec les infos de la boutique pour traçabilité
        $user = auth()->user();
        $operatorName = $user ? $user->getEmcefOperatorName() : 'Système';
        
        $operator = [
            'id' => (string) ($user?->id ?? '1'),
            'name' => $operatorName,
        ];
        
        // Préparer le paiement (split payment si paiement partiel)
        $payments = $this->buildPaymentArray($sale);
        
        return [
            'ifu' => $this->company->tax_number,
            'aib' => $this->getAibGroup($sale),
            'type' => $invoiceType,
            'items' => $items,
            'client' => $client,
            'operator' => $operator,
            'payment' => $payments,
            'reference' => $this->getInvoiceReference($sale, $invoiceType),
        ];
    }

    /**
     * Récupère la référence pour l'API e-MCeF
     * Pour les avoirs (FA/EA), c'est le codeMECeFDGI de la facture originale
     * Pour les factures normales, c'est le numéro de facture interne
     */
    protected function getInvoiceReference(Sale $sale, string $invoiceType): ?string
    {
        // Pour les avoirs (FA ou EA), la référence DOIT être le codeMECeFDGI de la facture originale
        if (in_array($invoiceType, ['FA', 'EA'])) {
            if ($sale->parent_id && $sale->parent) {
                // Vérifier que la facture parent a bien été certifiée
                if (empty($sale->parent->emcef_code_mecef)) {
                    throw new \Exception('La facture originale n\'a pas été certifiée e-MCeF. Impossible de créer un avoir.');
                }
                return $sale->parent->emcef_code_mecef;
            }
            throw new \Exception('Un avoir doit être lié à une facture originale certifiée e-MCeF.');
        }
        
        // Pour les factures normales, on utilise le numéro de facture interne
        return $sale->invoice_number;
    }

    /**
     * Détermine le type de facture e-MCeF
     * FV = Facture de Vente
     * FA = Facture d'Avoir (crédit)
     * EV = Facture de Vente à l'export
     * EA = Facture d'Avoir à l'export
     */
    protected function getInvoiceType(Sale $sale): string
    {
        // Vérifier si c'est une vente à l'export (flag is_export ou au moins un article Groupe C)
        $isExport = $sale->is_export || $sale->items->contains(function ($item) {
            return strtoupper($item->vat_category ?? '') === 'C';
        });

        if ($sale->type === 'credit_note') {
            return $isExport ? 'EA' : 'FA';
        }
        
        return $isExport ? 'EV' : 'FV';
    }

    /**
     * Détermine le groupe AIB (Acompte sur Impôt sur les Bénéfices)
     * A = 1% AIB (client avec IFU)
     * B = 5% AIB (client sans IFU)
     * null = Exonéré
     */
    protected function getAibGroup(Sale $sale): ?string
    {
        // Utiliser le taux AIB calculé sur la vente
        if ($sale->aib_rate) {
            return $sale->aib_rate; // 'A' ou 'B'
        }
        
        // Fallback : déterminer selon le client (rétrocompatibilité)
        if ($sale->customer && !empty($sale->customer->tax_number)) {
            return 'A'; // Client avec IFU = 1%
        }
        
        return null; // Pas d'AIB
    }

    /**
     * Convertit le taux de TVA en groupe de taxe e-MCeF
     * Utilise vat_category en priorité si c'est un groupe e-MCeF valide
     * A = TVA 18% (taux normal)
     * B = TVA 0% (exonéré)
     * C = Exportation
     * D = Régime fiscal particulier
     * E = Taxe spécifique
     * F = Autre
     */
    protected function getTaxGroup(float $vatRate, ?string $vatCategory = null): string
    {
        // Si une catégorie e-MCeF valide est définie, l'utiliser directement
        $validGroups = ['A', 'B', 'C', 'D', 'E', 'F'];
        if ($vatCategory && in_array($vatCategory, $validGroups)) {
            return $vatCategory;
        }
        
        // Sinon, déduire du taux
        return match (true) {
            $vatRate >= 18 => 'A',
            $vatRate == 0 => 'B',
            default => 'A',
        };
    }

    /**
     * Convertit le mode de paiement en type e-MCeF
     */
    protected function mapPaymentMethod(?string $method): string
    {
        return match ($method) {
            'cash', 'especes' => 'ESPECES',
            'transfer', 'bank_transfer', 'virement' => 'VIREMENT',
            'card', 'carte_bancaire' => 'CARTEBANCAIRE',
            'mobile_money', 'momo', 'moov_money', 'mtn_money' => 'MOBILEMONEY',
            'check', 'cheque', 'cheques' => 'CHEQUES',
            'credit' => 'CREDIT',
            default => 'AUTRE',
        };
    }

    /**
     * Construit le tableau des paiements pour l'API e-MCeF
     * Gère automatiquement le split payment (paiement mixte) si paiement partiel
     * 
     * Exemple: Client achète 500.000 F mais ne paie que 100.000 F
     * => [
     *      ['name' => 'ESPECES', 'amount' => 100000],
     *      ['name' => 'CREDIT', 'amount' => 400000]
     *    ]
     */
    protected function buildPaymentArray(Sale $sale): array
    {
        $total = (int) round($sale->total);
        $amountPaid = (int) round($sale->amount_paid ?? 0);
        
        // Si paiement complet ou pas de montant payé spécifié
        if ($amountPaid <= 0 || $amountPaid >= $total) {
            return [
                [
                    'name' => $this->mapPaymentMethod($sale->payment_method),
                    'amount' => $total,
                ],
            ];
        }
        
        // Split payment : partie payée + partie à crédit
        $payments = [];
        
        // Partie payée (espèces, carte, etc.)
        $payments[] = [
            'name' => $this->mapPaymentMethod($sale->payment_method),
            'amount' => $amountPaid,
        ];
        
        // Partie restante à crédit
        $creditAmount = $total - $amountPaid;
        $payments[] = [
            'name' => 'CREDIT',
            'amount' => $creditAmount,
        ];
        
        Log::info('e-MCeF Split Payment', [
            'sale_id' => $sale->id,
            'total' => $total,
            'paid' => $amountPaid,
            'credit' => $creditAmount,
            'payments' => $payments,
        ]);
        
        return $payments;
    }

    /**
     * Génère le code QR à partir des données e-MCeF
     */
    public function generateQrCodeImage(Sale $sale): ?string
    {
        if (empty($sale->emcef_qr_code)) {
            return null;
        }
        
        // Le QR code est déjà encodé en base64 par l'API
        return $sale->emcef_qr_code;
    }

    /**
     * Teste la connexion avec le token fourni
     */
    public static function testConnection(string $token, bool $sandbox = true): array
    {
        $baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        
        // Vérifier si le token semble valide (format JWT)
        if (empty($token) || strlen($token) < 20) {
            return [
                'success' => false,
                'error' => 'Token manquant ou trop court. Veuillez obtenir un token JWT valide depuis le portail développeur de la DGI.',
                'help' => 'Rendez-vous sur https://developper.impots.bj pour créer un compte et obtenir votre token API.',
            ];
        }
        
        try {
            Log::info('e-MCeF Test Connection', [
                'sandbox' => $sandbox,
                'url' => $baseUrl . '/api/info/status',
                'token_length' => strlen($token),
            ]);
            
            $response = Http::baseUrl($baseUrl)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(30)
                ->get('/api/info/status');
            
            Log::info('e-MCeF Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'nim' => $data['nim'] ?? null,
                    'ifu' => $data['ifu'] ?? null,
                    'token_valid_until' => $data['tokenValid'] ?? null,
                ];
            }
            
            // Messages d'erreur détaillés selon le code HTTP
            $errorMessage = match($response->status()) {
                401 => 'Token invalide ou expiré. Veuillez vérifier votre token API ou en générer un nouveau.',
                403 => 'Accès refusé. Votre compte n\'a peut-être pas les permissions requises.',
                404 => 'Endpoint non trouvé. L\'URL de l\'API pourrait avoir changé.',
                500 => 'Erreur serveur e-MCeF. Veuillez réessayer plus tard.',
                502, 503, 504 => 'Service e-MCeF temporairement indisponible. Réessayez dans quelques minutes.',
                default => 'Erreur de connexion (Code HTTP: ' . $response->status() . ')',
            };
            
            // Essayer d'extraire le message d'erreur de la réponse
            $responseBody = $response->json();
            if (!empty($responseBody['message'])) {
                $errorMessage .= ' - ' . $responseBody['message'];
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $response->status(),
                'response' => $responseBody,
                'help' => 'Vérifiez que votre token est bien copié depuis https://developper.impots.bj et qu\'il n\'a pas expiré.',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'error' => 'Impossible de se connecter au serveur e-MCeF. Vérifiez votre connexion internet.',
                'help' => 'URL testée: ' . $baseUrl,
            ];
        } catch (\Exception $e) {
            Log::error('e-MCeF Test Connection Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }
}
