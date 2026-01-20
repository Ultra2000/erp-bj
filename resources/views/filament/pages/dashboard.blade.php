<x-filament-panels::page>
    {{-- Filtre d'entrepôt pour les admins --}}
    @if(!auth()->user()?->hasWarehouseRestriction())
    <div class="mb-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-building-storefront class="h-5 w-5 text-gray-500" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vue par entrepôt :</span>
                </div>
                <div class="w-64">
                    {{ $this->form }}
                </div>
                @if($this->selectedWarehouse)
                    <x-filament::badge color="info">
                        Filtre actif
                    </x-filament::badge>
                @else
                    <x-filament::badge color="gray">
                        Tous les entrepôts
                    </x-filament::badge>
                @endif
            </div>
        </div>
    </div>
    @endif

    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="2"
    />
</x-filament-panels::page> 