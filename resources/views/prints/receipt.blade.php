<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'JetBrains Mono', 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            width: 80mm;
            margin: 0 auto;
            padding: 4mm;
            background: #fff;
        }
        
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .small { font-size: 10px; }
        .xsmall { font-size: 9px; }
        .large { font-size: 16px; }
        .xlarge { font-size: 20px; }
        
        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .divider-double {
            border: none;
            border-top: 2px solid #000;
            margin: 6px 0;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .header-info {
            margin-top: 4px;
        }
        .header-info p {
            font-size: 10px;
            line-height: 1.4;
        }
        
        .ticket-title {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 6px 0;
        }
        
        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .items-table th {
            font-size: 10px;
            font-weight: 600;
            text-align: left;
            padding: 2px 0;
            border-bottom: 1px solid #000;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        .items-table td {
            font-size: 11px;
            padding: 2px 0;
            vertical-align: top;
        }
        .item-name {
            font-weight: 500;
            max-width: 45mm;
            word-wrap: break-word;
        }
        .item-detail {
            font-size: 10px;
            color: #444;
            padding-left: 8px;
        }
        
        .totals {
            margin-top: 4px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 11px;
        }
        .total-row.grand {
            font-size: 16px;
            font-weight: 700;
            padding: 4px 0;
        }
        
        .payment-info {
            margin-top: 4px;
            font-size: 11px;
        }
        
        .emcef-section {
            margin-top: 6px;
            padding: 4px;
            border: 1px solid #000;
        }
        .emcef-section p {
            font-size: 9px;
        }
        
        .qr-code {
            margin: 6px auto;
            text-align: center;
        }
        .qr-code img {
            max-width: 40mm;
            height: auto;
        }
        
        .footer {
            margin-top: 8px;
            font-size: 9px;
            text-align: center;
            line-height: 1.5;
        }
        .footer .thank-you {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        /* Actions -- masqués à l'impression */
        .actions {
            text-align: center;
            margin: 15px auto;
            padding: 10px;
        }
        .actions button {
            padding: 10px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            border: none;
            margin: 4px;
        }
        .btn-print {
            background: #059669;
            color: white;
        }
        .btn-print:hover {
            background: #047857;
        }
        .btn-close {
            background: #6b7280;
            color: white;
        }
        .btn-close:hover {
            background: #4b5563;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 2mm;
            }
            .actions {
                display: none !important;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    {{-- Boutons d'action (masqués à l'impression) --}}
    <div class="actions">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer le ticket</button>
        <button class="btn-close" onclick="window.close()">✕ Fermer</button>
    </div>

    {{-- En-tête entreprise --}}
    <div class="center">
        @if($company->logo_path)
            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" style="max-width: 50mm; max-height: 15mm; margin-bottom: 4px;">
        @endif
        <div class="company-name">{{ strtoupper($company->name) }}</div>
        <div class="header-info">
            @if($company->address)<p>{{ $company->address }}</p>@endif
            @if($company->city)<p>{{ $company->city }}@if($company->zip_code) {{ $company->zip_code }}@endif</p>@endif
            @if($company->phone)<p>Tél: {{ $company->phone }}</p>@endif
            @if($company->tax_number)<p>IFU: {{ $company->tax_number }}</p>@endif
            @if($company->registration_number)<p>RCCM: {{ $company->registration_number }}</p>@endif
        </div>
    </div>

    <hr class="divider-double">

    {{-- Titre du document --}}
    <div class="center">
        <div class="ticket-title">{{ $sale->type === 'credit_note' ? 'AVOIR' : 'TICKET DE CAISSE' }}</div>
    </div>

    {{-- Métadonnées --}}
    <div class="meta-row">
        <span>N°: {{ $sale->invoice_number }}</span>
        <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
    </div>
    @if($sale->customer && $sale->customer->email !== 'walkin@pos.local')
    <div class="meta-row">
        <span>Client: {{ $sale->customer->name }}</span>
    </div>
    @endif
    <div class="meta-row small">
        <span>Caissier: {{ $sale->cashSession?->user?->name ?? auth()->user()->name }}</span>
    </div>

    <hr class="divider">

    {{-- Articles --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
                <th>Qté</th>
                <th>P.U.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td class="item-name">{{ $item->product?->name ?? 'Produit supprimé' }}</td>
                <td>{{ $item->quantity == intval($item->quantity) ? intval($item->quantity) : number_format($item->quantity, 2, ',', '') }}</td>
                <td>{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                <td class="right">{{ number_format($item->total_price, 0, ',', ' ') }}</td>
            </tr>
            @if($item->vat_rate > 0)
            <tr>
                <td colspan="4" class="item-detail">
                    TVA {{ number_format($item->vat_rate, 0) }}%: {{ number_format($item->vat_amount ?? 0, 0, ',', ' ') }} FCFA
                    @if($item->tax_specific_total > 0)
                     | {{ $item->tax_specific_label ?? 'Taxe spéc.' }}: {{ number_format($item->tax_specific_total, 0, ',', ' ') }} FCFA
                    @endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <hr class="divider">

    {{-- Totaux --}}
    <div class="totals">
        <div class="total-row">
            <span>Sous-total HT</span>
            <span>{{ number_format($sale->total_ht, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="total-row">
            <span>TVA</span>
            <span>{{ number_format($sale->total_vat, 0, ',', ' ') }} FCFA</span>
        </div>
        @if($sale->discount_percent > 0)
        <div class="total-row">
            <span>Remise ({{ number_format($sale->discount_percent, 0) }}%)</span>
            <span>-{{ number_format(($sale->total_ht + $sale->total_vat) * $sale->discount_percent / (100 - $sale->discount_percent), 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @if($sale->aib_amount > 0)
        <div class="total-row">
            <span>AIB ({{ number_format($sale->aib_rate ?? 0, 0) }}%)</span>
            <span>{{ number_format($sale->aib_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif

        <hr class="divider-double">

        <div class="total-row grand">
            <span>TOTAL TTC</span>
            <span>{{ number_format($sale->total, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>

    <hr class="divider">

    {{-- Informations de paiement --}}
    <div class="payment-info">
        @php
            $paymentLabels = [
                'cash' => 'Espèces',
                'card' => 'Carte bancaire',
                'mobile' => 'Mobile Money',
                'mixed' => 'Paiement mixte',
                'transfer' => 'Virement',
            ];
        @endphp
        <div class="total-row">
            <span>Mode de paiement</span>
            <span class="bold">{{ $paymentLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</span>
        </div>

        @if($sale->payment_method === 'mixed' && $sale->payment_details)
            @if(($sale->payment_details['cash'] ?? 0) > 0)
            <div class="total-row small">
                <span>  └ Espèces</span>
                <span>{{ number_format($sale->payment_details['cash'], 0, ',', ' ') }} FCFA</span>
            </div>
            @endif
            @if(($sale->payment_details['card'] ?? 0) > 0)
            <div class="total-row small">
                <span>  └ Carte</span>
                <span>{{ number_format($sale->payment_details['card'], 0, ',', ' ') }} FCFA</span>
            </div>
            @endif
            @if(($sale->payment_details['mobile'] ?? 0) > 0)
            <div class="total-row small">
                <span>  └ Mobile</span>
                <span>{{ number_format($sale->payment_details['mobile'], 0, ',', ' ') }} FCFA</span>
            </div>
            @endif
        @endif
    </div>

    {{-- Section e-MCeF (certification DGI Bénin) --}}
    @if($sale->emcef_nim || $sale->emcef_code_mecef)
    <hr class="divider">
    <div class="emcef-section center">
        <p class="bold">CERTIFIÉ e-MCeF / DGI Bénin</p>
        @if($sale->emcef_nim)
        <p>NIM: {{ $sale->emcef_nim }}</p>
        @endif
        @if($sale->emcef_code_mecef)
        <p>Code MECeF: {{ $sale->emcef_code_mecef }}</p>
        @endif
        @if($sale->emcef_counters)
        <p>Compteurs: {{ $sale->emcef_counters }}</p>
        @endif
        @if($sale->emcef_datetime)
        <p>Date DGI: {{ \Carbon\Carbon::parse($sale->emcef_datetime)->format('d/m/Y H:i:s') }}</p>
        @endif
    </div>

    {{-- QR Code e-MCeF --}}
    @if($sale->emcef_qr_code)
    <div class="qr-code">
        <img src="data:image/png;base64,{{ $sale->emcef_qr_code }}" alt="QR Code e-MCeF">
    </div>
    @endif
    @endif

    <hr class="divider">

    {{-- Pied de page --}}
    <div class="footer">
        <div class="thank-you">Merci pour votre achat !</div>
        @if($company->footer_text)
        <p>{{ $company->footer_text }}</p>
        @endif
        @if($company->website)
        <p>{{ $company->website }}</p>
        @endif
        <p class="xsmall" style="margin-top: 6px;">
            {{ $sale->invoice_number }} — {{ $sale->created_at->format('d/m/Y H:i') }}
        </p>
        <p class="xsmall">
            {{ $sale->items->count() }} article(s) — {{ number_format($sale->items->sum('quantity'), 0) }} unité(s)
        </p>
    </div>

    <script>
        // Auto-print si ouvert avec paramètre ?print=1
        if (new URLSearchParams(window.location.search).has('print')) {
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 300);
            });
        }
    </script>
</body>
</html>
