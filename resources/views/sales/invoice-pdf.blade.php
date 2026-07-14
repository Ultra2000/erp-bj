<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture' }} {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #2d3748;
            line-height: 1.5;
            padding: 0;
            margin: 0;
        }

        .page-wrap {
            padding: 0 20mm 15mm 20mm;
        }

        /* ===== ACCENT BAR ===== */
        .accent-bar {
            height: 6px;
            background: #1a365d;
        }

        /* ===== HEADER ===== */
        .header {
            padding: 16px 0 14px 0;
            margin-bottom: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-height: 50px;
            max-width: 140px;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 8px;
            color: #718096;
            line-height: 1.6;
        }

        .invoice-title-block {
            text-align: right;
            padding-top: 2px;
        }

        .invoice-type-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a365d;
            margin-bottom: 4px;
        }

        .invoice-number {
            font-size: 20px;
            font-weight: bold;
            color: #1a365d;
            letter-spacing: 0.5px;
        }

        .invoice-date {
            font-size: 9px;
            color: #718096;
            margin-top: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .status-completed {
            background: #c6f6d5;
            color: #276749;
        }

        .status-pending {
            background: #fefcbf;
            color: #975a16;
        }

        .status-cancelled {
            background: #fed7d7;
            color: #9b2c2c;
            text-decoration: line-through;
        }

        .header-divider {
            border: none;
            border-top: 2px solid #1a365d;
            margin: 0 0 14px 0;
        }

        /* ===== INFO CARDS ===== */
        .info-section {
            margin-bottom: 16px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
        }

        .info-table td:first-child {
            padding-right: 8px;
        }

        .info-table td:last-child {
            padding-left: 8px;
        }

        .info-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
            height: 100%;
        }

        .info-card-title {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a365d;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-card-name {
            font-size: 11px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .info-card-text {
            font-size: 8px;
            color: #718096;
            line-height: 1.6;
        }

        /* ===== ITEMS TABLE ===== */
        .items-section {
            margin-bottom: 16px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table thead th {
            padding: 8px 10px;
            text-align: left;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #1a365d;
            color: #ffffff;
        }

        .items-table thead th:first-child {
            border-radius: 3px 0 0 0;
        }

        .items-table thead th:last-child {
            border-radius: 0 3px 0 0;
        }

        .items-table tbody td {
            padding: 7px 10px;
            font-size: 9px;
            border-bottom: 1px solid #edf2f7;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #f7fafc;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #e2e8f0;
        }

        .product-name {
            font-weight: 600;
            color: #2d3748;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* ===== TOTALS ===== */
        .totals-section {
            margin-bottom: 14px;
        }

        .totals-wrapper {
            width: 100%;
            border-collapse: collapse;
        }

        .spacer-cell { width: 55%; }

        .totals-cell {
            width: 45%;
            vertical-align: top;
        }

        .totals-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .totals-row {
            padding: 5px 12px;
            border-bottom: 1px solid #edf2f7;
        }

        .totals-row-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-label {
            font-size: 8px;
            color: #718096;
        }

        .totals-value {
            text-align: right;
            font-size: 9px;
            font-weight: 500;
            color: #2d3748;
        }

        .totals-value.discount {
            color: #e53e3e;
        }

        .grand-total {
            background: #1a365d;
            padding: 8px 12px;
            border-bottom: none;
        }

        .grand-total .totals-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #ffffff;
        }

        .grand-total .totals-value {
            font-size: 13px;
            font-weight: bold;
            color: #ffffff;
        }

        .amount-words {
            padding: 5px 12px;
            font-size: 7px;
            font-style: italic;
            color: #a0aec0;
            border-top: 1px solid #edf2f7;
            background: #f7fafc;
        }

        /* ===== PAYMENT STATUS ===== */
        .payment-status-section {
            margin-bottom: 14px;
        }

        .payment-status-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-info-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            background: #f7fafc;
        }

        .payment-info-row {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-info-row td {
            padding: 2px 0;
            font-size: 8px;
        }

        .payment-info-label {
            color: #718096;
            width: 40%;
        }

        .payment-info-value {
            text-align: right;
            font-weight: 600;
            color: #2d3748;
        }

        .payment-paid { color: #276749; }
        .payment-partial { color: #975a16; }
        .payment-unpaid { color: #9b2c2c; }

        /* ===== NOTES ===== */
        .notes-box {
            border-left: 3px solid #1a365d;
            background: #f7fafc;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 8px;
            color: #4a5568;
        }

        .notes-title {
            font-weight: bold;
            color: #1a365d;
            font-size: 8px;
        }

        /* ===== CREDIT NOTE ===== */
        .credit-note-banner {
            background: #fffbeb;
            border: 1px solid #f6ad55;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 9px;
            color: #744210;
        }

        /* ===== QR VERIFICATION ===== */
        .verification-section {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 12px;
            background: #f7fafc;
        }

        .verification-table {
            width: 100%;
        }

        .qr-cell {
            width: 70px;
            vertical-align: top;
        }

        .qr-box img {
            width: 60px;
            height: 60px;
        }

        .verification-info {
            padding-left: 12px;
            vertical-align: middle;
        }

        .verification-title {
            font-size: 9px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 3px;
        }

        .verification-text {
            font-size: 7px;
            color: #718096;
            line-height: 1.5;
        }

        .verification-code {
            display: inline-block;
            font-family: 'DejaVu Sans Mono', monospace;
            background: #edf2f7;
            border: 1px solid #cbd5e0;
            padding: 2px 8px;
            font-size: 8px;
            margin-top: 4px;
            border-radius: 2px;
            letter-spacing: 1px;
        }

        .emcef-badge {
            display: inline-block;
            background: #276749;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .emcef-pending-badge {
            display: inline-block;
            background: #975a16;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 10px;
            border-top: 2px solid #1a365d;
            color: #a0aec0;
            font-size: 7px;
            line-height: 1.6;
        }

        .footer strong {
            color: #718096;
        }

        .tax-group-badge {
            display: inline-block;
            border: 1px solid #1a365d;
            font-size: 7px;
            padding: 0 3px;
            font-weight: bold;
            color: #1a365d;
            border-radius: 2px;
        }

        .tax-group-badge-e {
            border-color: #dd6b20;
            color: #dd6b20;
        }
    </style>
</head>
<body>
@php
    $currency = $company->currency ?? 'XOF';
    $status = $sale->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $sale->discount_percent ?? 0;

    $isVatFranchise = \App\Models\AccountingSetting::isVatFranchise($company->id);

    $rawTotalHt = $sale->items->sum('total_price_ht');
    $rawTotalVat = $isVatFranchise ? 0 : $sale->items->sum('vat_amount');

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

    $vatBreakdown = [];
    $totalTaxSpecific = 0;
    $taxSpecificLabel = null;
    if (!$isVatFranchise) {
        foreach ($sale->items as $item) {
            $group = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category);
            $rate = number_format($item->vat_rate ?? 0, 1);
            $key = $group . '_' . $rate;
            if (!isset($vatBreakdown[$key])) {
                $vatBreakdown[$key] = ['base_ht' => 0, 'vat_amount' => 0, 'group' => $group, 'rate' => $rate];
            }
            $vatBreakdown[$key]['base_ht'] += $item->total_price_ht ?? 0;
            $vatBreakdown[$key]['vat_amount'] += $item->vat_amount ?? 0;
            if ($item->tax_specific_amount > 0) {
                $totalTaxSpecific += ($item->tax_specific_total ?? ($item->tax_specific_amount * $item->quantity));
                if (!$taxSpecificLabel && $item->tax_specific_label) {
                    $taxSpecificLabel = $item->tax_specific_label;
                }
            }
        }
        ksort($vatBreakdown);
    }
    $hasMixedRates = count($vatBreakdown) > 1 || $totalTaxSpecific > 0;

    $discountMultiplier = 1 - (($sale->discount_percent ?? 0) / 100);
    $totalHt = round($rawTotalHt * $discountMultiplier, 2);
    $totalVat = round($rawTotalVat * $discountMultiplier, 2);
    $grandTotal = $isVatFranchise ? $totalHt : ($totalHt + $totalVat + $totalTaxSpecific);

    $isEmcefEnabled = $company->emcef_enabled ?? false;

    $totalAvantRemise = $rawTotalHt + $rawTotalVat;
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);

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
        'completed' => 'Validée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];

    $paymentStatusLabels = [
        'paid' => 'Payée',
        'partial' => 'Partiellement payée',
        'unpaid' => 'Impayée',
        'pending' => 'En attente',
    ];

    $invoiceTypeLabel = $sale->type === 'credit_note' ? 'AVOIR' : ($sale->is_export ? 'FACTURE EXPORT' : 'FACTURE');

    $isEmcefCertified = ($sale->emcef_status === 'certified' && $sale->emcef_qr_code);

    $netToPay = $sale->aib_amount > 0 ? ($grandTotal + $sale->aib_amount) : $grandTotal;
    $remainingAmount = max(0, $netToPay - floatval($sale->amount_paid));
@endphp

<!-- ACCENT BAR -->
<div class="accent-bar"></div>

<div class="page-wrap">

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if($company->logo_path)
                    <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @endif
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}@endif
                    @if($company->email) &bull; {{ $company->email }}@endif
                    @if($company->tax_number)<br>IFU: {{ $company->tax_number }}@endif
                    @if($company->siret) &bull; SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="invoice-title-block">
                <div class="invoice-type-label">{{ $invoiceTypeLabel }}</div>
                <div class="invoice-number">{{ $sale->invoice_number }}</div>
                <div class="invoice-date">
                    Date : {{ $sale->created_at->format('d/m/Y') }}
                </div>
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </td>
        </tr>
    </table>
</div>

<hr class="header-divider">

{{-- Avoir : référence facture d'origine --}}
@if($sale->type === 'credit_note' && $sale->parent)
<div class="credit-note-banner">
    <strong>Avoir relatif à la facture N° {{ $sale->parent->invoice_number }}</strong> du {{ $sale->parent->created_at->format('d/m/Y') }}
    @if($sale->parent->emcef_code_mecef)
        &mdash; Code MECeF : {{ $sale->parent->emcef_code_mecef }}
    @endif
</div>
@endif

<!-- INFO CARDS -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Facturé à</div>
                    <div class="info-card-name">{{ $sale->customer->name ?? 'Client non défini' }}</div>
                    <div class="info-card-text">
                        @if(optional($sale->customer)->registration_number)IFU : {{ $sale->customer->registration_number }}<br>@endif
                        @if(optional($sale->customer)->siret && optional($sale->customer)->siret !== optional($sale->customer)->registration_number)SIRET : {{ $sale->customer->siret }}<br>@endif
                        @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                        @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                        @if(optional($sale->customer)->phone)Tel : {{ $sale->customer->phone }}<br>@endif
                        @if(optional($sale->customer)->email){{ $sale->customer->email }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Informations</div>
                    <div class="info-card-text" style="padding-top: 2px;">
                        <table class="payment-info-row">
                            <tr>
                                <td class="payment-info-label">Référence</td>
                                <td class="payment-info-value">{{ $sale->reference ?? $sale->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="payment-info-label">Mode de paiement</td>
                                <td class="payment-info-value">
                                    @switch($sale->payment_method)
                                        @case('cash') Espèces @break
                                        @case('card') Carte bancaire @break
                                        @case('mobile') Mobile Money @break
                                        @case('transfer') Virement @break
                                        @case('check') Chèque @break
                                        @case('mixed') Mixte @break
                                        @default {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}
                                    @endswitch
                                </td>
                            </tr>
                            @if($sale->warehouse)
                            <tr>
                                <td class="payment-info-label">Point de vente</td>
                                <td class="payment-info-value">{{ $sale->warehouse->name }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="payment-info-label">Statut paiement</td>
                                <td class="payment-info-value {{ $sale->payment_status === 'paid' ? 'payment-paid' : ($sale->payment_status === 'partial' ? 'payment-partial' : 'payment-unpaid') }}">
                                    {{ $paymentStatusLabels[$sale->payment_status] ?? ucfirst($sale->payment_status ?? 'En attente') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS TABLE -->
<div class="items-section">
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
                @php $pdfItemGroup = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category); @endphp
                <tr>
                    <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                    <td class="text-center">{{ floatval($item->quantity) == intval($item->quantity) ? intval($item->quantity) : rtrim(rtrim(number_format(floatval($item->quantity), 3, ',', ' '), '0'), ',') }}</td>
                    <td class="text-right" style="color: #718096;">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }}</td>
                    <td class="text-center">
                        @if($pdfItemGroup === 'E' && !$item->tax_specific_amount)TPS @endif{{ number_format($item->vat_rate ?? 0, 0) }}%
                        @if($item->tax_specific_amount > 0)<br><span style="font-size:7px;color:#718096;">+ {{ number_format($item->tax_specific_amount, 0, ',', ' ') }}/u</span>@endif
                        @if($isEmcefEnabled)
                            <span class="tax-group-badge">{{ $pdfItemGroup }}</span>
                            @if($item->tax_specific_amount > 0) <span class="tax-group-badge tax-group-badge-e">E</span>@endif
                        @endif
                    </td>
                    <td class="text-right" style="font-weight:600;">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #a0aec0;">
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
            <td class="spacer-cell">
                {{-- Espace pour notes ou mentions légales à gauche --}}
                @if($sale->notes)
                <div class="notes-box">
                    <span class="notes-title">Note :</span> {{ $sale->notes }}
                </div>
                @endif
            </td>
            <td class="totals-cell">
                <div class="totals-card">
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Total HT</td>
                                <td class="totals-value">{{ number_format($rawTotalHt, 2, ',', ' ') }} {{ $currency }}</td>
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
                    @if($discountAmount > 0)
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Total HT après remise</td>
                                <td class="totals-value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    @endif
                    @if($hasMixedRates)
                        @foreach($vatBreakdown as $key => $amounts)
                        @php $taxLabel = ($amounts['group'] ?? '') === 'E' ? 'TPS' : 'TVA'; @endphp
                        <div class="totals-row">
                            <table class="totals-row-table">
                                <tr>
                                    <td class="totals-label">{{ $taxLabel }} {{ $amounts['rate'] }}%@if($isEmcefEnabled && !empty($amounts['group'])) &mdash; Grp {{ $amounts['group'] }}@endif</td>
                                    <td class="totals-value">{{ number_format($amounts['vat_amount'], 2, ',', ' ') }} {{ $currency }}</td>
                                </tr>
                            </table>
                        </div>
                        @endforeach
                        @if($totalTaxSpecific > 0)
                        <div class="totals-row">
                            <table class="totals-row-table">
                                <tr>
                                    <td class="totals-label">{{ $taxSpecificLabel ?? 'Taxe spécifique' }}{{ $isEmcefEnabled ? ' — Grp E' : '' }}</td>
                                    <td class="totals-value">{{ number_format($totalTaxSpecific, 2, ',', ' ') }} {{ $currency }}</td>
                                </tr>
                            </table>
                        </div>
                        @endif
                    @else
                    @php $singleGroup = count($vatBreakdown) ? (reset($vatBreakdown)['group'] ?? null) : null; @endphp
                    @php $singleRate = count($vatBreakdown) ? (reset($vatBreakdown)['rate'] ?? '0') : '0'; @endphp
                    @php $singleTaxLabel = $singleGroup === 'E' ? 'TPS' : 'TVA'; @endphp
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">{{ $singleTaxLabel }} ({{ $singleRate }}%@if($isEmcefEnabled && $singleGroup) &mdash; Grp {{ $singleGroup }}@endif)</td>
                                <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($totalTaxSpecific > 0)
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">{{ $taxSpecificLabel ?? 'Taxe spécifique' }}{{ $isEmcefEnabled ? ' — Grp E' : '' }}</td>
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
                                <td class="totals-label">AIB {{ $sale->aib_rate === 'A' ? '(1%)' : '(5%)' }} <span style="font-size:7px;color:#a0aec0;">&mdash; Acompte sur Impôt</span></td>
                                <td class="totals-value">{{ number_format($sale->aib_amount, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">NET A PAYER</td>
                                <td class="totals-value">{{ number_format($netToPay, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    @if($sale->payment_status === 'partial' && floatval($sale->amount_paid) > 0)
                    <div class="totals-row" style="background:#f0fff4;">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label" style="color:#276749;">Montant payé</td>
                                <td class="totals-value" style="color:#276749;">{{ number_format($sale->amount_paid, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="totals-row" style="background:#fff5f5;">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label" style="color:#9b2c2c;font-weight:bold;">Reste à payer</td>
                                <td class="totals-value" style="color:#9b2c2c;font-weight:bold;">{{ number_format($remainingAmount, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    <div class="amount-words">
                        Arrêté la présente facture à la somme de :<br>
                        <strong>{{ amountToWordsFrSalePdf($netToPay, $currency) }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- QR VERIFICATION (App) -->
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
                    Scannez le QR code ou visitez le lien ci-dessous pour vérifier ce document.<br>
                    <span style="font-size:6px;word-break:break-all;color:#a0aec0;">{{ $verificationUrl }}</span>
                </div>
                <span class="verification-code">{{ $verificationCode }}</span>
            </td>
        </tr>
    </table>
</div>
@endif

{{-- e-MCeF (Certification DGI Bénin) --}}
@if($isEmcefCertified)
<div class="verification-section" style="border-color:#276749;">
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
                <div class="verification-title" style="color:#276749;">Facture certifiée DGI Bénin</div>
                <div class="verification-text">
                    <table style="width:100%;border-collapse:collapse;">
                        <tr><td style="width:90px;color:#718096;padding:1px 0;">NIM</td><td style="font-weight:bold;padding:1px 0;">{{ $sale->emcef_nim }}</td></tr>
                        <tr><td style="color:#718096;padding:1px 0;">Code MECeF</td><td style="font-weight:bold;padding:1px 0;">{{ $sale->emcef_code_mecef }}</td></tr>
                        <tr><td style="color:#718096;padding:1px 0;">Date certification</td><td style="padding:1px 0;">{{ $sale->emcef_certified_at?->format('d/m/Y H:i') }}</td></tr>
                    </table>
                </div>
                @if($sale->emcef_counters)
                    <span class="verification-code">{{ $sale->emcef_counters }}</span>
                @endif
                <span class="emcef-badge">Certifiée e-MCeF</span>
            </td>
        </tr>
    </table>
</div>
@elseif(isset($company) && $company->emcef_enabled && $sale->emcef_status === 'pending')
<div class="verification-section" style="border-color:#d69e2e;">
    <table class="verification-table">
        <tr>
            <td class="verification-info" style="width: 100%;">
                <span class="emcef-pending-badge">Certification en cours</span>
                <div class="verification-text" style="margin-top:4px;">
                    Cette facture est en attente de certification par la DGI Bénin via e-MCeF.
                </div>
            </td>
        </tr>
    </table>
</div>
@endif

<!-- FOOTER -->
<div class="footer">
    @if($sale->is_export)
        <strong>Exonération de TVA — Exportation de biens (Art. 262 CGI)</strong><br>
    @elseif($isVatFranchise)
        <strong>Exonéré de TVA</strong><br>
    @endif
    @if($company->footer_text)
        {{ $company->footer_text }}
    @else
        Merci pour votre confiance &bull; {{ $company->name }}<br>
        {{ $company->phone ?? '' }} {{ $company->email ? '&bull; ' . $company->email : '' }}
    @endif
</div>

</div>{{-- /.page-wrap --}}
</body>
</html>
