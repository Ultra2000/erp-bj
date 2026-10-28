<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Synthèse tontine - {{ $company->name }}</title>
    @php $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency; @endphp
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
        .summary { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .summary td { padding: 6px 8px; border: 1px solid #e2e8f0; font-size: 10px; }
        .summary .lbl { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .summary .val { font-size: 12px; font-weight: bold; }
        .pos { color: #047857; } .neg { color: #b91c1c; }
        h2 { font-size: 10px; text-transform: uppercase; color: #312e81; margin: 10px 0 4px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
        table.data thead th { padding: 6px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #4f46e5; color: #312e81; }
        table.data tbody td { padding: 5px 6px; font-size: 10px; border-bottom: 1px solid #eee; }
        table.data tbody tr:nth-child(even) td { background: #f8f8ff; }
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
                <div class="doc-title">Synthèse tontine</div>
                <div class="period">Du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}</div>
                <div class="period">{{ $total_ops }} opération(s)</div>
            </td>
        </tr></table>
    </div>

    <table class="summary"><tr>
        <td style="width:25%;"><div class="lbl">Dépôts collectés</div><div class="val pos">+ {{ $fmt($deposited) }}</div><div class="muted" style="font-size:8px;">{{ $deposited_nb }} dépôt(s)</div></td>
        <td style="width:25%;"><div class="lbl">Retraits</div><div class="val neg">- {{ $fmt($withdrawn) }}</div><div class="muted" style="font-size:8px;">{{ $withdrawn_nb }} retrait(s)</div></td>
        <td style="width:25%;"><div class="lbl">Utilisés (achats)</div><div class="val neg">- {{ $fmt($used) }}</div></td>
        <td style="width:25%;"><div class="lbl">Frais de tenue</div><div class="val">{{ $fmt($fees) }}</div><div class="muted" style="font-size:8px;">{{ $fees_nb }} compte(s)</div></td>
    </tr></table>

    <h2>Collecte par tontinier</h2>
    <table class="data">
        <thead><tr><th style="width:55%;">Tontinier</th><th style="width:20%;" class="text-center">Dépôts</th><th style="width:25%;" class="text-right">Collecté</th></tr></thead>
        <tbody>
            @forelse($agentRows as $a)
            <tr><td style="font-weight:bold;">{{ $a['name'] }}</td><td class="text-center">{{ number_format($a['nb'], 0, ',', ' ') }}</td><td class="text-right pos" style="font-weight:bold;">{{ $fmt($a['total']) }}</td></tr>
            @empty
            <tr><td colspan="3" class="text-center muted" style="padding:10px;">Aucune collecte.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Collecte par zone</h2>
    <table class="data">
        <thead><tr><th style="width:55%;">Zone</th><th style="width:20%;" class="text-center">Dépôts</th><th style="width:25%;" class="text-right">Collecté</th></tr></thead>
        <tbody>
            @forelse($zoneRows as $z)
            <tr><td style="font-weight:bold;">{{ $z['name'] }}</td><td class="text-center">{{ number_format($z['nb'], 0, ',', ' ') }}</td><td class="text-right" style="font-weight:bold;">{{ $fmt($z['total']) }}</td></tr>
            @empty
            <tr><td colspan="3" class="text-center muted" style="padding:10px;">Aucune collecte par zone.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">{{ $company->name }} &bull; Synthèse tontine générée le @dt($generatedAt) &bull; Document interne</div>
</body>
</html>
