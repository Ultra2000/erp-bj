<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $sale->invoice_number }}</title>
    @if(!empty($previewMode))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            color: #333;
            background: #fff;
            line-height: 1.5;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Header */
        .invoice-header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-info h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .company-info .subtitle {
            font-size: 12px;
            color: #666;
        }

        .company-details {
            margin-top: 12px;
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }

        .invoice-meta {
            text-align: right;
            min-width: 200px;
        }

        .invoice-number {
            font-size: 28px;
            font-weight: 700;
        }

        .invoice-number span {
            font-size: 13px;
            color: #666;
            font-weight: 400;
            display: block;
            margin-bottom: 4px;
        }

        .invoice-date {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }

        .logo-container {
            margin-bottom: 12px;
        }

        .logo-container img {
            max-height: 50px;
            max-width: 140px;
            object-fit: contain;
        }

        /* Preview Banner */
        .preview-banner {
            border: 1px solid #999;
            padding: 10px 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #555;
        }

        .preview-banner a {
            color: #333;
            font-weight: 600;
            text-decoration: none;
            padding: 6px 14px;
            border: 1px solid #333;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border: 1px solid #333;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 12px;
        }

        .status-completed {
            border-color: #333;
        }

        .status-pending {
            border-color: #999;
            color: #999;
        }

        .status-cancelled {
            border-color: #999;
            color: #999;
            text-decoration: line-through;
        }

        /* Cards Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .info-card {
            border: 1px solid #ccc;
            padding: 16px;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }

        .info-card-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
        }

        .info-card-content h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .info-card-content p {
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }

        /* Table */
        .items-section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #ccc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #333;
            color: #333;
        }

        .items-table thead th:last-child {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #eee;
        }

        .items-table tbody tr:last-child {
            border-bottom: 1px solid #ccc;
        }

        .items-table tbody td {
            padding: 10px 12px;
            font-size: 13px;
        }

        .items-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .product-name {
            font-weight: 500;
        }

        .text-right { text-align: right; }
        .text-muted { color: #777; }

        /* Totals Card */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }

        .totals-card {
            width: 300px;
            border: 1px solid #ccc;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 16px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .totals-row:last-child {
            border-bottom: none;
        }

        .totals-row .label {
            color: #555;
        }

        .totals-row .value {
            font-weight: 500;
        }

        .totals-row.discount .value {
            color: #555;
        }

        .totals-row.grand-total {
            border-top: 2px solid #333;
            padding: 12px 16px;
            font-weight: bold;
        }

        .totals-row.grand-total .label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
        }

        .totals-row.grand-total .value {
            font-size: 18px;
            font-weight: 700;
        }

        .amount-words {
            padding: 8px 16px;
            font-size: 11px;
            font-style: italic;
            color: #777;
            border-top: 1px dashed #ccc;
        }

        /* QR Verification */
        .verification-section {
            border: 1px solid #ccc;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 24px;
        }

        .qr-code {
            flex-shrink: 0;
        }

        .qr-code img {
            display: block;
            width: 80px;
            height: 80px;
        }

        .verification-info h4 {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .verification-info p {
            font-size: 11px;
            color: #555;
            line-height: 1.6;
        }

        .verification-code {
            display: inline-block;
            font-family: monospace;
            border: 1px solid #333;
            padding: 3px 8px;
            font-size: 11px;
            margin-top: 4px;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 16px;
            border-top: 1px solid #ccc;
            color: #777;
            font-size: 11px;
            line-height: 1.6;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #333;
            background: #fff;
            color: #333;
        }

        .btn-primary {
            background: #333;
            color: #fff;
        }

        .btn-primary:hover {
            background: #555;
        }

        .btn-secondary:hover {
            background: #f5f5f5;
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .invoice-container { padding: 20px; max-width: none; }
        }
    </style>
</head>
<body>
@php
    $currency = $company->currency ?? 'XOF';
    $status = $sale->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $sale->discount_percent ?? 0;

    // Vérifier si l'entreprise est en franchise de TVA
    $isVatFranchise = \App\Models\AccountingSetting::isVatFranchise($company->id);

    // Utiliser les valeurs TVA calculées par ligne et stockées dans Sale
    $totalHt = $sale->total_ht ?? $sale->items->sum('total_price_ht');
    $totalVat = $isVatFranchise ? 0 : ($sale->total_vat ?? $sale->items->sum('vat_amount'));
    $grandTotal = $isVatFranchise ? $totalHt : ($sale->total ?? ($totalHt + $totalVat));

    // Déterminer le groupe de taxe à partir du taux TVA (convention DGI Bénin)
    // Groupes e-MCeF valides : A, B, C, D, E, F
    $validEmcefGroups = ['A', 'B', 'C', 'D', 'E', 'F'];
    $getTaxGroupLabel = function(float $vatRate, ?string $vatCategory = null) use ($validEmcefGroups): string {
        if ($vatCategory && in_array(strtoupper($vatCategory), $validEmcefGroups)) {
            return strtoupper($vatCategory);
        }
        return match (true) {
            $vatRate >= 18 => 'A',
            $vatRate == 0 => 'B',
            default => 'A',
        };
    };

    // Ventilation TVA par taux (pour factures avec taux mixtes)
    $vatBreakdown = [];
    $totalTaxSpecific = 0; // Total taxe spécifique (affiché séparément)
    $taxSpecificLabel = null; // Libellé personnalisé (ex: Taxe de séjour)
    if (!$isVatFranchise) {
        foreach ($sale->items as $item) {
            // TVA classique (toujours)
            $rate = number_format($item->vat_rate ?? 0, 1);
            $group = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category);
            if (!isset($vatBreakdown[$rate])) {
                $vatBreakdown[$rate] = ['base_ht' => 0, 'vat_amount' => 0, 'group' => $group];
            }
            $vatBreakdown[$rate]['base_ht'] += $item->total_price_ht ?? 0;
            $vatBreakdown[$rate]['vat_amount'] += $item->vat_amount ?? 0;
            
            // Taxe spécifique (cumulée séparément)
            if ($item->tax_specific_amount > 0) {
                $totalTaxSpecific += $item->tax_specific_total ?? round($item->tax_specific_amount * $item->quantity, 2);
                if (!$taxSpecificLabel && $item->tax_specific_label) {
                    $taxSpecificLabel = $item->tax_specific_label;
                }
            }
        }
        ksort($vatBreakdown);
    }
    $hasMixedRates = count($vatBreakdown) > 1 || $totalTaxSpecific > 0;

    // Vérifier si e-MCeF est activé (pour afficher les groupes de taxe DGI)
    $isEmcefEnabled = $company->emcef_enabled ?? false;

    // Calculer la remise si présente
    $totalAvantRemise = $sale->items->sum('total_price');
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);

    // Calculer les économies prix de gros
    $wholesaleSavings = $sale->items->sum(function($item) {
        if ($item->is_wholesale && $item->retail_unit_price) {
            return ($item->retail_unit_price - $item->unit_price) * $item->quantity;
        }
        return 0;
    });

    function amountToWordsFr($number, $currency = 'EUR') {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        $euros = floor($number);
        $centimes = round(($number - $euros) * 100);

        $units = [
            'EUR' => ['euro', 'euros', 'centime', 'centimes'],
            'FCFA' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'XOF' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'USD' => ['dollar', 'dollars', 'cent', 'cents'],
            'GBP' => ['livre sterling', 'livres sterling', 'penny', 'pence'],
        ];
        $u = $units[$currency] ?? ['unité', 'unités', 'centime', 'centimes'];

        $euroWord = $euros == 1 ? $u[0] : $u[1];
        $centimeWord = $centimes == 1 ? $u[2] : $u[3];

        $text = ucfirst($fmt->format($euros)) . ' ' . $euroWord;
        if ($centimes > 0) {
            $text .= ' et ' . $fmt->format($centimes) . ' ' . $centimeWord;
        }
        return $text;
    }

    $currencyNames = [
        'EUR' => 'euros',
        'FCFA' => 'francs CFA',
        'XOF' => 'francs CFA',
        'USD' => 'dollars',
        'GBP' => 'livres sterling',
    ];
    $currencyInWords = $currencyNames[$currency] ?? $currency;

    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];

    // Déterminer si la facture est certifiée EMCEF
    $isEmcefCertified = ($sale->emcef_status === 'certified' && $sale->emcef_qr_code);
@endphp

<div class="invoice-container">
    @if(!empty($previewMode))
        <div class="preview-banner no-print">
            <span>Mode prévisualisation — Cette facture n'est pas finalisée</span>
            <a href="{{ route('sales.invoice', $sale) }}">Télécharger PDF</a>
        </div>
    @endif

    <header class="invoice-header">
        <div class="company-info">
            @if($company->logo_path)
                <div class="logo-container">
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}">
                </div>
            @endif
            <h1>{{ $company->name ?: 'Votre Entreprise' }}</h1>
            <p class="subtitle">{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture de vente' }}</p>
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone)Tél: {{ $company->phone }}@endif
                @if($company->email) | {{ $company->email }}@endif
                @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
            </div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number">
                <span>{{ $sale->type === 'credit_note' ? 'Avoir N°' : 'Facture N°' }}</span>
                {{ $sale->invoice_number }}
            </div>
            <div class="invoice-date">
                {{ $sale->created_at->format('d/m/Y') }}
            </div>
            <div>
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </div>
        </div>
    </header>

    {{-- Référence facture d'origine pour les avoirs (exigence DGI) --}}
    @if($sale->type === 'credit_note' && $sale->parent)
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            <strong style="color:#856404;">Avoir relatif à la facture N° {{ $sale->parent->invoice_number }} du {{ $sale->parent->created_at->format('d/m/Y') }}</strong>
            <div style="margin-top:6px;color:#555;">
                <span><strong>Facture d'origine :</strong> {{ $sale->parent->invoice_number }}</span>
                @if($sale->parent->emcef_code_mecef)
                    <span style="margin-left:16px;"><strong>Code MECeF/DGI :</strong> {{ $sale->parent->emcef_code_mecef }}</span>
                @endif
            </div>
        </div>
    @endif

    <div class="info-grid">
        <div class="info-card">
            <div class="info-card-header">
                <span class="info-card-title">Client</span>
            </div>
            <div class="info-card-content">
                <h3>{{ $sale->customer->name ?? 'Client non défini' }}</h3>
                <p>
                    @if(optional($sale->customer)->registration_number)<strong>IFU:</strong> {{ $sale->customer->registration_number }}<br>@endif
                    @if(optional($sale->customer)->siret && optional($sale->customer)->siret !== optional($sale->customer)->registration_number)<strong>SIRET:</strong> {{ $sale->customer->siret }}<br>@endif
                    @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                    @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                    @if(optional($sale->customer)->phone)Tél: {{ $sale->customer->phone }}<br>@endif
                    @if(optional($sale->customer)->email){{ $sale->customer->email }}@endif
                </p>
            </div>
        </div>

        <div class="info-card">
            <div class="info-card-header">
                <span class="info-card-title">Détails</span>
            </div>
            <div class="info-card-content">
                <h3>Informations de paiement</h3>
                <p>
                    Mode: {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}<br>
                    @if($sale->warehouse)Entrepôt: {{ $sale->warehouse->name }}@endif
                </p>
            </div>
        </div>
    </div>

    <div class="items-section">
        <h2 class="section-title">Articles commandés</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 38%">Désignation</th>
                    <th style="width: 10%">Qté</th>
                    <th style="width: 17%" class="text-right">P.U. HT</th>
                    <th style="width: 15%" class="text-right">TVA</th>
                    <th style="width: 20%" class="text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sale->items as $item)
                    <tr>
                        <td>
                            <span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span>
                            @if($item->is_wholesale)
                                <span style="display: inline-block; border: 1px solid #333; font-size: 9px; padding: 1px 4px; margin-left: 4px;">PRIX GROS</span>
                            @endif
                        </td>
                        <td>{{ floatval($item->quantity) == intval($item->quantity) ? intval($item->quantity) : rtrim(rtrim(number_format(floatval($item->quantity), 3, ',', ' '), '0'), ',') }}</td>
                        <td class="text-right text-muted">
                            {{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}
                            @if($item->is_wholesale && $item->retail_unit_price)
                                <br><small style="color: #999; text-decoration: line-through;">{{ number_format($item->retail_unit_price, 2, ',', ' ') }}</small>
                            @endif
                        </td>
                        <td class="text-right">
                            @php $itemGroup = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category); @endphp
                            {{ number_format($item->vat_rate ?? 0, 0) }}%
                            @if($item->tax_specific_amount > 0)
                                <br><small style="color: #666;">+ {{ number_format($item->tax_specific_amount, 0, ',', ' ') }} {{ $currency }}/u</small>
                            @endif
                            @if($isEmcefEnabled)
                                <span style="display:inline-block;border:1px solid #555;font-size:8px;padding:0 3px;margin-left:2px;border-radius:2px;font-weight:bold;">{{ $itemGroup }}</span>
                                @if($item->tax_specific_amount > 0)
                                    <span style="display:inline-block;border:1px solid #e67e22;background:#fef3e2;font-size:8px;padding:0 3px;margin-left:1px;border-radius:2px;font-weight:bold;color:#e67e22;">E</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #999;">
                            Aucun article dans cette facture
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-section">
        <div class="totals-card">
            <div class="totals-row">
                <span class="label">Total HT</span>
                <span class="value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</span>
            </div>
            @if($wholesaleSavings > 0)
                <div class="totals-row">
                    <span class="label">Économie prix de gros</span>
                    <span class="value">- {{ number_format($wholesaleSavings, 0, ',', ' ') }} {{ $currency }}</span>
                </div>
            @endif
            @if($discountPercent > 0)
                <div class="totals-row discount">
                    <span class="label">Remise ({{ number_format($discountPercent, 1, ',', ' ') }}%)</span>
                    <span class="value">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            @endif
            @if($isVatFranchise)
                <div class="totals-row">
                    <span class="label">TVA</span>
                    <span class="value" style="color: #999;">Non applicable</span>
                </div>
            @elseif($hasMixedRates)
                @foreach($vatBreakdown as $rate => $amounts)
                <div class="totals-row">
                    <span class="label">TVA {{ $rate }}%@if($isEmcefEnabled && !empty($amounts['group'])) — Groupe {{ $amounts['group'] }}@endif (base {{ number_format($amounts['base_ht'], 2, ',', ' ') }})</span>
                    <span class="value">{{ number_format($amounts['vat_amount'], 2, ',', ' ') }} {{ $currency }}</span>
                </div>
                @endforeach
                @if($totalTaxSpecific > 0)
                <div class="totals-row">
                    <span class="label">{{ $taxSpecificLabel ?? 'Taxe spécifique' }}{{ $isEmcefEnabled ? ' — Groupe E' : '' }}</span>
                    <span class="value">{{ number_format($totalTaxSpecific, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
                @endif
            @else
                @php $singleGroup = count($vatBreakdown) ? (reset($vatBreakdown)['group'] ?? null) : null; @endphp
                @php $singleRate = count($vatBreakdown) ? array_key_first($vatBreakdown) : '0'; @endphp
                <div class="totals-row">
                    <span class="label">TVA ({{ $singleRate }}%@if($isEmcefEnabled && $singleGroup) — Groupe {{ $singleGroup }}@endif)</span>
                    <span class="value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
                @if($totalTaxSpecific > 0)
                <div class="totals-row">
                    <span class="label">{{ $taxSpecificLabel ?? 'Taxe spécifique' }}{{ $isEmcefEnabled ? ' — Groupe E' : '' }}</span>
                    <span class="value">{{ number_format($totalTaxSpecific, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
                @endif
            @endif
            <div class="totals-row grand-total">
                <span class="label">Total {{ $isVatFranchise ? 'Net' : 'TTC' }}</span>
                <span class="value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</span>
            </div>
            {{-- AIB (Acompte sur Impôt Bénéfices) - Bénin --}}
            @if($sale->aib_rate && $sale->aib_amount > 0)
                <div class="totals-row">
                    <span class="label">
                        AIB {{ $sale->aib_rate === 'A' ? '(1%)' : '(5%)' }}
                        <small style="display:block;font-size:9px;color:#999;">Acompte sur Impôt Bénéfices</small>
                    </span>
                    <span class="value">{{ number_format($sale->aib_amount, 0, ',', ' ') }} {{ $currency }}</span>
                </div>
                <div class="totals-row grand-total">
                    <span class="label">NET À PAYER</span>
                    <span class="value">{{ number_format($grandTotal + $sale->aib_amount, 0, ',', ' ') }} {{ $currency }}</span>
                </div>
            @endif
            <div class="amount-words">
                {{ amountToWordsFr($sale->aib_amount > 0 ? ($grandTotal + $sale->aib_amount) : $grandTotal, $currency) }}
            </div>
        </div>
    </div>

    <!-- QR VERIFICATION (App) - Masqué si facture certifiée EMCEF -->
    @if(!$isEmcefCertified && !empty($verificationUrl) && !empty($verificationCode))
        <div class="verification-section">
            <div class="qr-code">
                @php
                    try {
                        $qr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate($verificationUrl));
                    } catch (\Throwable $e) { $qr = null; }
                @endphp
                @if($qr)
                    <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
                @else
                    <div style="width:80px;height:80px;"></div>
                @endif
            </div>
            <div class="verification-info">
                <h4>Vérification d'authenticité</h4>
                <p>
                    Scannez le QR code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce document.<br>
                    <span style="word-break:break-all;font-size:10px;">{{ $verificationUrl }}</span>
                </p>
                <span class="verification-code">{{ $verificationCode }}</span>
            </div>
        </div>
    @endif

    {{-- Section e-MCeF (Certification DGI Bénin) --}}
    @if($isEmcefCertified)
        <div class="verification-section">
            <div class="qr-code">
                @php
                    try {
                        $emcefQr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate($sale->emcef_qr_code));
                    } catch (\Throwable $e) {
                        $emcefQr = null;
                    }
                @endphp
                @if($emcefQr)
                    <img src="data:image/svg+xml;base64,{{ $emcefQr }}" alt="QR Code e-MCeF" style="width: 80px; height: 80px;">
                @else
                    <div style="width:80px;height:80px;"></div>
                @endif
            </div>
            <div class="verification-info">
                <h4>Facture certifiée DGI Bénin</h4>
                <p>
                    Cette facture a été certifiée conformément à la réglementation fiscale béninoise (e-MCeF).<br>
                    <strong>NIM :</strong> {{ $sale->emcef_nim }}<br>
                    <strong>Code MECeF :</strong> {{ $sale->emcef_code_mecef }}<br>
                    <strong>Date de certification :</strong> {{ $sale->emcef_certified_at?->format('d/m/Y à H:i') }}
                </p>
                @if($sale->emcef_counters)
                    <span class="verification-code">{{ $sale->emcef_counters }}</span>
                @endif
            </div>
        </div>
    @elseif($sale->emcef_status === 'error')
        <div class="verification-section">
            <div class="verification-info" style="width: 100%;">
                <h4>Certification e-MCeF en erreur</h4>
                <p>
                    Cette facture n'a pas pu être certifiée par la DGI.<br>
                    <strong>Erreur :</strong> {{ $sale->emcef_error ?? 'Erreur inconnue' }}
                </p>
            </div>
        </div>
    @elseif($company->emcef_enabled && $sale->emcef_status === 'pending')
        <div class="verification-section">
            <div class="verification-info" style="width: 100%;">
                <h4>Certification e-MCeF en cours</h4>
                <p>
                    Cette facture est en attente de certification par la DGI Bénin.
                </p>
            </div>
        </div>
    @endif

    <footer class="invoice-footer">
        @if($isVatFranchise)
            <p style="font-weight: 600; margin-bottom: 6px;"><strong>Exonéré de TVA</strong></p>
        @endif
        @if($company->footer_text)
            {{ $company->footer_text }}
        @else
            Merci pour votre confiance<br>
            {{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
        @endif
    </footer>

    <div class="actions no-print">
        @if(empty($previewMode))
            <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
        @else
            <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-primary">Télécharger PDF</a>
            <button onclick="window.print()" class="btn btn-secondary">Imprimer</button>
        @endif
    </div>

    {{-- Accordion XML Preview (only in preview mode) --}}
    @if(!empty($previewMode) && !empty($facturxXml))
        <div style="margin-top: 24px; border: 1px solid #ccc;" class="no-print">
            <button style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: #f5f5f5; border: none; cursor: pointer; font-size: 13px; font-weight: 500; text-align: left;" onclick="document.getElementById('xmlContent').classList.toggle('open'); document.getElementById('xmlChevron').classList.toggle('open');">
                <span>Factur-X XML intégré (CII)</span>
                <span style="margin-left: auto; font-size: 11px; border: 1px solid #333; padding: 2px 8px;">PDF/A-3</span>
                <span id="xmlChevron" style="transition: transform 0.3s;">▼</span>
            </button>
            <div id="xmlContent" style="display: none; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 16px; border-bottom: 1px solid #eee;">
                    <span style="font-size: 11px; color: #666;">Profil: BASIC | Norme: Factur-X / ZUGFeRD 2.1</span>
                    <button onclick="navigator.clipboard.writeText(document.getElementById('xmlCode').textContent)" style="background: #333; color: white; border: none; padding: 6px 12px; font-size: 11px; cursor: pointer;">Copier XML</button>
                </div>
                <pre id="xmlCode" style="margin: 0; padding: 16px; font-family: monospace; font-size: 11px; line-height: 1.5; overflow-x: auto; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;"><code>{{ $facturxXml }}</code></pre>
            </div>
        </div>
        <style>
            #xmlContent.open { display: block !important; }
            #xmlChevron.open { transform: rotate(180deg); }
        </style>
    @endif
</div>
</body>
</html>
