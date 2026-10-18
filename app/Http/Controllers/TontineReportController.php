<?php

namespace App\Http\Controllers;

use App\Filament\Pages\TontineOverview;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SavingsTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

    /**
     * Relevé de compte d'épargne d'un client sur une période (solde d'ouverture,
     * mouvements avec solde cumulé, totaux, solde de clôture).
     */
    public function statement(Request $request, int $customer)
    {
        $cust = Customer::withoutGlobalScopes()->findOrFail($customer);

        $user = auth()->user();
        if (! $user->is_super_admin && ! $user->companies()->where('companies.id', $cust->company_id)->exists()) {
            abort(403);
        }

        $company = Company::find($cust->company_id);

        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->startOfYear();
        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->endOfDay();

        // Solde d'ouverture = solde après le dernier mouvement avant la période.
        $before = SavingsTransaction::withoutGlobalScopes()
            ->where('customer_id', $cust->id)
            ->where('created_at', '<', $start)
            ->latest('id')
            ->first();
        $opening = $before ? (int) $before->balance_after : 0;

        $movements = SavingsTransaction::withoutGlobalScopes()
            ->where('customer_id', $cust->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('createdBy:id,name')
            ->orderBy('id')
            ->get();

        $closing = $movements->count() ? (int) $movements->last()->balance_after : $opening;
        $sumType = fn (string $t) => (int) abs((int) $movements->where('type', $t)->sum('amount'));

        $pdf = Pdf::loadView('reports.savings-statement', [
            'company' => $company,
            'customer' => $cust,
            'movements' => $movements,
            'opening' => $opening,
            'closing' => $closing,
            'deposits' => $sumType('deposit'),
            'withdrawals' => $sumType('withdrawal'),
            'purchases' => $sumType('purchase'),
            'refunds' => $sumType('refund'),
            'start' => $start,
            'end' => $end,
            'currency' => $company->currency ?? 'FCFA',
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream('releve-epargne-' . $cust->id . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export PDF des mouvements d'épargne d'une entreprise sur une période
     * (tous clients), filtrable par type.
     */
    public function movements(Request $request, $company = null)
    {
        $companyId = $company ?? $request->query('company') ?? Filament::getTenant()?->id;

        if (! $companyId) {
            abort(400, 'Company ID required');
        }

        $companyModel = Company::findOrFail($companyId);

        $user = auth()->user();
        if (! $user->is_super_admin && ! $user->companies()->where('companies.id', $companyModel->id)->exists()) {
            abort(403);
        }

        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth();
        $until = $request->query('until')
            ? Carbon::parse($request->query('until'))->endOfDay()
            : now()->endOfDay();
        $type = $request->query('type');

        $movements = SavingsTransaction::withoutGlobalScopes()
            ->where('company_id', $companyModel->id)
            ->whereBetween('created_at', [$from, $until])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->with(['customer:id,name', 'createdBy:id,name'])
            ->orderByDesc('id')
            ->get();

        $sumType = fn (string $t) => (int) abs((int) $movements->where('type', $t)->sum('amount'));

        $pdf = Pdf::loadView('reports.savings-movements', [
            'company' => $companyModel,
            'movements' => $movements,
            'from' => $from,
            'until' => $until,
            'typeLabel' => $type ? (SavingsTransaction::TYPES[$type] ?? $type) : null,
            'deposits' => $sumType('deposit'),
            'withdrawals' => $sumType('withdrawal'),
            'purchases' => $sumType('purchase'),
            'count' => $movements->count(),
            'currency' => $companyModel->currency ?? 'FCFA',
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream('mouvements-epargne-' . now()->format('Y-m-d') . '.pdf');
    }
}
