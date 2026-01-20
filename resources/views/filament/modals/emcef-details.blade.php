<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Facture</h4>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $sale->invoice_number }}</p>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-4">
            <h4 class="text-sm font-medium text-green-600 dark:text-green-400">Statut</h4>
            <p class="text-lg font-semibold text-green-700 dark:text-green-300">✅ Certifiée</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-shield-check class="w-5 h-5 text-green-600" />
            Informations de certification
        </h3>
        
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                <dt class="text-gray-500 dark:text-gray-400">UID e-MCeF</dt>
                <dd class="font-mono text-gray-900 dark:text-white">{{ $sale->emcef_uid }}</dd>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                <dt class="text-gray-500 dark:text-gray-400">NIM</dt>
                <dd class="font-mono font-semibold text-gray-900 dark:text-white">{{ $sale->emcef_nim }}</dd>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                <dt class="text-gray-500 dark:text-gray-400">Code MECeF DGI</dt>
                <dd class="font-mono font-semibold text-green-600 dark:text-green-400">{{ $sale->emcef_code_mecef }}</dd>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                <dt class="text-gray-500 dark:text-gray-400">Compteurs</dt>
                <dd class="font-mono text-gray-900 dark:text-white">{{ $sale->emcef_counters ?? '-' }}</dd>
            </div>
            
            <div class="flex justify-between py-2">
                <dt class="text-gray-500 dark:text-gray-400">Date de certification</dt>
                <dd class="text-gray-900 dark:text-white">{{ $sale->emcef_certified_at?->format('d/m/Y à H:i:s') }}</dd>
            </div>
        </dl>
    </div>

    @if($sale->emcef_qr_code)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">QR Code de vérification</h4>
            <div class="inline-block bg-white p-3 rounded-lg shadow">
                @php
                    try {
                        $emcefQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(128)->generate($sale->emcef_qr_code);
                        $emcefQrBase64 = base64_encode($emcefQrSvg);
                    } catch (\Throwable $e) {
                        $emcefQrBase64 = null;
                    }
                @endphp
                @if($emcefQrBase64)
                    <img src="data:image/svg+xml;base64,{{ $emcefQrBase64 }}" alt="QR Code e-MCeF" class="w-32 h-32">
                @else
                    <div class="w-32 h-32 bg-gray-100 flex items-center justify-center text-gray-400 text-xs">QR indisponible</div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">Scannez ce QR code pour vérifier l'authenticité</p>
            <p class="text-xs text-gray-500 mt-1 font-mono break-all">{{ $sale->emcef_qr_code }}</p>
        </div>
    @endif

    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-medium">À propos de la certification e-MCeF</p>
                <p class="mt-1 text-blue-600 dark:text-blue-400">
                    Cette facture a été certifiée par le Mécanisme de Certification électronique des Factures (e-MCeF) 
                    de la Direction Générale des Impôts du Bénin. Le NIM et le Code MECeF garantissent l'authenticité 
                    et la traçabilité fiscale de ce document.
                </p>
            </div>
        </div>
    </div>
</div>
