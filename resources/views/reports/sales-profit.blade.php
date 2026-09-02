<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport de rentabilité - {{ $company->name }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
            padding: 15mm 16mm;
        }

        .header { border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; }
        .company-name { font-size: 16px; font-weight: bold; color: #134e4a; }
        .doc-title { font-size: 13px; font-weight: bold; color: #0d9488; text-transform: uppercase; letter-spacing: .5px; }
        .period { font-size: 9px; color: #555; margin-top: 3px; }
        .muted { color: #64748b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Cartes de synthèse */
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 6px; }
        .summary td { width: 25%; vertical-align: top; }
        .card { border: 1px solid #cbd5e1; border-top: 2px solid #0d9488; padding: 7px 9px; }
        .card .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; }
        .card .value { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .card .value.pos { color: #166534; }
        .card .value.neg { color: #b91c1c; }
        .card.hl { background: #f0fdfa; border-color: #99f6e4; }

        .note { border: 1px dashed #d97706; background: #fffbeb; color: #92400e; padding: 6px 9px; font-size: 8px; margin: 8px 0 12px; }

        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #0d9488; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #0d9488; }

        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th {
            padding: 5px 6px; text-align: left; font-size: 7.5px; font-weight: bold; text-transform: uppercase;
            letter-spacing: .3px; border-bottom: 2px solid #0d9488; color: #134e4a;
        }
        table.data tbody td { padding: 4px 6px; font-size: 9px; border-bottom: 1px solid #eee; }
        table.data tbody tr:nth-child(even) td { background: #f7fbfb; }
        table.data tfoot td { padding: 6px; font-size: 9.5px; font-weight: bold; border-top: 2px solid #0d9488; }
        .loss td { background: #fef2f2 !important; color: #b91c1c; }
        .pos { color: #166534; }
        .neg { color: #b91c1c; }

        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; color: #64748b; font-size: 7.5px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' FCFA';
    $pct = fn ($v) => number_format((float) $v, 1, ',', ' ') . ' %';
    $profitClass = $totals['profit'] >= 0 ? 'pos' : 'neg';
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                @if($company->tax_number)<div class="muted" style="font-size:8px;">N° Fiscal : {{ $company->tax_number }}</div>@endif
            </td>
            <td class="text-right">
                <div class="doc-title">Rentabilité des ventes</div>
                <div class="period">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- Synthèse ligne 1 : activité --}}
<table class="summary">
    <tr>
        <td><div class="card"><div class="label">Ventes</div><div class="value">{{ number_format($totals['sales_count'], 0, ',', ' ') }}</div></div></td>
        <td><div class="card"><div class="label">Quantité vendue</div><div class="value">{{ number_format($totals['qty'], 0, ',', ' ') }}</div></div></td>
        <td><div class="card"><div class="label">Panier moyen HT</div><div class="value">{{ $fmt($totals['avg_basket']) }}</div></div></td>
        <td><div class="card"><div class="label">Remises accordées</div><div class="value neg">{{ $fmt($totals['discount']) }}</div></div></td>
    </tr>
</table>

{{-- Synthèse ligne 2 : rentabilité --}}
<table class="summary">
    <tr>
        <td><div class="card"><div class="label">CA HT (net remise)</div><div class="value">{{ $fmt($totals['revenue']) }}</div></div></td>
        <td><div class="card"><div class="label">Coût d'achat HT</div><div class="value">{{ $fmt($totals['cost']) }}</div></div></td>
        <td><div class="card hl"><div class="label">Bénéfice brut</div><div class="value {{ $profitClass }}">{{ $fmt($totals['profit']) }}</div></div></td>
        <td><div class="card hl"><div class="label">Marge / Marque</div><div class="value {{ $profitClass }}">{{ $pct($totals['margin']) }}</div><div class="muted" style="font-size:7px;">marque {{ $pct($totals['markup']) }}</div></div></td>
    </tr>
</table>

@if($missingCost)
<div class="note">
    <strong>Attention :</strong> certains produits n'ont pas de prix d'achat renseigné (coût = 0). Le bénéfice est <strong>surestimé</strong> pour ces lignes (marquées d'un « * »). Renseignez le prix d'achat des produits concernés pour une rentabilité exacte.
</div>
@endif

{{-- Détail par produit --}}
<div class="section-title">Rentabilité par produit ({{ count($products) }})</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:34%;">Produit</th>
            <th style="width:10%;" class="text-center">Qté</th>
            <th style="width:17%;" class="text-right">CA HT net</th>
            <th style="width:17%;" class="text-right">Coût HT</th>
            <th style="width:14%;" class="text-right">Bénéfice</th>
            <th style="width:8%;" class="text-right">Marge</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        <tr class="{{ $p['profit'] < 0 ? 'loss' : '' }}">
            <td>{{ $p['name'] }}@if($p['no_cost']) <span style="color:#d97706;font-weight:bold;">*</span>@endif</td>
            <td class="text-center">{{ number_format($p['qty'], 0, ',', ' ') }}</td>
            <td class="text-right">{{ $fmt($p['revenue']) }}</td>
            <td class="text-right muted">{{ $fmt($p['cost']) }}</td>
            <td class="text-right {{ $p['profit'] >= 0 ? 'pos' : 'neg' }}">{{ $fmt($p['profit']) }}</td>
            <td class="text-right {{ $p['margin'] >= 0 ? 'pos' : 'neg' }}">{{ $pct($p['margin']) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center" style="padding:14px;color:#999;">Aucune vente sur la période.</td></tr>
        @endforelse
    </tbody>
    @if(count($products))
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td class="text-center">{{ number_format($totals['qty'], 0, ',', ' ') }}</td>
            <td class="text-right">{{ $fmt($totals['revenue']) }}</td>
            <td class="text-right">{{ $fmt($totals['cost']) }}</td>
            <td class="text-right {{ $profitClass }}">{{ $fmt($totals['profit']) }}</td>
            <td class="text-right {{ $profitClass }}">{{ $pct($totals['margin']) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Ventes à perte --}}
@if(count($lossProducts))
<div class="section-title" style="color:#b91c1c;border-color:#b91c1c;">⚠ Produits vendus à perte ({{ count($lossProducts) }})</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:50%;">Produit</th>
            <th style="width:12%;" class="text-center">Qté</th>
            <th style="width:19%;" class="text-right">CA HT net</th>
            <th style="width:19%;" class="text-right">Perte</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lossProducts as $p)
        <tr class="loss">
            <td>{{ $p['name'] }}@if($p['no_cost']) <span style="font-weight:bold;">*</span>@endif</td>
            <td class="text-center">{{ number_format($p['qty'], 0, ',', ' ') }}</td>
            <td class="text-right">{{ $fmt($p['revenue']) }}</td>
            <td class="text-right neg">{{ $fmt($p['profit']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Rapport de rentabilité généré le @dt(now()) &bull; {{ $company->name }} &bull; Coût estimé au prix d'achat courant des produits &bull; Document à usage interne
</div>

</body>
</html>
