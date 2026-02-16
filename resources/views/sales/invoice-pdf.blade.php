<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture' }} {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0; /* On gère les marges via le body pour plus de contrôle */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
            padding: 15mm 20mm; /* 1.5cm haut/bas, 2cm gauche/droite */
            margin: 0;
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-height: 45px;
            max-width: 120px;
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 9px;
            color: #666;
            margin-bottom: 6px;
        }

        .company-details {
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 2px;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
        }

        .invoice-date {
            font-size: 9px;
            color: #666;
            margin-top: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #333;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
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

        /* ===== INFO SECTION ===== */
        .info-section {
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }

        .info-table td:last-child {
            padding: 0 0 0 8px;
        }

        .info-card {
            border: 1px solid #ccc;
            padding: 8px 10px;
        }

        .info-card-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
        }

        .info-card-name {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-card-text {
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        /* ===== ITEMS TABLE ===== */
        .items-section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Force le respect des largeurs de colonnes */
        }

        .items-table thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #333;
            color: #333;
        }

        .items-table tbody td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #ccc;
        }

        .product-name {
            font-weight: 500;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #777; }

        /* ===== TOTALS ===== */
        .totals-section {
            margin-bottom: 15px;
        }

        .totals-wrapper {
            width: 100%;
            border-collapse: collapse;
        }

        .spacer { width: 55%; }

        .totals {
            width: 45%;
            vertical-align: top;
        }

        .totals-card {
            border: 1px solid #ccc;
        }

        .totals-row {
            padding: 4px 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-row-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-label {
            font-size: 8px;
            color: #555;
        }

        .totals-value {
            text-align: right;
            font-size: 9px;
            font-weight: 500;
        }

        .totals-value.discount {
            color: #555;
        }

        .grand-total {
            border-top: 2px solid #333;
            padding: 6px 10px;
        }

        .grand-total .totals-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #333;
        }

        .grand-total .totals-value {
            font-size: 12px;
            font-weight: bold;
        }

        .amount-words {
            padding: 4px 10px;
            font-size: 7px;
            font-style: italic;
            color: #777;
            border-top: 1px dashed #ccc;
        }

        /* ===== NOTES ===== */
        .notes-box {
            border: 1px solid #ccc;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8px;
        }

        .notes-title {
            font-weight: bold;
        }

        /* ===== QR VERIFICATION ===== */
        .verification-section {
            border: 1px solid #ccc;
            padding: 8px;
            margin-bottom: 10px;
        }

        .verification-table {
            width: 100%;
        }

        .qr-cell {
            width: 70px;
            vertical-align: top;
        }

        .qr-box {
            display: inline-block;
        }

        .qr-box img {
            width: 60px;
            height: 60px;
        }

        .verification-info {
            padding-left: 10px;
            vertical-align: middle;
        }

        .verification-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .verification-text {
            font-size: 7px;
            color: #555;
            line-height: 1.4;
        }

        .verification-code {
            display: inline-block;
            font-family: monospace;
            border: 1px solid #333;
            padding: 2px 6px;
            font-size: 8px;
            margin-top: 3px;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            color: #777;
            font-size: 7px;
            line-height: 1.5;
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

    // Calculs TVA
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
    $totalTaxSpecific = 0;
    if (!$isVatFranchise) {
        foreach ($sale->items as $item) {
            $group = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category);
            $rate = number_format($item->vat_rate ?? 0, 1);
            if (!isset($vatBreakdown[$rate])) {
                $vatBreakdown[$rate] = ['base_ht' => 0, 'vat_amount' => 0, 'group' => $group];
            }
            $vatBreakdown[$rate]['base_ht'] += $item->total_price_ht ?? 0;
            $vatBreakdown[$rate]['vat_amount'] += $item->vat_amount ?? 0;
            // Taxe spécifique (Groupe E) — cumulée séparément
            if ($item->tax_specific_amount > 0) {
                $totalTaxSpecific += ($item->tax_specific_total ?? ($item->tax_specific_amount * $item->quantity));
            }
        }
        ksort($vatBreakdown);
    }
    $hasMixedRates = count($vatBreakdown) > 1 || $totalTaxSpecific > 0;

    // Vérifier si e-MCeF est activé (pour afficher les groupes de taxe DGI)
    $isEmcefEnabled = $company->emcef_enabled ?? false;

    $totalAvantRemise = $sale->items->sum('total_price');
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);

    // Fonction montant en lettres
    function amountToWordsFrSalePdf($number, $currency = 'EUR') {
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

    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];

    $invoiceTypeLabel = $sale->type === 'credit_note' ? 'Avoir N°' : 'Facture N°';

    // Déterminer si la facture est certifiée EMCEF
    $isEmcefCertified = ($sale->emcef_status === 'certified' && $sale->emcef_qr_code);
@endphp

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($company->logo_path)
                    <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @endif
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                <div class="company-subtitle">{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture de vente' }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}@endif
                    @if($company->email) | {{ $company->email }}@endif
                    @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
                    @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label">{{ $invoiceTypeLabel }}</div>
                <div class="invoice-number">{{ $sale->invoice_number }}</div>
                <div class="invoice-date">{{ $sale->created_at->format('d/m/Y') }}</div>
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- Référence facture d'origine pour les avoirs (exigence DGI) --}}
@if($sale->type === 'credit_note' && $sale->parent)
<div style="background:#fff3cd;border:1px solid #d4a913;padding:8px 12px;margin-bottom:10px;font-size:10px;">
    <strong>Avoir relatif à la facture N° {{ $sale->parent->invoice_number }} du {{ $sale->parent->created_at->format('d/m/Y') }}</strong><br>
    Facture d'origine : {{ $sale->parent->invoice_number }}
    @if($sale->parent->emcef_code_mecef)
        &nbsp;&mdash;&nbsp;Code MECeF/DGI : {{ $sale->parent->emcef_code_mecef }}
    @endif
</div>
@endif

<!-- INFO CARDS -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Client</div>
                    <div class="info-card-name">{{ $sale->customer->name ?? 'Client non défini' }}</div>
                    <div class="info-card-text">
                        @if(optional($sale->customer)->registration_number)IFU: {{ $sale->customer->registration_number }}<br>@endif
                        @if(optional($sale->customer)->siret && optional($sale->customer)->siret !== optional($sale->customer)->registration_number)SIRET: {{ $sale->customer->siret }}<br>@endif
                        @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                        @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                        @if(optional($sale->customer)->phone)Tel: {{ $sale->customer->phone }}<br>@endif
                        @if(optional($sale->customer)->email){{ $sale->customer->email }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Détails</div>
                    <div class="info-card-name">Informations de paiement</div>
                    <div class="info-card-text">
                        Mode: {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}<br>
                        Référence: {{ $sale->reference ?? $sale->invoice_number }}<br>
                        @if($sale->warehouse)Entrepôt: {{ $sale->warehouse->name }}@endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS TABLE -->
<div class="items-section">
    <div class="section-title">Articles facturés</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 38%;">Désignation</th>
                <th style="width: 10%;" class="text-center">Qté</th>
                <th style="width: 17%;" class="text-right">P.U. HT</th>
                <th style="width: 15%;" class="text-center">TVA</th>
                <th style="width: 20%;" class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sale->items as $item)
                <tr>
                    <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                    <td class="text-center">{{ floatval($item->quantity) == intval($item->quantity) ? intval($item->quantity) : rtrim(rtrim(number_format(floatval($item->quantity), 3, ',', ' '), '0'), ',') }}</td>
                    <td class="text-right text-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    @php $pdfItemGroup = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category); @endphp
                    <td class="text-center">{{ number_format($item->vat_rate ?? 0, 0) }}%@if($item->tax_specific_amount > 0)<br><span style="font-size:7px;">+ {{ number_format($item->tax_specific_amount, 0, ',', ' ') }} {{ $currency }}/u</span>@endif @if($isEmcefEnabled) <span style="border:1px solid #555;font-size:7px;padding:0 2px;font-weight:bold;">{{ $pdfItemGroup }}</span>@if($item->tax_specific_amount > 0) <span style="border:1px solid #e67e22;color:#e67e22;font-size:7px;padding:0 2px;font-weight:bold;">E</span>@endif @endif</td>
                    <td class="text-right">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px; color: #999;">
                        Aucun article dans cette facture
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- TOTALS -->
<div class="totals-section">
    <table class="totals-wrapper">
        <tr>
            <td class="spacer"></td>
            <td class="totals">
                <div class="totals-card">
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Total HT</td>
                                <td class="totals-value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($discountAmount > 0)
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Remise ({{ number_format($discountPercent, 1) }}%)</td>
                                <td class="totals-value discount">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    @if($hasMixedRates)
                        @foreach($vatBreakdown as $rate => $amounts)
                        <div class="totals-row">
                            <table class="totals-row-table">
                                <tr>
                                    <td class="totals-label">TVA {{ $rate }}%@if($isEmcefEnabled && !empty($amounts['group'])) — Groupe {{ $amounts['group'] }}@endif (base {{ number_format($amounts['base_ht'], 2, ',', ' ') }})</td>
                                    <td class="totals-value">{{ number_format($amounts['vat_amount'], 2, ',', ' ') }} {{ $currency }}</td>
                                </tr>
                            </table>
                        </div>
                        @endforeach
                        @if($totalTaxSpecific > 0)
                        <div class="totals-row">
                            <table class="totals-row-table">
                                <tr>
                                    <td class="totals-label">Taxe spécifique{{ $isEmcefEnabled ? ' — Groupe E' : '' }}</td>
                                    <td class="totals-value">{{ number_format($totalTaxSpecific, 2, ',', ' ') }} {{ $currency }}</td>
                                </tr>
                            </table>
                        </div>
                        @endif
                    @else
                    @php $singleGroup = count($vatBreakdown) ? (reset($vatBreakdown)['group'] ?? null) : null; @endphp
                    @php $singleRate = count($vatBreakdown) ? array_key_first($vatBreakdown) : '0'; @endphp
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TVA ({{ $singleRate }}%@if($isEmcefEnabled && $singleGroup) — Groupe {{ $singleGroup }}@endif)</td>
                                <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($totalTaxSpecific > 0)
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Taxe spécifique{{ $isEmcefEnabled ? ' — Groupe E' : '' }}</td>
                                <td class="totals-value">{{ number_format($totalTaxSpecific, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    @endif
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TOTAL TTC</td>
                                <td class="totals-value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($sale->aib_rate && $sale->aib_amount > 0)
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">AIB {{ $sale->aib_rate === 'A' ? '(1%)' : '(5%)' }}<br><span style="font-size:7px;color:#999;">Acompte sur Impôt Bénéfices</span></td>
                                <td class="totals-value">{{ number_format($sale->aib_amount, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">NET À PAYER</td>
                                <td class="totals-value">{{ number_format($grandTotal + $sale->aib_amount, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    <div class="amount-words">
                        {{ amountToWordsFrSalePdf($sale->aib_amount > 0 ? ($grandTotal + $sale->aib_amount) : $grandTotal, $currency) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- NOTES -->
@if($sale->notes)
<div class="notes-box">
    <span class="notes-title">Note:</span> {{ $sale->notes }}
</div>
@endif

<!-- QR VERIFICATION (App) - Masqué si facture certifiée EMCEF -->
@if(!$isEmcefCertified && !empty($verificationUrl) && !empty($verificationCode))
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="qr-cell">
                <div class="qr-box">
                    @php
                        try {
                            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($verificationUrl);
                            $qrBase64 = base64_encode($qrSvg);
                        } catch (\Throwable $e) {
                            $qrBase64 = null;
                        }
                    @endphp
                    @if($qrBase64)
                        <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR Code">
                    @else
                        <div style="width:60px;height:60px;"></div>
                    @endif
                </div>
            </td>
            <td class="verification-info">
                <div class="verification-title">Vérification d'authenticité</div>
                <div class="verification-text">
                    Scannez le QR code ou visitez le lien pour vérifier ce document.<br>
                    <span style="font-size:7px;word-break:break-all;">{{ $verificationUrl }}</span>
                </div>
                <span class="verification-code">{{ $verificationCode }}</span>
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Section e-MCeF (Certification DGI Bénin) --}}
@if($isEmcefCertified)
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="qr-cell">
                <div class="qr-box">
                    @php
                        try {
                            $emcefQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($sale->emcef_qr_code);
                            $emcefQrBase64 = base64_encode($emcefQrSvg);
                        } catch (\Throwable $e) {
                            $emcefQrBase64 = null;
                        }
                    @endphp
                    @if($emcefQrBase64)
                        <img src="data:image/svg+xml;base64,{{ $emcefQrBase64 }}" alt="QR Code e-MCeF">
                    @else
                        <div style="width:60px;height:60px;"></div>
                    @endif
                </div>
            </td>
            <td class="verification-info">
                <div class="verification-title">Facture certifiée DGI Bénin</div>
                <div class="verification-text">
                    NIM : {{ $sale->emcef_nim }}<br>
                    Code MECeF : {{ $sale->emcef_code_mecef }}<br>
                    Certifiée le : {{ $sale->emcef_certified_at?->format('d/m/Y H:i') }}
                </div>
                @if($sale->emcef_counters)
                    <span class="verification-code">{{ $sale->emcef_counters }}</span>
                @endif
            </td>
        </tr>
    </table>
</div>
@elseif(isset($company) && $company->emcef_enabled && $sale->emcef_status === 'pending')
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="verification-info" style="width: 100%;">
                <div class="verification-title">Certification e-MCeF en cours</div>
                <div class="verification-text">
                    Cette facture est en attente de certification par la DGI Bénin.
                </div>
            </td>
        </tr>
    </table>
</div>
@endif

<!-- FOOTER -->
<div class="footer">
    @if($isVatFranchise)
        <strong>Exonéré de TVA</strong><br>
    @endif
    @if($company->footer_text)
        {{ $company->footer_text }}
    @else
        Merci pour votre confiance<br>
        {{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
    @endif
</div>

</body>
</html>
