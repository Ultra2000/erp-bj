<?php

namespace App\Observers;

use App\Jobs\CertifyInvoiceEmcef;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    /**
     * Handle the Sale "creating" event.
     * Appliquer l'AIB avant la création
     */
    public function creating(Sale $sale): void
    {
        $this->applyAibIfNeeded($sale);
    }

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        // Soumettre à e-MCeF si la vente est complète et l'entreprise a e-MCeF activé
        $this->submitToEmcefIfNeeded($sale);
    }

    /**
     * Handle the Sale "updating" event.
     * Recalculer l'AIB si le client ou le total change
     */
    public function updating(Sale $sale): void
    {
        // Recalculer l'AIB si le client change ou si total_ht change
        if ($sale->isDirty(['customer_id', 'total_ht'])) {
            $this->applyAibIfNeeded($sale);
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Si le statut passe à "completed", soumettre à e-MCeF
        if ($sale->wasChanged('status') && $sale->status === 'completed') {
            $this->submitToEmcefIfNeeded($sale);
        }
    }

    /**
     * Applique l'AIB automatiquement si le mode est "auto"
     */
    protected function applyAibIfNeeded(Sale $sale): void
    {
        // Ne pas recalculer si exonération manuelle
        if ($sale->aib_exempt) {
            $sale->aib_rate = null;
            $sale->aib_amount = 0;
            return;
        }

        $company = $sale->company ?? \App\Models\Company::find($sale->company_id);
        
        if (!$company) {
            return;
        }

        // Appliquer AIB selon le mode
        if ($company->aib_mode === 'auto') {
            $sale->aib_rate = $sale->determineAibRate();
            $sale->aib_amount = $sale->calculateAibAmount();
        } elseif ($company->aib_mode === 'disabled') {
            $sale->aib_rate = null;
            $sale->aib_amount = 0;
        }
        // En mode 'manual', on laisse la valeur saisie par l'utilisateur
    }

    /**
     * Soumet la facture à e-MCeF si nécessaire
     */
    protected function submitToEmcefIfNeeded(Sale $sale): void
    {
        // Vérifier que la vente est complète
        if ($sale->status !== 'completed') {
            return;
        }

        // Vérifier que l'entreprise existe et a e-MCeF activé
        $company = $sale->company;
        
        if (!$company || !$company->emcef_enabled) {
            return;
        }

        // Vérifier que la facture n'est pas déjà certifiée ou en cours
        if (in_array($sale->emcef_status, ['certified', 'submitted'])) {
            return;
        }

        // Vérifier que le total est calculé
        if (!$sale->total || $sale->total <= 0) {
            Log::info('SaleObserver: Skipping e-MCeF submission - total not set', [
                'sale_id' => $sale->id,
            ]);
            return;
        }

        // Dispatcher le job de certification
        Log::info('SaleObserver: Dispatching e-MCeF certification job', [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
        ]);

        // Dispatcher le job avec un délai de 2 secondes pour s'assurer que les items sont bien enregistrés
        CertifyInvoiceEmcef::dispatch($sale)->delay(now()->addSeconds(2));
    }
}
