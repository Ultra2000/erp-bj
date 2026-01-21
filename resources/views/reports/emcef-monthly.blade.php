<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport e-MCeF {{ $monthName }} {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            opacity: 0.9;
        }
        .header .company {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        .header .period {
            font-size: 16px;
            margin-top: 5px;
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
        }
        .section {
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .section-header {
            background: #f8fafc;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }
        .section-content {
            padding: 15px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .stats-row {
            display: table-row;
        }
        .stats-cell {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border-right: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .stats-cell:last-child {
            border-right: none;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .stats-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .stats-detail {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
        }
        .stats-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .highlight {
            background: #dbeafe;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .highlight-label {
            font-size: 9px;
            color: #1e40af;
            font-weight: bold;
        }
        .highlight-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table thead th {
            background: #f1f5f9;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #e2e8f0;
        }
        table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-primary { color: #1e40af; }
        .counters-grid {
            display: table;
            width: 100%;
        }
        .counter-cell {
            display: table-cell;
            width: 25%;
            padding: 10px;
            background: #f8fafc;
            border-radius: 4px;
            margin: 5px;
        }
        .counter-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
        }
        .counter-value {
            font-family: monospace;
            font-size: 10px;
            margin-top: 3px;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Rapport e-MCeF (DGI Bénin)</h1>
        <div class="subtitle">Récapitulatif mensuel des factures certifiées</div>
        <div class="company">{{ $company->name }}</div>
        <div class="period">{{ $monthName }} {{ $year }}</div>
    </div>

    {{-- Résumé --}}
    <div class="section">
        <div class="section-header">📊 Synthèse du mois</div>
        <div class="section-content">
            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stats-cell">
                        <div class="stats-number text-success">{{ $stats['total_invoices'] }}</div>
                        <div class="stats-label">Factures certifiées</div>
                        <div class="stats-detail">
                            <div>HT: {{ number_format($stats['total_ht'], 0, ',', ' ') }} FCFA</div>
                            <div>TVA: {{ number_format($stats['total_vat'], 0, ',', ' ') }} FCFA</div>
                            <div class="font-bold">TTC: {{ number_format($stats['total_ttc'], 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                    <div class="stats-cell">
                        <div class="stats-number text-danger">{{ $stats['total_credit_notes'] }}</div>
                        <div class="stats-label">Avoirs émis</div>
                        <div class="stats-detail">
                            <div>HT: -{{ number_format($stats['credit_notes_ht'], 0, ',', ' ') }} FCFA</div>
                            <div>TVA: -{{ number_format($stats['credit_notes_vat'], 0, ',', ' ') }} FCFA</div>
                            <div class="font-bold">TTC: -{{ number_format($stats['credit_notes_ttc'], 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                    <div class="stats-cell">
                        <div class="stats-number text-primary">{{ $stats['total_invoices'] - $stats['total_credit_notes'] }}</div>
                        <div class="stats-label">Opérations nettes</div>
                        <div class="stats-detail">
                            <div>HT Net: {{ number_format($stats['net_ht'], 0, ',', ' ') }} FCFA</div>
                            <div class="highlight">
                                <div class="highlight-label">TVA nette à reverser</div>
                                <div class="highlight-value">{{ number_format($stats['net_vat'], 0, ',', ' ') }} FCFA</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Compteurs e-MCeF --}}
    <div class="section">
        <div class="section-header">🔢 Compteurs e-MCeF</div>
        <div class="section-content">
            <table>
                <tr>
                    <td style="width:25%; background:#f8fafc; padding:10px;">
                        <div class="counter-label">Premier NIM</div>
                        <div class="counter-value font-bold">{{ $stats['first_nim'] ?? '-' }}</div>
                    </td>
                    <td style="width:25%; background:#f8fafc; padding:10px;">
                        <div class="counter-label">Dernier NIM</div>
                        <div class="counter-value font-bold">{{ $stats['last_nim'] ?? '-' }}</div>
                    </td>
                    <td style="width:25%; background:#f8fafc; padding:10px;">
                        <div class="counter-label">Premier Code MECeF</div>
                        <div class="counter-value">{{ $stats['first_code_mecef'] ?? '-' }}</div>
                    </td>
                    <td style="width:25%; background:#f8fafc; padding:10px;">
                        <div class="counter-label">Dernier Code MECeF</div>
                        <div class="counter-value">{{ $stats['last_code_mecef'] ?? '-' }}</div>
                    </td>
                </tr>
            </table>
            @if($stats['counters'])
                <div style="margin-top:10px; padding:10px; background:#dbeafe; border-radius:4px;">
                    <div class="counter-label">Compteurs DGI (dernière facture)</div>
                    <div class="counter-value" style="font-size:9px;">{{ $stats['counters'] }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Ventilation TVA --}}
    @if(!empty($stats['vat_breakdown']))
    <div class="section">
        <div class="section-header">📈 Ventilation par groupe de taxe</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Groupe</th>
                        <th>Taux TVA</th>
                        <th class="text-center">Nb Factures</th>
                        <th class="text-right">Base HT</th>
                        <th class="text-right">TVA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['vat_breakdown'] as $row)
                        <tr>
                            <td><span class="badge badge-success">{{ $row['vat_category'] ?? 'A' }}</span></td>
                            <td>{{ number_format($row['vat_rate'], 0) }}%</td>
                            <td class="text-center">{{ $row['invoice_count'] }}</td>
                            <td class="text-right">{{ number_format($row['base_ht'], 0, ',', ' ') }} FCFA</td>
                            <td class="text-right font-bold text-primary">{{ number_format($row['vat_amount'], 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Ventilation paiements --}}
    @if(!empty($stats['payment_breakdown']))
    <div class="section">
        <div class="section-header">💳 Ventilation par mode de paiement</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Mode</th>
                        <th class="text-center">Transactions</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $paymentLabels = [
                            'cash' => 'Espèces',
                            'card' => 'Carte bancaire',
                            'transfer' => 'Virement',
                            'mobile_money' => 'Mobile Money',
                            'check' => 'Chèque',
                            'credit' => 'Crédit',
                            'other' => 'Autre',
                        ];
                    @endphp
                    @foreach($stats['payment_breakdown'] as $payment)
                        <tr>
                            <td>{{ $paymentLabels[$payment['payment_method']] ?? ucfirst($payment['payment_method'] ?? 'Autre') }}</td>
                            <td class="text-center">{{ $payment['count'] }}</td>
                            <td class="text-right font-bold">{{ number_format($payment['total'], 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Liste détaillée --}}
    @if($invoices->count() > 0)
    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">📄 Détail des factures certifiées ({{ $invoices->count() }})</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Type</th>
                        <th>Client</th>
                        <th>NIM</th>
                        <th class="text-right">HT</th>
                        <th class="text-right">TVA</th>
                        <th class="text-right">TTC</th>
                        <th>Date certif.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td class="font-bold">{{ $invoice->invoice_number }}</td>
                            <td>
                                @if($invoice->type === 'credit_note')
                                    <span class="badge badge-danger">Avoir</span>
                                @else
                                    <span class="badge badge-success">Facture</span>
                                @endif
                            </td>
                            <td>{{ $invoice->customer?->name ?? '-' }}</td>
                            <td style="font-family:monospace; font-size:8px;">{{ $invoice->emcef_nim }}</td>
                            <td class="text-right">{{ number_format($invoice->total_ht, 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($invoice->total_vat, 0, ',', ' ') }}</td>
                            <td class="text-right font-bold">{{ number_format($invoice->total, 0, ',', ' ') }}</td>
                            <td>{{ $invoice->emcef_certified_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f1f5f9; font-weight:bold;">
                        <td colspan="4">TOTAUX</td>
                        <td class="text-right">{{ number_format($invoices->sum('total_ht'), 0, ',', ' ') }} FCFA</td>
                        <td class="text-right text-primary">{{ number_format($invoices->sum('total_vat'), 0, ',', ' ') }} FCFA</td>
                        <td class="text-right">{{ number_format($invoices->sum('total'), 0, ',', ' ') }} FCFA</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y à H:i') }} • {{ $company->name }} • IFU: {{ $company->tax_number ?? 'N/A' }}<br>
        Ce document est un récapitulatif interne. Les données officielles sont celles enregistrées sur la plateforme e-MCeF de la DGI.
    </div>
</body>
</html>
