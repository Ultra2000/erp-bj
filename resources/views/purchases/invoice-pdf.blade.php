<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture d'achat {{ $purchase->invoice_number }}</title>
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
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
            padding: 15mm 20mm;
            margin: 0;
        }

        /* ===== HEADER ===== */
        /* Bandeau teal léger pour différencier l'achat de la vente (blanche) */
        .header {
            background-color: #f0fdfa;
            border: 1px solid #99f6e4;
            border-left: 4px solid #0f766e;
            padding: 12px 14px;
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
            color: #134e4a;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 9px;
            color: #0f766e;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            color: #0f766e;
            margin-bottom: 2px;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #0f766e;
        }

        .invoice-date {
            font-size: 9px;
            color: #555;
            margin-top: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #0f766e;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .status-completed {
            border-color: #0f766e;
            color: #0f766e;
        }

        .status-pending {
            border-color: #b45309;
            color: #b45309;
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
            border-top: 2px solid #0f766e;
            padding: 8px 10px;
        }

        .info-card-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f766e;
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
            color: #0f766e;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #0f766e;
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
            border-bottom: 2px solid #0f766e;
            color: #134e4a;
        }

        .items-table tbody td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
        }

        .items-table tbody tr:nth-child(even) td {
            background-color: #f7fbfb;
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
            color: #0f766e;
        }

        .grand-total {
            border-top: 2px solid #0f766e;
            padding: 6px 10px;
        }

        .grand-total .totals-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #134e4a;
        }

        .grand-total .totals-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f766e;
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
            border-left: 3px solid #0f766e;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8px;
        }

        .notes-title {
            font-weight: bold;
            color: #0f766e;
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
            color: #134e4a;
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
            border: 1px solid #0f766e;
            color: #0f766e;
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
    $status = $purchase->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $purchase->discount_percent ?? 0;

    // Calculs — toujours depuis les lignes pour fiabilité, avec repli sur les totaux stockés
    $rawTotalHt = $purchase->items->sum('total_price_ht');
    $rawTotalVat = $purchase->items->sum('vat_amount');

    // Ventilation TVA par taux (pour achats avec taux mixtes)
    $vatBreakdown = [];
    foreach ($purchase->items as $item) {
        $rate = number_format($item->vat_rate ?? 0, 1);
        if (!isset($vatBreakdown[$rate])) {
            $vatBreakdown[$rate] = ['base_ht' => 0, 'vat_amount' => 0, 'rate' => $rate];
        }
        $vatBreakdown[$rate]['base_ht'] += $item->total_price_ht ?? 0;
        $vatBreakdown[$rate]['vat_amount'] += $item->vat_amount ?? 0;
    }
    ksort($vatBreakdown);
    $hasMixedRates = count($vatBreakdown) > 1;

    // Remise globale appliquée sur HT + TVA (cohérent avec Purchase::recalculateTotals)
    $discountMultiplier = 1 - ($discountPercent / 100);
    $totalHt = round($rawTotalHt * $discountMultiplier);
    $totalVat = round($rawTotalVat * $discountMultiplier);
    $grandTotal = $purchase->total ?? ($totalHt + $totalVat);

    $totalAvantRemise = $rawTotalHt + $rawTotalVat;
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);

    // Fonction montant en lettres
    function amountToWordsFrPurchasePdf($number, $currency = 'XOF') {
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
        'completed' => 'Terminé',
        'pending' => 'En attente',
        'cancelled' => 'Annulé'
    ];
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
                <div class="company-subtitle">Facture d'achat</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}@endif
                    @if($company->email) | {{ $company->email }}@endif
                    @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
                    @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label">Facture d'achat N°</div>
                <div class="invoice-number">{{ $purchase->invoice_number }}</div>
                <div class="invoice-date">{{ $purchase->created_at->format('d/m/Y à H:i') }}</div>
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- INFO CARDS -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Fournisseur</div>
                    <div class="info-card-name">{{ $purchase->supplier->name ?? 'Fournisseur non défini' }}</div>
                    <div class="info-card-text">
                        @if(optional($purchase->supplier)->registration_number)IFU: {{ $purchase->supplier->registration_number }}<br>@endif
                        @if(optional($purchase->supplier)->siret && optional($purchase->supplier)->siret !== optional($purchase->supplier)->registration_number)SIRET: {{ $purchase->supplier->siret }}<br>@endif
                        @if(optional($purchase->supplier)->address){{ $purchase->supplier->address }}<br>@endif
                        @if(optional($purchase->supplier)->zip_code || optional($purchase->supplier)->city){{ optional($purchase->supplier)->zip_code }} {{ optional($purchase->supplier)->city }}<br>@endif
                        @if(optional($purchase->supplier)->phone)Tel: {{ $purchase->supplier->phone }}<br>@endif
                        @if(optional($purchase->supplier)->email){{ $purchase->supplier->email }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Détails</div>
                    <div class="info-card-name">Informations de paiement</div>
                    <div class="info-card-text">
                        Mode: {{ ucfirst($purchase->payment_method ?? 'Non spécifié') }}<br>
                        Référence: {{ $purchase->reference ?? $purchase->invoice_number }}<br>
                        @if($purchase->warehouse)Entrepôt: {{ $purchase->warehouse->name }}@endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS TABLE -->
<div class="items-section">
    <div class="section-title">Articles commandés</div>
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
            @forelse($purchase->items as $item)
                <tr>
                    <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                    <td class="text-center">{{ floatval($item->quantity) == intval($item->quantity) ? intval($item->quantity) : rtrim(rtrim(number_format(floatval($item->quantity), 3, ',', ' '), '0'), ',') }}</td>
                    <td class="text-right text-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td class="text-center">{{ number_format($item->vat_rate ?? 0, 0) }}%</td>
                    <td class="text-right">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px; color: #999;">
                        Aucun article dans cette facture d'achat
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
                                    <td class="totals-label">TVA {{ $amounts['rate'] }}% (base {{ number_format($amounts['base_ht'] * $discountMultiplier, 2, ',', ' ') }})</td>
                                    <td class="totals-value">{{ number_format($amounts['vat_amount'] * $discountMultiplier, 2, ',', ' ') }} {{ $currency }}</td>
                                </tr>
                            </table>
                        </div>
                        @endforeach
                    @else
                    @php $singleRate = count($vatBreakdown) ? (reset($vatBreakdown)['rate'] ?? '0') : '0'; @endphp
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TVA déductible ({{ $singleRate }}%)</td>
                                <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TOTAL TTC</td>
                                <td class="totals-value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="amount-words">
                        {{ amountToWordsFrPurchasePdf($grandTotal, $currency) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- NOTES -->
@if($purchase->notes)
<div class="notes-box">
    <span class="notes-title">Note:</span> {{ $purchase->notes }}
</div>
@endif

<!-- QR VERIFICATION -->
@if(!empty($verificationUrl) && !empty($verificationCode))
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

<!-- FOOTER -->
<div class="footer">
    @if($company->footer_text)
        {{ $company->footer_text }}
    @else
        Document interne d'achat<br>
        {{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
    @endif
</div>

</body>
</html>
