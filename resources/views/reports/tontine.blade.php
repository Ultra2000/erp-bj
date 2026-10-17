<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tontine - {{ $company->name }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px; color: #1e293b; line-height: 1.4; padding: 15mm 16mm;
        }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; }
        .company-name { font-size: 16px; font-weight: bold; color: #312e81; }
        .doc-title { font-size: 13px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: .5px; }
        .period { font-size: 9px; color: #555; margin-top: 3px; }
        .text-right { text-align: right; }
        .muted { color: #64748b; }

        .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 8px; }
        .summary td { width: 33.33%; vertical-align: top; }
        .card { border: 1px solid #cbd5e1; border-top: 2px solid #4f46e5; padding: 8px 10px; }
        .card .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; }
        .card .value { font-size: 15px; font-weight: bold; color: #312e81; margin-top: 2px; }
        .card .sub { font-size: 8px; color: #64748b; margin-top: 2px; }
        .mv .row { font-size: 9px; }
        .mv .in { color: #166534; }
        .mv .out { color: #b91c1c; }

        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #4f46e5; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #4f46e5; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th { padding: 5px 6px; text-align: left; font-size: 7.5px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #4f46e5; color: #312e81; }
        table.data tbody td { padding: 4px 6px; font-size: 9px; border-bottom: 1px solid #eee; }
        table.data tbody tr:nth-child(even) td { background: #f8fafc; }
        .pos { color: #166534; } .neg { color: #b91c1c; }
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; color: #64748b; font-size: 7.5px; }
    </style>
</head>
<body>
@php $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency; @endphp

<div class="header">
    <table class="header-table"><tr>
        <td>
            <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
            @if($company->tax_number)<div class="muted" style="font-size:8px;">N° Fiscal : {{ $company->tax_number }}</div>@endif
        </td>
        <td class="text-right">
            <div class="doc-title">Tontine — État de l'épargne</div>
            <div class="period">Au {{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>
        </td>
    </tr></table>
</div>

{{-- Synthèse --}}
<table class="summary"><tr>
    <td><div class="card"><div class="label">Total épargné (dû aux clients)</div><div class="value">{{ $fmt($total) }}</div></div></td>
    <td><div class="card"><div class="label">Épargnants</div><div class="value">{{ number_format($savers, 0, ',', ' ') }}</div><div class="sub">Solde moyen : {{ $fmt($average) }}</div></div></td>
    <td><div class="card mv"><div class="label">Mouvements — {{ $month }}</div>
        <div class="row in">Dépôts : + {{ $fmt($deposits_month) }}</div>
        <div class="row out">Retraits : - {{ $fmt($withdrawals_month) }}</div>
        <div class="row out">Utilisés : - {{ $fmt($used_month) }}</div>
    </div></td>
</tr></table>

{{-- Top épargnants --}}
<div class="section-title">Top épargnants ({{ $topSavers->count() }})</div>
<table class="data">
    <thead><tr>
        <th style="width:8%;">#</th>
        <th style="width:50%;">Client</th>
        <th style="width:22%;">Téléphone</th>
        <th style="width:20%;" class="text-right">Solde</th>
    </tr></thead>
    <tbody>
        @forelse($topSavers as $i => $c)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $c->name }}</td>
            <td class="muted">{{ $c->phone ?: '—' }}</td>
            <td class="text-right" style="font-weight:bold;">{{ $fmt($c->savings_balance) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:12px;color:#999;">Aucun client avec un solde d'épargne.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Derniers mouvements --}}
<div class="section-title">Derniers mouvements</div>
<table class="data">
    <thead><tr>
        <th style="width:16%;">Date</th>
        <th style="width:34%;">Client</th>
        <th style="width:20%;">Opération</th>
        <th style="width:15%;" class="text-right">Montant</th>
        <th style="width:15%;" class="text-right">Solde après</th>
    </tr></thead>
    <tbody>
        @forelse($recent as $t)
        <tr>
            <td>@dt($t->created_at, 'd/m/Y H:i')</td>
            <td>{{ $t->customer?->name ?? '—' }}</td>
            <td>{{ \App\Models\SavingsTransaction::TYPES[$t->type] ?? $t->type }}</td>
            <td class="text-right {{ $t->amount >= 0 ? 'pos' : 'neg' }}">{{ ($t->amount >= 0 ? '+' : '') . number_format((int) $t->amount, 0, ',', ' ') }} {{ $currency }}</td>
            <td class="text-right">{{ $fmt($t->balance_after) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:12px;color:#999;">Aucun mouvement.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    {{ $company->name }} &bull; État de l'épargne (tontine) généré le @dt($generatedAt) &bull; Document interne
</div>

</body>
</html>
