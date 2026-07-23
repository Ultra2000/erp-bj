<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Créances clients - {{ $company->name }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #2d3748;
            padding: 14mm 14mm;
        }
        .header {
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .company-name { font-size: 16px; font-weight: bold; color: #1a202c; }
        .company-details { font-size: 8px; color: #718096; line-height: 1.5; margin-top: 3px; }
        .report-title { text-align: right; }
        .report-title .t { font-size: 15px; font-weight: bold; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px; }
        .report-title .d { font-size: 8px; color: #718096; margin-top: 4px; }

        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td {
            width: 33.33%;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
        }
        .summary .label { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; color: #718096; }
        .summary .value { font-size: 15px; font-weight: bold; color: #1a202c; margin-top: 2px; }
        .summary .value.debt { color: #b91c1c; }

        table.list { width: 100%; border-collapse: collapse; }
        table.list thead th {
            background: #b91c1c;
            color: #fff;
            padding: 6px 8px;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }
        table.list tbody td {
            padding: 6px 8px;
            font-size: 9px;
            border-bottom: 1px solid #edf2f7;
        }
        table.list tbody tr:nth-child(even) td { background: #f7fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .age-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
        }
        .age-old { background: #fee2e2; color: #b91c1c; }
        .age-mid { background: #fef3c7; color: #b45309; }
        .age-new { background: #edf2f7; color: #4a5568; }
        tfoot td {
            padding: 8px;
            font-size: 11px;
            font-weight: bold;
            border-top: 2px solid #b91c1c;
            color: #b91c1c;
        }
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 7px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        .empty { text-align: center; padding: 30px; color: #a0aec0; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-details">
                        @if($company->address){{ $company->address }}<br>@endif
                        @if($company->phone)Tel: {{ $company->phone }}@endif
                        @if($company->email) &bull; {{ $company->email }}@endif
                        @if($company->tax_number)<br>IFU: {{ $company->tax_number }}@endif
                    </div>
                </td>
                <td class="report-title">
                    <div class="t">État des créances clients</div>
                    <div class="d">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Total des créances</div>
                <div class="value debt">{{ number_format($total, 0, ',', ' ') }} FCFA</div>
            </td>
            <td>
                <div class="label">Clients débiteurs</div>
                <div class="value">{{ count($debtors) }}</div>
            </td>
            <td>
                <div class="label">Factures impayées</div>
                <div class="value">{{ $totalInvoices }}</div>
            </td>
        </tr>
    </table>

    @if(count($debtors) > 0)
    <table class="list">
        <thead>
            <tr>
                <th style="width: 28%;">Client</th>
                <th style="width: 16%;">Téléphone</th>
                <th style="width: 10%;" class="text-center">Factures</th>
                <th style="width: 18%;" class="text-right">Montant dû</th>
                <th style="width: 14%;">Depuis le</th>
                <th style="width: 14%;" class="text-center">Ancienneté</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debtors as $d)
            <tr>
                <td style="font-weight: 600;">{{ $d['name'] }}</td>
                <td>{{ $d['phone'] ?: '—' }}</td>
                <td class="text-center">{{ $d['debt_count'] }}</td>
                <td class="text-right" style="font-weight: bold; color: #b91c1c;">{{ number_format($d['debt_total'], 0, ',', ' ') }} FCFA</td>
                <td>{{ $d['oldest_date'] }}</td>
                <td class="text-center">
                    <span class="age-badge {{ $d['days'] > 30 ? 'age-old' : ($d['days'] > 15 ? 'age-mid' : 'age-new') }}">
                        {{ $d['days'] }} j
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">TOTAL DES CRÉANCES</td>
                <td class="text-right">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="empty">Aucune créance — toutes les factures clients sont soldées.</div>
    @endif

    <div class="footer">
        Document interne de suivi des créances &bull; {{ $company->name }} &bull; {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
