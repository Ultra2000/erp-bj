<x-filament-panels::page>
    @php $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency; @endphp

    {{-- Sélecteur de période --}}
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Du</label>
                <input type="date" wire:model.live="start_date"
                       class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Au</label>
                <input type="date" wire:model.live="end_date"
                       class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm">
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $total_ops }} opération(s) sur la période</div>
        </div>
    </x-filament::section>

    {{-- Cartes synthèse (période) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Dépôts collectés</div>
            <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">+ {{ $fmt($deposited) }}</div>
            <div class="text-xs text-gray-400">{{ number_format($deposited_nb, 0, ',', ' ') }} dépôt(s)</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Retraits</div>
            <div class="mt-1 text-2xl font-black text-red-600 dark:text-red-400">- {{ $fmt($withdrawn) }}</div>
            <div class="text-xs text-gray-400">{{ number_format($withdrawn_nb, 0, ',', ' ') }} retrait(s)</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Utilisés (achats)</div>
            <div class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">- {{ $fmt($used) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Frais de tenue</div>
            <div class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ $fmt($fees) }}</div>
            <div class="text-xs text-gray-400">{{ number_format($fees_nb, 0, ',', ' ') }} compte(s)</div>
        </x-filament::section>
    </div>

    {{-- Par tontinier --}}
    <x-filament::section>
        <x-slot name="heading">Collecte par tontinier (période)</x-slot>
        @if(count($agentRows))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-2">Tontinier</th><th class="py-2 pr-2 text-center">Dépôts</th><th class="py-2 text-right">Collecté</th>
                    </tr></thead>
                    <tbody>
                        @foreach($agentRows as $a)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">{{ $a['name'] }}</td>
                                <td class="py-2 pr-2 text-center">{{ number_format($a['nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ $fmt($a['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune collecte sur cette période.</p>
        @endif
    </x-filament::section>

    {{-- Par zone --}}
    <x-filament::section>
        <x-slot name="heading">Collecte par zone (période)</x-slot>
        @if(count($zoneRows))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-2">Zone</th><th class="py-2 pr-2 text-center">Dépôts</th><th class="py-2 text-right">Collecté</th>
                    </tr></thead>
                    <tbody>
                        @foreach($zoneRows as $z)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">{{ $z['name'] }}</td>
                                <td class="py-2 pr-2 text-center">{{ number_format($z['nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmt($z['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune collecte par zone sur cette période.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
