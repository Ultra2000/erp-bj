<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relevé épargne - {{ $customer->name }}</title>
    @php
        $typeLabels = \App\Models\SavingsTransaction::TYPES;
        $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency;
    @endphp
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; padding: 15mm 16mm; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; }
        .company-name { font-size: 16px; font-weight: bold; color: #312e81; }
        .doc-title { font-size: 13px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: .5px; }
        .period { font-size: 9px; color: #555; margin-top: 3px; }
        .text-right { text-align: right; } .text-center { text-align: center; }
        .muted { color: #64748b; }
        .client-box { border: 1px solid #e2e8f0; background: #f8fafc; padding: 8px 12px; margin-bottom: 12px; }
        .client-box .name { font-size: 12px; font-weight: bold; }
        .summary { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .summary td { padding: 6px 8px; border: 1px solid #e2e8f0; font-size: 10px; }
        .summary .lbl { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .summary .val { font-size: 12px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th { padding: 6px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #4f46e5; color: #312e81; }
        table.data tbody td { padding: 5px 6px; font-size: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        table.data tbody tr:nth-child(even) td { background: #f8f8ff; }
        .pos { color: #047857; } .neg { color: #b91c1c; }
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; color: #64748b; font-size: 7.5px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table"><tr>
            <td>
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                @if($company->tax_number)<div class="muted" style="font-size:8px;">IFU : {{ $company->tax_number }}</div>@endif
                @if($company->phone)<div class="muted" style="font-size:8px;">Tél : {{ $company->phone }}</div>@endif
            </td>
            <td class="text-right">
                <div class="doc-title">Relevé d'épargne</div>
                <div class="period">Du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}</div>
            </td>
        </tr></table>
    </div>

    <div class="client-box">
        <div class="name">{{ $customer->name }}</div>
        <div class="muted">
            @if($customer->phone)Tél : {{ $customer->phone }}@endif
            @if($customer->registration_number) &bull; IFU : {{ $customer->registration_number }}@endif
        </div>
    </div>

    <table class="summary">
        <tr>
            <td style="width:20%;"><div class="lbl">Solde d'ouverture</div><div class="val">{{ $fmt($opening) }}</div></td>
            <td style="width:20%;"><div class="lbl">Dépôts</div><div class="val pos">+ {{ $fmt($deposits) }}</div></td>
            <td style="width:20%;"><div class="lbl">Retraits</div><div class="val neg">- {{ $fmt($withdrawals) }}</div></td>
            <td style="width:20%;"><div class="lbl">Utilisés (achats)</div><div class="val neg">- {{ $fmt($purchases) }}</div></td>
            <td style="width:20%;"><div class="lbl">Solde de clôture</div><div class="val">{{ $fmt($closing) }}</div></td>
        </tr>
    </table>

    <table class="data">
        <thead><tr>
            <th style="width:16%;">Date</th>
            <th style="width:22%;">Opération</th>
            <th style="width:20%;" class="text-right">Montant</th>
            <th style="width:20%;" class="text-right">Solde après</th>
            <th style="width:22%;">Opérateur</th>
        </tr></thead>
        <tbody>
            {{-- Ligne solde d'ouverture --}}
            <tr>
                <td class="muted">{{ $start->format('d/m/Y') }}</td>
                <td class="muted" colspan="2">Solde d'ouverture</td>
                <td class="text-right" style="font-weight:bold;">{{ $fmt($opening) }}</td>
                <td></td>
            </tr>
            @forelse($movements as $t)
            <tr>
                <td class="muted">@dt($t->created_at, 'd/m/Y H:i')</td>
                <td>{{ $typeLabels[$t->type] ?? $t->type }}@if($t->notes)<div class="muted" style="font-size:8px;">{{ $t->notes }}</div>@endif</td>
                <td class="text-right {{ $t->amount >= 0 ? 'pos' : 'neg' }}" style="font-weight:bold;">
                    {{ ($t->amount >= 0 ? '+' : '') . number_format((int) $t->amount, 0, ',', ' ') }}
                </td>
                <td class="text-right">{{ $fmt($t->balance_after) }}</td>
                <td class="muted">{{ $t->createdBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center muted" style="padding:14px;">Aucun mouvement sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary" style="margin-top:12px;">
        <tr>
            <td class="text-right" style="font-weight:bold; font-size:12px; border:none;">Solde de clôture : {{ $fmt($closing) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $company->name }} &bull; Relevé d'épargne / tontine généré le @dt($generatedAt) &bull; Document interne non fiscal
    </div>
</body>
</html>
