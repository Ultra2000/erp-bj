<x-filament-panels::page>
    @php
        $backups = $this->getBackups();
        $gdrive = $this->getGoogleDriveStatus();
    @endphp

    {{-- Statut Google Drive --}}
    <div class="rounded-xl p-4 ring-1 {{ $gdrive['ok'] ? 'bg-success-50 ring-success-600/20 dark:bg-success-500/10' : 'bg-gray-50 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' }}">
        <div class="flex items-center gap-3">
            <x-filament::icon
                :icon="$gdrive['ok'] ? 'heroicon-o-cloud' : 'heroicon-o-cloud'"
                @class([
                    'h-6 w-6',
                    'text-success-600 dark:text-success-400' => $gdrive['ok'],
                    'text-gray-400' => ! $gdrive['ok'],
                ])
            />
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Google Drive</p>
                <p class="text-xs {{ $gdrive['ok'] ? 'text-success-700 dark:text-success-300' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $gdrive['reason'] }}
                </p>
            </div>
        </div>
    </div>

    {{-- Liste des sauvegardes --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                Sauvegardes disponibles ({{ count($backups) }})
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Stockées dans storage/app/backups sur le serveur.</p>
        </div>

        @if(count($backups) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wider text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="px-6 py-3">Fichier</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3 text-right">Taille</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($backups as $b)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $b['name'] }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $b['date'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-300">{{ $b['size'] }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ $b['url'] }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">
                                        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" />
                                        Télécharger
                                    </a>
                                    <button type="button"
                                            wire:click="deleteBackup('{{ $b['name'] }}')"
                                            wire:confirm="Supprimer définitivement cette sauvegarde ?"
                                            class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-danger-50 hover:text-danger-600 dark:bg-white/5 dark:text-gray-300">
                                        <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <x-filament::icon icon="heroicon-o-circle-stack" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Aucune sauvegarde pour le moment.</p>
                <p class="text-xs text-gray-400">Cliquez sur « Sauvegarder maintenant » pour en créer une.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
