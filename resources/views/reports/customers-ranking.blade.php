<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Classement clients - {{ $company->name }}</title>
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
        .note { border: 1px dashed #4f46e5; background: #eef2ff; padding: 8px 12px; margin-bottom: 12px; font-size: 10px; color: #312e81; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th { padding: 6px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #4f46e5; color: #312e81; }
        table.data tbody td { padding: 5px 6px; font-size: 10px; border-bottom: 1px solid #eee; }
        table.data tbody tr:nth-child(even) td { background: #f8f8ff; }
        table.data tfoot td { padding: 6px; font-weight: bold; border-top: 2px solid #4f46e5; font-size: 10px; }
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; color: #64748b; font-size: 7.5px; }
    </style>
</head>
<body>
@php $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $currency; @endphp

<div class="header">
    <table class="header-table"><tr>
        <td>
            <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
            @if($company->tax_number)<div class="muted" style="font-size:8px;">N° Fiscal : {{ $company->tax_number }}</div>@endif
        </td>
        <td class="text-right">
            <div class="doc-title">Classement des clients</div>
            <div class="period">Du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}</div>
            <div class="period">{{ $clients_count }} client(s) — {{ $total_sales }} vente(s) — CA total : {{ $fmt($total_ca) }}</div>
        </td>
    </tr></table>
</div>

@if($biggest && $biggest['amount'] > 0)
<div class="note"><strong>Plus grosse vente de la période :</strong> {{ $fmt($biggest['amount']) }} par <strong>{{ $biggest['client'] }}</strong> ({{ $biggest['invoice'] }} — {{ $biggest['date'] }}).</div>
@endif

<table class="data">
    <thead><tr>
        <th style="width:6%;">#</th>
        <th style="width:30%;">Client</th>
        <th style="width:10%;" class="text-center">Achats</th>
        <th style="width:16%;" class="text-right">CA</th>
        <th style="width:15%;" class="text-right">+ gros achat</th>
        <th style="width:12%;" class="text-right">Panier moy.</th>
        <th style="width:11%;" class="text-right">Dernier</th>
    </tr></thead>
    <tbody>
        @forelse($customers as $i => $c)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-weight:bold;">{{ $c['name'] }}@if($c['phone'])<div class="muted" style="font-size:8px;font-weight:normal;">{{ $c['phone'] }}</div>@endif</td>
            <td class="text-center">{{ number_format($c['nb'], 0, ',', ' ') }}</td>
            <td class="text-right" style="font-weight:bold;">{{ $fmt($c['ca']) }}</td>
            <td class="text-right">{{ $fmt($c['max_sale']) }}</td>
            <td class="text-right muted">{{ $fmt($c['avg']) }}</td>
            <td class="text-right muted">{{ $c['last_at'] }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:14px;color:#999;">Aucune vente client sur cette période.</td></tr>
        @endforelse
    </tbody>
    @if(count($customers))
    <tfoot><tr>
        <td colspan="2">TOTAL</td>
        <td class="text-center">{{ number_format($total_sales, 0, ',', ' ') }}</td>
        <td class="text-right">{{ $fmt($total_ca) }}</td>
        <td colspan="3"></td>
    </tr></tfoot>
    @endif
</table>

<div class="footer">{{ $company->name }} &bull; Classement des clients généré le @dt($generatedAt) &bull; Document interne</div>
</body>
</html>
