<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
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
            font-size: 9px;
            color: #333;
            line-height: 1.4;
            padding: 15mm 20mm;
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

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #777; }

        /* ===== NOTICE ===== */
        .notice-box {
            background: #fff3cd;
            border: 1px solid #d4a913;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 9px;
            text-align: center;
            color: #856404;
            font-weight: bold;
        }

        /* ===== AMOUNT BOX ===== */
        .amount-section {
            margin-bottom: 15px;
        }

        .amount-box {
            border: 2px solid #333;
            padding: 12px;
            text-align: center;
        }

        .amount-box-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 4px;
        }

        .amount-box-value {
            font-size: 26px;
            font-weight: bold;
            color: #333;
        }

        .amount-box-words {
            font-size: 7px;
            font-style: italic;
            color: #777;
            margin-top: 4px;
            border-top: 1px dashed #ccc;
            padding-top: 4px;
        }

        /* ===== SECTION TITLE ===== */
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

        /* ===== DETAILS TABLE ===== */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .details-table td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
        }

        .details-table tr:last-child td {
            border-bottom: 1px solid #ccc;
        }

        .details-label {
            font-size: 8px;
            color: #555;
            width: 40%;
        }

        .details-value {
            font-weight: 500;
        }

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

        .paid-row {
            background: #f0fff0;
        }

        .remaining-row {
            background: #fff5f5;
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

        /* ===== SIGNATURES ===== */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9px;
            color: #555;
            font-weight: bold;
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
    $currency = $company->currency ?? 'FCFA';
    $remaining = max(0, floatval($sale->total) - floatval($sale->amount_paid));

    function amountToWordsFrReceiptPdf($number, $currency = 'FCFA') {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        $euros = floor($number);
        $units = [
            'EUR' => ['euro', 'euros'],
            'FCFA' => ['franc CFA', 'francs CFA'],
            'XOF' => ['franc CFA', 'francs CFA'],
            'USD' => ['dollar', 'dollars'],
            'GBP' => ['livre sterling', 'livres sterling'],
        ];
        $u = $units[$currency] ?? ['unité', 'unités'];
        $euroWord = $euros == 1 ? $u[0] : $u[1];
        return ucfirst($fmt->format($euros)) . ' ' . $euroWord;
    }
@endphp

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($company->logo_path)
                    <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @endif
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-subtitle">Reçu de paiement</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}@endif
                    @if($company->email) | {{ $company->email }}@endif
                    @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
                    @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label">Reçu N°</div>
                <div class="invoice-number">REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="invoice-date">{{ $payment->payment_date->format('d/m/Y') }}</div>
                <span class="status-badge">Paiement reçu</span>
            </td>
        </tr>
    </table>
</div>

<!-- NOTICE -->
<div class="notice-box">
    Document interne — Ce reçu atteste d'un règlement reçu. Il ne remplace pas la facture normalisée e-MCeF.
</div>

<!-- INFO CARDS -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Détails du paiement</div>
                    <div class="info-card-text">
                        Mode : {{ \App\Models\Payment::METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method) }}<br>
                        @if($payment->reference)Référence : {{ $payment->reference }}<br>@endif
                        Reçu par : {{ $payment->creator?->name ?? 'N/A' }}<br>
                        Heure : {{ $payment->payment_date->format('H:i') }}
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Client</div>
                    <div class="info-card-name">{{ $sale->customer?->name ?? 'Client comptoir' }}</div>
                    <div class="info-card-text">
                        @if(optional($sale->customer)->registration_number)IFU: {{ $sale->customer->registration_number }}<br>@endif
                        @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                        @if(optional($sale->customer)->phone)Tel: {{ $sale->customer->phone }}<br>@endif
                        @if(optional($sale->customer)->email){{ $sale->customer->email }}@endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- AMOUNT BOX -->
<div class="amount-section">
    <div class="amount-box">
        <div class="amount-box-label">Montant reçu</div>
        <div class="amount-box-value">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $currency }}</div>
        <div class="amount-box-words">{{ amountToWordsFrReceiptPdf($payment->amount, $currency) }}</div>
    </div>
</div>

<!-- INVOICE REFERENCE -->
<div class="section-title">Facture de référence</div>
<table class="details-table">
    <tr>
        <td class="details-label">N° Facture</td>
        <td class="details-value">{{ $sale->invoice_number }}</td>
    </tr>
    <tr>
        <td class="details-label">Date facture</td>
        <td class="details-value">{{ $sale->created_at->format('d/m/Y') }}</td>
    </tr>
    @if($sale->emcef_nim)
    <tr>
        <td class="details-label">NIM e-MCeF</td>
        <td class="details-value">{{ $sale->emcef_nim }}</td>
    </tr>
    @endif
    @if($sale->emcef_code_mecef)
    <tr>
        <td class="details-label">Code MECeF</td>
        <td class="details-value">{{ $sale->emcef_code_mecef }}</td>
    </tr>
    @endif
</table>

<!-- TOTALS -->
<div class="totals-section">
    <table class="totals-wrapper">
        <tr>
            <td class="spacer">
                @if($payment->notes)
                <div class="notes-box">
                    <span class="notes-title">Note:</span> {{ $payment->notes }}
                </div>
                @endif
            </td>
            <td class="totals">
                <div class="totals-card">
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Total facture</td>
                                <td class="totals-value">{{ number_format($sale->total, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">CE PAIEMENT</td>
                                <td class="totals-value">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="totals-row paid-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label" style="color:#2e7d32;">Total payé à ce jour</td>
                                <td class="totals-value" style="color:#2e7d32;">{{ number_format($sale->amount_paid, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($remaining > 0)
                    <div class="totals-row remaining-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label" style="color:#c62828;font-weight:bold;">Reste à payer</td>
                                <td class="totals-value" style="color:#c62828;font-weight:bold;">{{ number_format($remaining, 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @else
                    <div class="totals-row paid-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label" style="color:#2e7d32;font-weight:bold;">Solde</td>
                                <td class="totals-value" style="color:#2e7d32;font-weight:bold;">SOLDÉ</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- SIGNATURES -->
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

<!-- FOOTER -->
<div class="footer">
    Reçu généré le {{ now()->format('d/m/Y à H:i') }}<br>
    Règlement de la Facture N° {{ $sale->invoice_number }}
    @if($sale->emcef_nim)
        — Facture certifiée e-MCeF — NIM: {{ $sale->emcef_nim }}
    @endif
    <br>{{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
</div>

</body>
</html>
