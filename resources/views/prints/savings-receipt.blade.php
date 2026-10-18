<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isDeposit = $tx->amount >= 0;
        $title = match ($tx->type) {
            'deposit' => 'REÇU DE DÉPÔT',
            'withdrawal' => 'REÇU DE RETRAIT',
            'refund' => 'REÇU DE REMBOURSEMENT',
            'adjustment' => 'AJUSTEMENT ÉPARGNE',
            default => 'REÇU ÉPARGNE',
        };
        $ref = $tx->reference ?: ('EP-' . str_pad((string) $tx->id, 6, '0', STR_PAD_LEFT));
        $paymentLabels = [
            'cash' => 'Espèces',
            'card' => 'Carte bancaire',
            'transfer' => 'Virement',
            'mobile_money' => 'Mobile Money',
            'mobile' => 'Mobile Money',
        ];
        $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' FCFA';
    @endphp
    <title>{{ $title }} — {{ $ref }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
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
        .divider { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        .divider-double { border: none; border-top: 2px solid #000; margin: 6px 0; }
        .company-name { font-size: 18px; font-weight: 700; letter-spacing: 1px; }
        .header-info p { font-size: 10px; line-height: 1.4; }
        .ticket-title { font-size: 14px; font-weight: 700; letter-spacing: 2px; margin: 6px 0; }
        .meta-row { display: flex; justify-content: space-between; font-size: 10px; padding: 1px 0; }
        .amount-box { text-align: center; margin: 8px 0; padding: 6px; border: 2px solid #000; }
        .amount-box .label { font-size: 10px; letter-spacing: 1px; }
        .amount-box .value { font-size: 22px; font-weight: 700; margin-top: 2px; }
        .total-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 12px; }
        .total-row.grand { font-size: 15px; font-weight: 700; padding: 4px 0; }
        .footer { margin-top: 8px; font-size: 9px; text-align: center; line-height: 1.5; }
        .footer .thank-you { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .actions { text-align: center; margin: 15px auto; padding: 10px; }
        .actions button { padding: 10px 30px; font-size: 16px; font-weight: 600; cursor: pointer; border-radius: 8px; border: none; margin: 4px; }
        .btn-print { background: #059669; color: white; }
        .btn-close { background: #6b7280; color: white; }
        @media print {
            body { margin: 0; padding: 2mm; }
            .actions { display: none !important; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    {{-- Boutons d'action (masqués à l'impression) --}}
    <div class="actions">
        <button class="btn-print" onclick="window.print()">Imprimer le reçu</button>
        <button class="btn-close" onclick="window.close()">Fermer</button>
    </div>

    {{-- En-tête entreprise --}}
    <div class="center">
        @if($company && $company->logo_path)
            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" style="max-width: 50mm; max-height: 15mm; margin-bottom: 4px;">
        @endif
        <div class="company-name">{{ strtoupper($company->name ?? 'ENTREPRISE') }}</div>
        <div class="header-info">
            @if($company?->address)<p>{{ $company->address }}</p>@endif
            @if($company?->city)<p>{{ $company->city }}@if($company->zip_code) {{ $company->zip_code }}@endif</p>@endif
            @if($company?->phone)<p>Tél: {{ $company->phone }}</p>@endif
            @if($company?->tax_number)<p>IFU: {{ $company->tax_number }}</p>@endif
        </div>
    </div>

    <hr class="divider-double">

    {{-- Titre --}}
    <div class="center">
        <div class="ticket-title">{{ $title }}</div>
        <div class="xsmall">Épargne / Tontine</div>
    </div>

    {{-- Métadonnées --}}
    <div class="meta-row">
        <span>N°: {{ $ref }}</span>
        <span>@dt($tx->created_at, 'd/m/Y H:i')</span>
    </div>
    <div class="meta-row">
        <span>Client: {{ $tx->customer?->name ?? '—' }}</span>
    </div>
    @if($tx->customer?->phone)
    <div class="meta-row small">
        <span>Tél: {{ $tx->customer->phone }}</span>
    </div>
    @endif
    <div class="meta-row small">
        <span>Opérateur: {{ $tx->createdBy?->name ?? auth()->user()->name }}</span>
    </div>

    {{-- Montant du mouvement --}}
    <div class="amount-box">
        <div class="label">{{ $isDeposit ? 'MONTANT DÉPOSÉ' : 'MONTANT RETIRÉ' }}</div>
        <div class="value">{{ ($isDeposit ? '+' : '-') . ' ' . number_format(abs((int) $tx->amount), 0, ',', ' ') }} FCFA</div>
    </div>

    {{-- Mode de paiement --}}
    @if($tx->payment_method)
    <div class="total-row">
        <span>Mode</span>
        <span class="bold">{{ $paymentLabels[$tx->payment_method] ?? ucfirst($tx->payment_method) }}</span>
    </div>
    @endif

    <hr class="divider">

    {{-- Nouveau solde --}}
    <div class="total-row grand">
        <span>NOUVEAU SOLDE</span>
        <span>{{ $fmt($tx->balance_after) }}</span>
    </div>

    @if($tx->notes)
    <hr class="divider">
    <div class="small"><span class="bold">Note :</span> {{ $tx->notes }}</div>
    @endif

    <hr class="divider">

    {{-- Pied de page --}}
    <div class="footer">
        <div class="thank-you">Merci de votre confiance !</div>
        <p class="xsmall">Document interne non fiscal — à conserver</p>
        <p class="xsmall" style="margin-top: 4px;">{{ $ref }} — @dt($tx->created_at, 'd/m/Y H:i')</p>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).has('print')) {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>
