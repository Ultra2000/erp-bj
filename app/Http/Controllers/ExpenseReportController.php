<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseReportController extends Controller
{
    public function export(Request $request, $company = null)
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
        $categoryId = $request->query('category');

        $expenses = Expense::withoutGlobalScopes()
            ->where('company_id', $companyModel->id)
            ->whereBetween('spent_at', [$from->toDateString(), $until->toDateString()])
            ->when($categoryId, fn ($q) => $q->where('expense_category_id', $categoryId))
            ->with(['category', 'beneficiaryUser', 'createdBy'])
            ->orderBy('spent_at', 'desc')
            ->get();

        $byCategory = $expenses
            ->groupBy(fn (Expense $e) => $e->category?->name ?? 'Sans catégorie')
            ->map(fn ($rows) => (int) $rows->sum('amount'))
            ->sortDesc();

        $pdf = Pdf::loadView('reports.expenses', [
            'company' => $companyModel,
            'expenses' => $expenses,
            'byCategory' => $byCategory,
            'total' => (int) $expenses->sum('amount'),
            'count' => $expenses->count(),
            'categoryLabel' => $categoryId ? (ExpenseCategory::withoutGlobalScopes()->find($categoryId)?->name) : null,
            'from' => $from,
            'until' => $until,
            'currency' => $companyModel->currency ?? 'FCFA',
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream('depenses-' . now()->format('Y-m-d') . '.pdf');
    }
}
