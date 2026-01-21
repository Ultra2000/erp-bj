<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement - {{ $payment->reference ?? $payment->id }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            color: #666;
        }
        .document-title {
            background: #f5f5f5;
            padding: 10px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .document-title h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .document-title .subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .info-box {
            background: #fff8e1;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
        }
        .info-box .warning {
            font-weight: bold;
            color: #856404;
            text-align: center;
        }
        .details-table {
            width: 100%;
            margin: 20px 0;
        }
        .details-table td {
            padding: 8px 0;
            vertical-align: top;
        }
        .details-table .label {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        .details-table .value {
            width: 60%;
        }
        .amount-box {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-box .label {
            font-size: 12px;
            color: #2e7d32;
            margin-bottom: 5px;
        }
        .amount-box .amount {
            font-size: 28px;
            font-weight: bold;
            color: #1b5e20;
        }
        .reference-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 12px;
            margin: 15px 0;
        }
        .reference-box h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #1565c0;
        }
        .reference-box p {
            margin: 5px 0;
            font-size: 11px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-name">{{ $company->name }}</div>
            <div class="company-info">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone)Tél: {{ $company->phone }}@endif
                @if($company->email) | {{ $company->email }}@endif
                @if($company->tax_number)<br>IFU: {{ $company->tax_number }}@endif
            </div>
        </div>

        <div class="document-title">
            <h1>📄 REÇU DE PAIEMENT</h1>
            <div class="subtitle">Document interne - Non fiscal</div>
        </div>

        <div class="info-box">
            <div class="warning">
                ⚠️ Ce document est un reçu de règlement interne.<br>
                Il ne remplace pas la facture normalisée e-MCeF.
            </div>
        </div>

        <table class="details-table">
            <tr>
                <td class="label">N° Reçu :</td>
                <td class="value"><strong>REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</strong></td>
            </tr>
            <tr>
                <td class="label">Date du paiement :</td>
                <td class="value">{{ $payment->payment_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Mode de paiement :</td>
                <td class="value">{{ \App\Models\Payment::METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
            </tr>
            @if($payment->reference)
            <tr>
                <td class="label">Référence :</td>
                <td class="value">{{ $payment->reference }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Reçu par :</td>
                <td class="value">{{ $payment->creator?->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="amount-box">
            <div class="label">MONTANT REÇU</div>
            <div class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $company->currency ?? 'FCFA' }}</div>
        </div>

        <div class="reference-box">
            <h3>📋 Référence de la Facture Originale</h3>
            <p><strong>N° Facture :</strong> {{ $sale->invoice_number }}</p>
            <p><strong>Date facture :</strong> {{ $sale->created_at->format('d/m/Y') }}</p>
            <p><strong>Client :</strong> {{ $sale->customer?->name ?? 'Client comptoir' }}</p>
            @if($sale->emcef_nim)
            <p><strong>NIM e-MCeF :</strong> {{ $sale->emcef_nim }}</p>
            @endif
            <p><strong>Total facture :</strong> {{ number_format($sale->total, 0, ',', ' ') }} {{ $company->currency ?? 'FCFA' }}</p>
            <p><strong>Total payé :</strong> {{ number_format($sale->amount_paid, 0, ',', ' ') }} {{ $company->currency ?? 'FCFA' }}</p>
            <p><strong>Reste à payer :</strong> {{ number_format($sale->total - $sale->amount_paid, 0, ',', ' ') }} {{ $company->currency ?? 'FCFA' }}</p>
        </div>

        @if($payment->notes)
        <div style="margin: 15px 0; padding: 10px; background: #fafafa; border-radius: 5px;">
            <strong>Notes :</strong> {{ $payment->notes }}
        </div>
        @endif

        <div class="signature">
            <div class="signature-box">
                <div class="signature-line">Le Client</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Le Caissier</div>
            </div>
        </div>

        <div class="footer">
            <p>Reçu généré le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>Règlement partiel de la Facture Normalisée N° {{ $sale->invoice_number }}</p>
            @if($sale->emcef_nim)
            <p>Facture certifiée e-MCeF - NIM: {{ $sale->emcef_nim }}</p>
            @endif
        </div>
    </div>
</body>
</html>
