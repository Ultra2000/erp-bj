<?php

namespace App\Support;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\SavingsTransaction;

/**
 * Exécution centralisée des opérations d'épargne (dépôt / retrait) :
 * rattachement à la session de caisse ouverte si espèces + recalcul du tiroir.
 */
class SavingsOps
{
    public static function deposit(Customer $customer, int $amount, string $method = 'cash', ?string $notes = null): SavingsTransaction
    {
        return self::run($customer, 'deposit', $amount, $method, $notes);
    }

    public static function withdraw(Customer $customer, int $amount, ?string $notes = null): SavingsTransaction
    {
        return self::run($customer, 'withdraw', $amount, 'cash', $notes);
    }

    protected static function run(Customer $customer, string $op, int $amount, string $method, ?string $notes): SavingsTransaction
    {
        $meta = ['payment_method' => $method, 'notes' => $notes];

        $session = $method === 'cash'
            ? CashSession::getOpenSession($customer->company_id, auth()->id())
            : null;

        if ($session) {
            $meta['cash_session_id'] = $session->id;
        }

        $tx = $op === 'withdraw'
            ? $customer->withdraw($amount, $meta)
            : $customer->deposit($amount, $meta);

        $session?->recalculate();

        return $tx;
    }
}
