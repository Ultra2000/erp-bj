<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reçu de Paiement - REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
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
            font-size: 10px;
            color: #2d3748;
            line-height: 1.5;
        }

        .accent-bar {
            height: 6px;
            background: #276749;
        }

        .page-wrap {
            padding: 0 22mm 15mm 22mm;
        }

        /* ===== HEADER ===== */
        .header {
            padding: 18px 0 14px 0;
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
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #276749;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 8px;
            color: #718096;
            line-height: 1.6;
        }

        .receipt-title-block {
            text-align: right;
            padding-top: 2px;
        }

        .receipt-type-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #276749;
            margin-bottom: 4px;
        }

        .receipt-number {
            font-size: 20px;
            font-weight: bold;
            color: #276749;
            letter-spacing: 0.5px;
        }

        .receipt-date {
            font-size: 9px;
            color: #718096;
            margin-top: 6px;
        }

        .header-divider {
            border: none;
            border-top: 2px solid #276749;
            margin: 0 0 16px 0;
        }

        /* ===== NON-FISCAL NOTICE ===== */
        .notice-bar {
            background: #fffbeb;
            border: 1px solid #d69e2e;
            border-radius: 4px;
            padding: 8px 14px;
            margin-bottom: 16px;
            text-align: center;
        }

        .notice-text {
            font-size: 8px;
            color: #975a16;
            font-weight: 600;
            line-height: 1.6;
        }

        /* ===== AMOUNT BOX ===== */
        .amount-section {
            margin-bottom: 18px;
        }

        .amount-box {
            background: #276749;
            border-radius: 6px;
            padding: 18px 20px;
            text-align: center;
        }

        .amount-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #c6f6d5;
            margin-bottom: 6px;
        }

        .amount-value {
            font-size: 30px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        .amount-currency {
            font-size: 14px;
            font-weight: normal;
            color: #c6f6d5;
        }

        .amount-words {
            font-size: 7px;
            font-style: italic;
            color: #9ae6b4;
            margin-top: 6px;
        }

        /* ===== INFO CARDS ===== */
        .info-section {
            margin-bottom: 16px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
        }

        .info-grid td:first-child {
            padding-right: 8px;
        }

        .info-grid td:last-child {
            padding-left: 8px;
        }

        .info-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 12px 14px;
            height: 100%;
        }

        .info-card-title {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #276749;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-row td {
            padding: 4px 0;
            font-size: 9px;
        }

        .detail-label {
            color: #718096;
            width: 42%;
        }

        .detail-value {
            text-align: right;
            font-weight: 600;
            color: #2d3748;
        }

        /* ===== INVOICE REFERENCE ===== */
        .invoice-ref-section {
            margin-bottom: 16px;
        }

        .invoice-ref-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .invoice-ref-header {
            background: #f7fafc;
            padding: 8px 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        .invoice-ref-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #276749;
        }

        .invoice-ref-body {
            padding: 12px 14px;
        }

        .invoice-ref-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-ref-table td {
            padding: 5px 0;
            font-size: 9px;
            border-bottom: 1px solid #edf2f7;
        }

        .invoice-ref-table tr:last-child td {
            border-bottom: none;
        }

        .ref-label {
            color: #718096;
            width: 35%;
        }

        .ref-value {
            font-weight: 600;
            color: #2d3748;
        }

        /* ===== PAYMENT STATUS BAR ===== */
        .status-bar {
            margin-bottom: 16px;
        }

        .status-bar-table {
            width: 100%;
            border-collapse: collapse;
        }

        .status-cell {
            border-radius: 4px;
            padding: 10px 14px;
            text-align: center;
            width: 33.33%;
        }

        .status-cell-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .status-cell-value {
            font-size: 13px;
            font-weight: bold;
        }

        .status-total {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
        }

        .status-total .status-cell-label { color: #718096; }
        .status-total .status-cell-value { color: #2d3748; }

        .status-paid {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
        }

        .status-paid .status-cell-label { color: #276749; }
        .status-paid .status-cell-value { color: #276749; }

        .status-remaining {
            background: #fff5f5;
            border: 1px solid #fed7d7;
        }

        .status-remaining .status-cell-label { color: #9b2c2c; }
        .status-remaining .status-cell-value { color: #9b2c2c; }

        .status-remaining-zero {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
        }

        .status-remaining-zero .status-cell-label { color: #276749; }
        .status-remaining-zero .status-cell-value { color: #276749; }

        /* ===== NOTES ===== */
        .notes-box {
            border-left: 3px solid #276749;
            background: #f7fafc;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 9px;
            color: #4a5568;
        }

        .notes-title {
            font-weight: bold;
            color: #276749;
            font-size: 8px;
            margin-bottom: 3px;
        }

        /* ===== SIGNATURES ===== */
        .signatures-section {
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #2d3748;
            margin-top: 50px;
            padding-top: 6px;
            font-size: 9px;
            color: #718096;
            font-weight: 600;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 12px;
            border-top: 2px solid #276749;
            color: #a0aec0;
            font-size: 7px;
            line-height: 1.7;
        }

        .footer strong {
            color: #718096;
        }

        .emcef-ref {
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
    </style>
</head>
<body>
@php
    $currency = $company->currency ?? 'FCFA';
    $remaining = max(0, floatval($sale->total) - floatval($sale->amount_paid));

    $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
    $euros = floor($payment->amount);
    $units = [
        'EUR' => 'euro(s)', 'FCFA' => 'franc(s) CFA', 'XOF' => 'franc(s) CFA',
        'USD' => 'dollar(s)', 'GBP' => 'livre(s) sterling',
    ];
    $unitLabel = $units[$currency] ?? 'unité(s)';
    $amountWords = ucfirst($fmt->format($euros)) . ' ' . $unitLabel;
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
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}@endif
                    @if($company->email) &bull; {{ $company->email }}@endif
                    @if($company->tax_number)<br>IFU: {{ $company->tax_number }}@endif
                    @if($company->siret) &bull; SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="receipt-title-block">
                <div class="receipt-type-label">Reçu de paiement</div>
                <div class="receipt-number">REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="receipt-date">
                    Date : {{ $payment->payment_date->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>
</div>

<hr class="header-divider">

<!-- NON-FISCAL NOTICE -->
<div class="notice-bar">
    <div class="notice-text">
        Document interne &mdash; Ce reçu atteste d'un règlement reçu. Il ne remplace pas la facture normalisée e-MCeF.
    </div>
</div>

<!-- AMOUNT BOX -->
<div class="amount-section">
    <div class="amount-box">
        <div class="amount-label">Montant reçu</div>
        <div class="amount-value">
            {{ number_format($payment->amount, 0, ',', ' ') }}
            <span class="amount-currency">{{ $currency }}</span>
        </div>
        <div class="amount-words">{{ $amountWords }}</div>
    </div>
</div>

<!-- PAYMENT & CLIENT INFO -->
<div class="info-section">
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Détails du paiement</div>
                    <table class="detail-row">
                        <tr>
                            <td class="detail-label">Mode de paiement</td>
                            <td class="detail-value">{{ \App\Models\Payment::METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</td>
                        </tr>
                        @if($payment->reference)
                        <tr>
                            <td class="detail-label">Référence</td>
                            <td class="detail-value">{{ $payment->reference }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="detail-label">Reçu par</td>
                            <td class="detail-value">{{ $payment->creator?->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label">Heure</td>
                            <td class="detail-value">{{ $payment->payment_date->format('H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Client</div>
                    <table class="detail-row">
                        <tr>
                            <td class="detail-label">Nom</td>
                            <td class="detail-value">{{ $sale->customer?->name ?? 'Client comptoir' }}</td>
                        </tr>
                        @if(optional($sale->customer)->registration_number)
                        <tr>
                            <td class="detail-label">IFU</td>
                            <td class="detail-value">{{ $sale->customer->registration_number }}</td>
                        </tr>
                        @endif
                        @if(optional($sale->customer)->phone)
                        <tr>
                            <td class="detail-label">Téléphone</td>
                            <td class="detail-value">{{ $sale->customer->phone }}</td>
                        </tr>
                        @endif
                        @if(optional($sale->customer)->address)
                        <tr>
                            <td class="detail-label">Adresse</td>
                            <td class="detail-value">{{ $sale->customer->address }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- INVOICE REFERENCE -->
<div class="invoice-ref-section">
    <div class="invoice-ref-card">
        <div class="invoice-ref-header">
            <div class="invoice-ref-title">Facture de référence</div>
        </div>
        <div class="invoice-ref-body">
            <table class="invoice-ref-table">
                <tr>
                    <td class="ref-label">N° Facture</td>
                    <td class="ref-value">{{ $sale->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="ref-label">Date facture</td>
                    <td class="ref-value">{{ $sale->created_at->format('d/m/Y') }}</td>
                </tr>
                @if($sale->emcef_nim)
                <tr>
                    <td class="ref-label">NIM e-MCeF</td>
                    <td class="ref-value">{{ $sale->emcef_nim }}</td>
                </tr>
                @endif
                @if($sale->emcef_code_mecef)
                <tr>
                    <td class="ref-label">Code MECeF</td>
                    <td class="ref-value">{{ $sale->emcef_code_mecef }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- PAYMENT STATUS -->
<div class="status-bar">
    <table class="status-bar-table">
        <tr>
            <td class="status-cell status-total" style="border-radius: 4px 0 0 4px;">
                <div class="status-cell-label">Total facture</div>
                <div class="status-cell-value">{{ number_format($sale->total, 0, ',', ' ') }}</div>
            </td>
            <td class="status-cell status-paid">
                <div class="status-cell-label">Total payé</div>
                <div class="status-cell-value">{{ number_format($sale->amount_paid, 0, ',', ' ') }}</div>
            </td>
            <td class="status-cell {{ $remaining <= 0 ? 'status-remaining-zero' : 'status-remaining' }}" style="border-radius: 0 4px 4px 0;">
                <div class="status-cell-label">Reste à payer</div>
                <div class="status-cell-value">{{ number_format($remaining, 0, ',', ' ') }}</div>
            </td>
        </tr>
    </table>
</div>

<!-- NOTES -->
@if($payment->notes)
<div class="notes-box">
    <div class="notes-title">Note</div>
    {{ $payment->notes }}
</div>
@endif

<!-- SIGNATURES -->
<div class="signatures-section">
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">Le Client</div>
            </td>
            <td>
                <div class="signature-line">Le Caissier</div>
            </td>
        </tr>
    </table>
</div>

<!-- FOOTER -->
<div class="footer">
    Reçu généré le {{ now()->format('d/m/Y à H:i') }}<br>
    <strong>Règlement de la Facture N° {{ $sale->invoice_number }}</strong>
    @if($sale->emcef_nim)
        <br><span class="emcef-ref">Facture certifiée e-MCeF &mdash; NIM: {{ $sale->emcef_nim }}</span>
    @endif
    <br>{{ $company->name }} {{ $company->phone ? '&bull; ' . $company->phone : '' }} {{ $company->email ? '&bull; ' . $company->email : '' }}
</div>

</div>{{-- /.page-wrap --}}
</body>
</html>
