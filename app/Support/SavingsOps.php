<?php

namespace App\Support;

use App\Models\CashSession;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SavingsTransaction;

/**
 * Exécution centralisée des opérations d'épargne (dépôt / retrait) :
 * rattachement à la session de caisse ouverte si espèces + recalcul du tiroir.
 * Au tout premier dépôt d'un client, des frais de tenue de compte uniques
 * sont prélevés EN PLUS (revenu entreprise, hors solde d'épargne).
 */
class SavingsOps
{
    public static function deposit(Customer $customer, int $amount, string $method = 'cash', ?string $notes = null): SavingsTransaction
    {
        $meta = ['payment_method' => $method, 'notes' => $notes];

        $session = $method === 'cash'
            ? CashSession::getOpenSession($customer->company_id, auth()->id())
            : null;

        if ($session) {
            $meta['cash_session_id'] = $session->id;
        }

        // Premier dépôt ? (aucun dépôt antérieur pour ce client)
        $isFirstDeposit = ! SavingsTransaction::withoutGlobalScopes()
            ->where('customer_id', $customer->getKey())
            ->where('type', 'deposit')
            ->exists();

        $tx = $customer->deposit($amount, $meta);

        if ($isFirstDeposit) {
            self::chargeAccountFee($customer, $method, $session?->id);
        }

        $session?->recalculate();

        return $tx;
    }

    public static function withdraw(Customer $customer, int $amount, ?string $notes = null): SavingsTransaction
    {
        $meta = ['payment_method' => 'cash', 'notes' => $notes];

        $session = CashSession::getOpenSession($customer->company_id, auth()->id());
        if ($session) {
            $meta['cash_session_id'] = $session->id;
        }

        $tx = $customer->withdraw($amount, $meta);

        $session?->recalculate();

        return $tx;
    }

    /**
     * Frais de tenue de compte uniques, prélevés une seule fois (au 1er dépôt).
     */
    protected static function chargeAccountFee(Customer $customer, string $method, ?int $sessionId): void
    {
        // Sécurité : jamais deux fois.
        $alreadyCharged = SavingsTransaction::withoutGlobalScopes()
            ->where('customer_id', $customer->getKey())
            ->where('type', 'fee')
            ->exists();

        if ($alreadyCharged) {
            return;
        }

        $fee = (int) (Company::find($customer->company_id)?->tontine_fee ?? 2000);

        if ($fee <= 0) {
            return;
        }

        $meta = [
            'payment_method' => $method,
            'notes' => 'Frais de tenue de compte unique',
            'reference' => 'FEE-' . $customer->getKey(),
        ];

        if ($sessionId) {
            $meta['cash_session_id'] = $sessionId;
        }

        $customer->chargeAccountFee($fee, $meta);
    }
}
