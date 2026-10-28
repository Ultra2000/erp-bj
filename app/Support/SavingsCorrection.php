<?php

namespace App\Support;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\SavingsTransaction;

/**
 * Correction d'un mouvement d'épargne par contre-passation.
 * On ne supprime jamais une écriture : on crée une écriture d'ajustement
 * qui corrige le solde (et le tiroir de caisse si le mouvement était en espèces).
 */
class SavingsCorrection
{
    /** Types de mouvements corrigeables (saisis manuellement). */
    public const CORRECTABLE = ['deposit', 'withdrawal'];

    /**
     * Un mouvement déjà annulé ou corrigé ne peut plus l'être (évite les doubles).
     */
    public static function isAdjusted(SavingsTransaction $tx): bool
    {
        return SavingsTransaction::withoutGlobalScopes()
            ->whereIn('reference', ['ANNUL-' . $tx->id, 'CORR-' . $tx->id])
            ->exists();
    }

    public static function isCorrectable(SavingsTransaction $tx): bool
    {
        return in_array($tx->type, self::CORRECTABLE, true) && ! self::isAdjusted($tx);
    }

    /**
     * Annule entièrement un mouvement : écriture d'ajustement de sens inverse.
     */
    public static function reverse(SavingsTransaction $tx, ?string $reason = null): SavingsTransaction
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($tx->customer_id);

        $meta = [
            'payment_method' => $tx->payment_method,
            'reference' => 'ANNUL-' . $tx->id,
            'notes' => 'Annulation du mouvement #' . $tx->id . ($reason ? ' — ' . $reason : ''),
        ];

        return self::apply($customer, -(int) $tx->amount, $tx, $meta);
    }

    /**
     * Corrige le montant : crée l'ajustement de la différence, dans le même
     * sens que le mouvement d'origine. $newMagnitude est un montant positif.
     */
    public static function correctAmount(SavingsTransaction $tx, int $newMagnitude, ?string $reason = null): SavingsTransaction
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($tx->customer_id);

        $direction = (int) $tx->amount >= 0 ? 1 : -1;
        $correctSigned = $direction * abs($newMagnitude);
        $delta = $correctSigned - (int) $tx->amount;

        if ($delta === 0) {
            throw new \RuntimeException('Le montant corrigé est identique au montant actuel.');
        }

        $meta = [
            'payment_method' => $tx->payment_method,
            'reference' => 'CORR-' . $tx->id,
            'notes' => 'Correction du mouvement #' . $tx->id . ' : '
                . number_format(abs((int) $tx->amount), 0, ',', ' ') . ' → '
                . number_format(abs($newMagnitude), 0, ',', ' ')
                . ($reason ? ' — ' . $reason : ''),
        ];

        return self::apply($customer, $delta, $tx, $meta);
    }

    /**
     * Applique l'ajustement : rattache la session de caisse ouverte si le
     * mouvement était en espèces, écrit l'ajustement et recalcule la session.
     */
    protected static function apply(Customer $customer, int $signedDelta, SavingsTransaction $tx, array $meta): SavingsTransaction
    {
        $session = $tx->payment_method === 'cash'
            ? CashSession::getOpenSession($customer->company_id, auth()->id())
            : null;

        if ($session) {
            $meta['cash_session_id'] = $session->id;
        }

        $adjustment = $customer->adjust($signedDelta, $meta);

        $session?->recalculate();

        return $adjustment;
    }
}
