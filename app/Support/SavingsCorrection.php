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
     * Un mouvement déjà annulé/corrigé (marqué reversed_at) ne peut plus l'être.
     */
    public static function isAdjusted(SavingsTransaction $tx): bool
    {
        return $tx->reversed_at !== null;
    }

    public static function isCorrectable(SavingsTransaction $tx): bool
    {
        return in_array($tx->type, self::CORRECTABLE, true) && $tx->reversed_at === null;
    }

    /**
     * Annule entièrement un mouvement : écriture d'ajustement de sens inverse,
     * et marque l'original comme annulé (exclu des totaux de collecte).
     */
    public static function reverse(SavingsTransaction $tx, ?string $reason = null): SavingsTransaction
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($tx->customer_id);

        $meta = [
            'payment_method' => $tx->payment_method,
            'reference' => 'ANNUL-' . $tx->id,
            'notes' => 'Annulation du mouvement #' . $tx->id . ($reason ? ' — ' . $reason : ''),
        ];

        $adjustment = self::apply($customer, -(int) $tx->amount, $tx, $meta);

        $tx->reversed_at = now();
        $tx->saveQuietly();

        return $adjustment;
    }

    /**
     * Corrige le montant : contre-passe l'original (marqué annulé) et recrée un
     * mouvement « vivant » au bon montant, en conservant l'auteur et la date
     * d'origine (pour une collecte juste). $newMagnitude est un montant positif.
     */
    public static function correctAmount(SavingsTransaction $tx, int $newMagnitude, ?string $reason = null): SavingsTransaction
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($tx->customer_id);

        $direction = (int) $tx->amount >= 0 ? 1 : -1;
        $correctSigned = $direction * abs($newMagnitude);

        if ($correctSigned === (int) $tx->amount) {
            throw new \RuntimeException('Le montant corrigé est identique au montant actuel.');
        }

        // 1) Contre-passer l'original + le marquer annulé.
        self::apply($customer, -(int) $tx->amount, $tx, [
            'payment_method' => $tx->payment_method,
            'reference' => 'CORR-' . $tx->id,
            'notes' => 'Correction du mouvement #' . $tx->id . ' : '
                . number_format(abs((int) $tx->amount), 0, ',', ' ') . ' → '
                . number_format(abs($newMagnitude), 0, ',', ' ')
                . ($reason ? ' — ' . $reason : ''),
        ]);
        $tx->reversed_at = now();
        $tx->saveQuietly();

        // 2) Recréer le mouvement au bon montant (type, auteur et date d'origine).
        $session = $tx->payment_method === 'cash'
            ? CashSession::getOpenSession($customer->company_id, auth()->id())
            : null;

        $replMeta = [
            'payment_method' => $tx->payment_method,
            'reference' => 'CORR-NEW-' . $tx->id,
            'notes' => 'Montant corrigé (réf. #' . $tx->id . ')',
        ];
        if ($session) {
            $replMeta['cash_session_id'] = $session->id;
        }

        $replacement = $tx->type === 'withdrawal'
            ? $customer->withdraw(abs($newMagnitude), $replMeta)
            : $customer->deposit(abs($newMagnitude), $replMeta);

        // Conserver l'attribution et la période du mouvement d'origine.
        $replacement->created_at = $tx->created_at;
        $replacement->created_by = $tx->created_by;
        $replacement->saveQuietly();

        $session?->recalculate();

        return $replacement;
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
