<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\Purchase;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updatePayable($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $this->updatePayable($payment);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->updatePayable($payment);
    }

    /**
     * Met à jour le statut et le montant payé du parent (Sale ou Purchase)
     */
    protected function updatePayable(Payment $payment): void
    {
        $payable = $payment->payable;

        if (!$payable) {
            return;
        }

        // Recalculer le total payé
        $totalPaid = $payable->payments()->sum('amount');
        
        // Mettre à jour le montant payé
        $payable->amount_paid = $totalPaid;

        // Déterminer le nouveau statut de paiement
        if ($totalPaid >= $payable->total) {
            $payable->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $payable->payment_status = 'partial';
        } else {
            $payable->payment_status = 'pending';
        }

        // Sauvegarder (sans déclencher d'événements inutiles si rien ne change)
        if ($payable->isDirty(['amount_paid', 'payment_status'])) {
            $payable->saveQuietly(); 
        }
    }
}
