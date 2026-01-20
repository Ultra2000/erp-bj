<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\EmcefService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CertifyInvoiceEmcef implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute entre les tentatives

    protected Sale $sale;

    /**
     * Create a new job instance.
     */
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Vérifier que la vente existe toujours
        if (!$this->sale->exists) {
            Log::warning('CertifyInvoiceEmcef: Sale no longer exists', ['sale_id' => $this->sale->id]);
            return;
        }

        // Vérifier que l'entreprise a e-MCeF activé
        $company = $this->sale->company;
        
        if (!$company || !$company->emcef_enabled) {
            Log::info('CertifyInvoiceEmcef: e-MCeF not enabled for company', [
                'sale_id' => $this->sale->id,
                'company_id' => $company?->id,
            ]);
            return;
        }

        // Vérifier que la facture n'est pas déjà certifiée
        if ($this->sale->emcef_status === 'certified') {
            Log::info('CertifyInvoiceEmcef: Invoice already certified', [
                'sale_id' => $this->sale->id,
                'emcef_uid' => $this->sale->emcef_uid,
            ]);
            return;
        }

        // Créer le service et soumettre la facture
        $emcefService = new EmcefService($company);
        
        $result = $emcefService->submitInvoice($this->sale);
        
        if ($result['success']) {
            Log::info('CertifyInvoiceEmcef: Invoice certified successfully', [
                'sale_id' => $this->sale->id,
                'invoice_number' => $this->sale->invoice_number,
                'emcef_nim' => $this->sale->fresh()->emcef_nim,
                'emcef_code' => $this->sale->fresh()->emcef_code_mecef,
            ]);
        } else {
            Log::error('CertifyInvoiceEmcef: Failed to certify invoice', [
                'sale_id' => $this->sale->id,
                'invoice_number' => $this->sale->invoice_number,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            
            // Si c'est la dernière tentative, marquer comme erreur définitive
            if ($this->attempts() >= $this->tries) {
                $this->sale->update([
                    'emcef_status' => 'error',
                    'emcef_error' => $result['error'] ?? 'Échec après plusieurs tentatives',
                ]);
            } else {
                // Relancer le job avec délai
                throw new \Exception($result['error'] ?? 'e-MCeF certification failed');
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CertifyInvoiceEmcef: Job failed permanently', [
            'sale_id' => $this->sale->id,
            'invoice_number' => $this->sale->invoice_number,
            'error' => $exception->getMessage(),
        ]);

        $this->sale->update([
            'emcef_status' => 'error',
            'emcef_error' => 'Échec définitif : ' . $exception->getMessage(),
        ]);
    }
}
