<?php

namespace App\Http\Controllers;

use App\Filament\Pages\TontineOverview;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class TontineReportController extends Controller
{
    public function export(Request $request, $company = null)
    {
        $companyId = $company ?? $request->query('company') ?? Filament::getTenant()?->id;

        if (! $companyId) {
            abort(400, 'Company ID required');
        }

        $companyModel = Company::findOrFail($companyId);
        if ($companyModel) {
            Filament::setTenant($companyModel);
        }

        $data = TontineOverview::buildData((int) $companyId);

        $pdf = Pdf::loadView('reports.tontine', array_merge($data, [
            'company' => $companyModel,
            'currency' => $companyModel->currency ?? 'FCFA',
            'generatedAt' => now(),
        ]))->setPaper('a4');

        return $pdf->download('tontine-' . now()->format('Y-m-d') . '.pdf');
    }
}
