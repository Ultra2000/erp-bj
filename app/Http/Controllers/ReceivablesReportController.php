<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CreancesClients;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReceivablesReportController extends Controller
{
    /**
     * Résout l'entreprise depuis la query et vérifie que l'utilisateur y a accès.
     */
    protected function resolveCompany(Request $request): Company
    {
        $companyId = (int) $request->query('company');
        $company = Company::find($companyId);
        abort_unless($company, 404, 'Entreprise introuvable');

        $user = auth()->user();
        if ($user && method_exists($user, 'companies')) {
            $ids = $user->companies()->pluck('companies.id')->toArray();
            abort_unless(in_array($company->id, $ids), 403);
        }

        return $company;
    }

    public function pdf(Request $request)
    {
        $company = $this->resolveCompany($request);
        $debtors = CreancesClients::debtorsForCompany($company->id);
        $total = collect($debtors)->sum('debt_total');
        $totalInvoices = collect($debtors)->sum('debt_count');

        $pdf = Pdf::loadView('reports.receivables', [
            'company' => $company,
            'debtors' => $debtors,
            'total' => $total,
            'totalInvoices' => $totalInvoices,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('creances-clients-' . now()->format('Y-m-d') . '.pdf');
    }

    public function excel(Request $request)
    {
        $company = $this->resolveCompany($request);
        $debtors = CreancesClients::debtorsForCompany($company->id);
        $total = collect($debtors)->sum('debt_total');

        $filename = 'creances-clients-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($debtors, $total) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour un affichage correct des accents dans Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Client', 'Telephone', 'IFU', 'Nb factures', 'Montant du (FCFA)', 'Depuis le', 'Anciennete (jours)'], ';');

            foreach ($debtors as $d) {
                fputcsv($out, [
                    $d['name'],
                    $d['phone'] ?? '',
                    $d['registration_number'] ?? '',
                    $d['debt_count'],
                    number_format($d['debt_total'], 0, ',', ' '),
                    $d['oldest_date'],
                    $d['days'],
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['TOTAL', '', '', '', number_format($total, 0, ',', ' '), '', ''], ';');

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
