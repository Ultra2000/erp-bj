<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Dépenses - {{ $company->name }}</title>
    @php
        $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency;
        $methods = ['cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'transfer' => 'Virement', 'check' => 'Chèque', 'other' => 'Autre'];
    @endphp
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; padding: 15mm 16mm; }
        .header { border-bottom: 2px solid #b91c1c; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; }
        .company-name { font-size: 16px; font-weight: bold; color: #7f1d1d; }
        .doc-title { font-size: 13px; font-weight: bold; color: #b91c1c; text-transform: uppercase; letter-spacing: .5px; }
        .period { font-size: 9px; color: #555; margin-top: 3px; }
        .text-right { text-align: right; } .text-center { text-align: center; }
        .muted { color: #64748b; }
        .summary { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .summary td { padding: 6px 8px; border: 1px solid #e2e8f0; font-size: 10px; }
        .summary .lbl { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .summary .val { font-size: 12px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th { padding: 6px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #b91c1c; color: #7f1d1d; }
        table.data tbody td { padding: 5px 6px; font-size: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        table.data tbody tr:nth-child(even) td { background: #fff7f7; }
        table.data tfoot td { padding: 6px; font-weight: bold; border-top: 2px solid #b91c1c; font-size: 11px; }
        .cats { margin-bottom: 12px; }
        .cats .row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 10px; border-bottom: 1px dotted #e2e8f0; }
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; color: #64748b; font-size: 7.5px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table"><tr>
            <td>
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                @if($company->tax_number)<div class="muted" style="font-size:8px;">IFU : {{ $company->tax_number }}</div>@endif
            </td>
            <td class="text-right">
                <div class="doc-title">Dépenses</div>
                <div class="period">Du {{ $from->format('d/m/Y') }} au {{ $until->format('d/m/Y') }}</div>
                @if($categoryLabel)<div class="period">Catégorie : {{ $categoryLabel }}</div>@endif
                <div class="period">{{ $count }} dépense(s)</div>
            </td>
        </tr></table>
    </div>

    <table class="summary">
        <tr>
            <td style="width:50%;"><div class="lbl">Total des dépenses</div><div class="val">{{ $fmt($total) }}</div></td>
            <td style="width:50%;"><div class="lbl">Nombre</div><div class="val">{{ number_format($count, 0, ',', ' ') }}</div></td>
        </tr>
    </table>

    @if($byCategory->count())
    <div style="font-size:9px; font-weight:bold; text-transform:uppercase; color:#7f1d1d; margin-bottom:4px;">Répartition par catégorie</div>
    <div class="cats">
        @foreach($byCategory as $cat => $amount)
            <div class="row"><span>{{ $cat }}</span><span style="font-weight:bold;">{{ $fmt($amount) }}</span></div>
        @endforeach
    </div>
    @endif

    <table class="data">
        <thead><tr>
            <th style="width:12%;">Date</th>
            <th style="width:20%;">Catégorie</th>
            <th style="width:24%;">Bénéficiaire</th>
            <th style="width:14%;">Mode</th>
            <th style="width:16%;" class="text-right">Montant</th>
            <th style="width:14%;">Pièce</th>
        </tr></thead>
        <tbody>
            @forelse($expenses as $e)
            <tr>
                <td class="muted">{{ \Illuminate\Support\Carbon::parse($e->spent_at)->format('d/m/Y') }}</td>
                <td>{{ $e->category?->name ?? '—' }}</td>
                <td>{{ $e->beneficiary_label ?? '—' }}@if($e->notes)<div class="muted" style="font-size:8px;">{{ $e->notes }}</div>@endif</td>
                <td class="muted">{{ $methods[$e->payment_method] ?? ($e->payment_method ?? '—') }}</td>
                <td class="text-right" style="font-weight:bold;">{{ $fmt($e->amount) }}</td>
                <td class="muted">{{ $e->reference ?: '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center muted" style="padding:14px;">Aucune dépense sur cette période.</td></tr>
            @endforelse
        </tbody>
        @if($count)
        <tfoot><tr>
            <td colspan="4">TOTAL</td>
            <td class="text-right">{{ $fmt($total) }}</td>
            <td></td>
        </tr></tfoot>
        @endif
    </table>

    <div class="footer">{{ $company->name }} &bull; État des dépenses généré le @dt($generatedAt) &bull; Document interne</div>
</body>
</html>
