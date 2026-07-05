<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Caissier</p>
            <p class="font-medium">{{ $session->user?->name ?? 'Inconnu' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Statut</p>
            <p class="font-medium">
                @if($session->closed_at)
                    <span class="text-success-600">Fermée</span>
                @else
                    <span class="text-warning-600">En cours</span>
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ouverture</p>
            <p class="font-medium">{{ $session->opened_at->format('d/m/Y à H:i') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Fermeture</p>
            <p class="font-medium">
                {{ $session->closed_at ? $session->closed_at->format('d/m/Y à H:i') : '-' }}
            </p>
        </div>
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    <div class="space-y-2">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Fond de caisse</span>
            <span class="font-medium">{{ number_format($session->opening_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Nombre de tickets</span>
            <span class="font-medium">{{ $session->sales_count ?? 0 }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Total des ventes</span>
            <span class="font-medium text-success-600">{{ number_format($session->total_sales ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Ventilation par mode de paiement</p>
    <div class="space-y-2">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Espèces</span>
            <span class="font-medium">{{ number_format($session->total_cash ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Carte bancaire</span>
            <span class="font-medium">{{ number_format($session->total_card ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Mobile Money</span>
            <span class="font-medium">{{ number_format($session->total_mobile ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        @if(($session->total_other ?? 0) > 0)
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Autre</span>
            <span class="font-medium">{{ number_format($session->total_other, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
    </div>

    @if($session->closed_at)
    <hr class="border-gray-200 dark:border-gray-700">

    @php
        $expectedCash = floatval($session->opening_amount) + floatval($session->total_cash);
        $difference = floatval($session->closing_amount) - $expectedCash;
    @endphp

    <div class="space-y-2">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Attendu en caisse (fond + espèces)</span>
            <span class="font-medium">{{ number_format($expectedCash, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Montant compté à la clôture</span>
            <span class="font-medium">{{ number_format($session->closing_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="flex justify-between {{ $difference == 0 ? 'text-success-600' : ($difference > 0 ? 'text-warning-600' : 'text-danger-600') }}">
            <span class="font-medium">Écart</span>
            <span class="font-bold">{{ ($difference >= 0 ? '+' : '') . number_format($difference, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>
    @endif

    @if($session->notes)
    <hr class="border-gray-200 dark:border-gray-700">
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Notes de clôture</p>
        <p class="mt-1 text-sm">{{ $session->notes }}</p>
    </div>
    @endif
</div>
