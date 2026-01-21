<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Facades\Filament;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class EmcefReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Rapport e-MCeF';
    protected static ?string $title = 'Rapport e-MCeF (DGI Bénin)';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 95;

    protected static string $view = 'filament.pages.emcef-report';

    public ?int $selectedYear = null;
    public ?int $selectedMonth = null;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->year;
        $this->selectedMonth = (int) now()->month;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->emcef_enabled ?? false;
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    /**
     * Récupère les statistiques du mois sélectionné
     */
    public function getMonthlyStats(): array
    {
        $companyId = $this->getCompanyId();
        
        $query = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->where('emcef_status', 'certified');

        // Totaux généraux
        $totalInvoices = (clone $query)->where('type', '!=', 'credit_note')->count();
        $totalCreditNotes = (clone $query)->where('type', 'credit_note')->count();
        
        $totalHT = (clone $query)->where('type', '!=', 'credit_note')->sum('total_ht');
        $totalVAT = (clone $query)->where('type', '!=', 'credit_note')->sum('total_vat');
        $totalTTC = (clone $query)->where('type', '!=', 'credit_note')->sum('total');
        
        $creditNotesHT = (clone $query)->where('type', 'credit_note')->sum('total_ht');
        $creditNotesVAT = (clone $query)->where('type', 'credit_note')->sum('total_vat');
        $creditNotesTTC = (clone $query)->where('type', 'credit_note')->sum('total');

        // Net (Factures - Avoirs)
        $netHT = $totalHT - $creditNotesHT;
        $netVAT = $totalVAT - $creditNotesVAT;
        $netTTC = $totalTTC - $creditNotesTTC;

        // Ventilation par groupe de taxe (A, B, etc.)
        $vatBreakdown = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->where('emcef_status', 'certified')
            ->where('type', '!=', 'credit_note')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->select(
                'sale_items.vat_category',
                'sale_items.vat_rate',
                DB::raw('SUM(sale_items.total_price_ht) as base_ht'),
                DB::raw('SUM(sale_items.vat_amount) as vat_amount'),
                DB::raw('COUNT(DISTINCT sales.id) as invoice_count')
            )
            ->groupBy('sale_items.vat_category', 'sale_items.vat_rate')
            ->get()
            ->toArray();

        // Ventilation par mode de paiement
        $paymentBreakdown = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->where('emcef_status', 'certified')
            ->where('type', '!=', 'credit_note')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('payment_method')
            ->get()
            ->toArray();

        // Compteurs e-MCeF (premier et dernier NIM du mois)
        $firstInvoice = (clone $query)->where('type', '!=', 'credit_note')
            ->orderBy('emcef_certified_at', 'asc')
            ->first();
        $lastInvoice = (clone $query)->where('type', '!=', 'credit_note')
            ->orderBy('emcef_certified_at', 'desc')
            ->first();

        return [
            'period' => $this->getMonthName($this->selectedMonth) . ' ' . $this->selectedYear,
            'total_invoices' => $totalInvoices,
            'total_credit_notes' => $totalCreditNotes,
            'total_ht' => $totalHT,
            'total_vat' => $totalVAT,
            'total_ttc' => $totalTTC,
            'credit_notes_ht' => $creditNotesHT,
            'credit_notes_vat' => $creditNotesVAT,
            'credit_notes_ttc' => $creditNotesTTC,
            'net_ht' => $netHT,
            'net_vat' => $netVAT,
            'net_ttc' => $netTTC,
            'vat_breakdown' => $vatBreakdown,
            'payment_breakdown' => $paymentBreakdown,
            'first_nim' => $firstInvoice?->emcef_nim,
            'last_nim' => $lastInvoice?->emcef_nim,
            'first_code_mecef' => $firstInvoice?->emcef_code_mecef,
            'last_code_mecef' => $lastInvoice?->emcef_code_mecef,
            'counters' => $lastInvoice?->emcef_counters,
        ];
    }

    /**
     * Table des factures certifiées du mois
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::withoutGlobalScopes()
                    ->where('company_id', $this->getCompanyId())
                    ->whereYear('created_at', $this->selectedYear)
                    ->whereMonth('created_at', $this->selectedMonth)
                    ->where('emcef_status', 'certified')
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('N° Facture')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'credit_note' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'credit_note' ? 'Avoir' : 'Facture'),
                TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('emcef_nim')
                    ->label('NIM')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('emcef_code_mecef')
                    ->label('Code MECeF')
                    ->copyable()
                    ->limit(20),
                TextColumn::make('total_ht')
                    ->label('HT')
                    ->money('XOF')
                    ->alignEnd(),
                TextColumn::make('total_vat')
                    ->label('TVA')
                    ->money('XOF')
                    ->alignEnd(),
                TextColumn::make('total')
                    ->label('TTC')
                    ->money('XOF')
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('emcef_certified_at')
                    ->label('Certifiée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('emcef_certified_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped();
    }

    /**
     * Exporter le rapport en PDF
     */
    public function exportPdf()
    {
        $stats = $this->getMonthlyStats();
        $company = Filament::getTenant();
        
        $invoices = Sale::withoutGlobalScopes()
            ->where('company_id', $this->getCompanyId())
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->where('emcef_status', 'certified')
            ->with('customer')
            ->orderBy('emcef_certified_at')
            ->get();

        $pdf = Pdf::loadView('reports.emcef-monthly', [
            'stats' => $stats,
            'company' => $company,
            'invoices' => $invoices,
            'year' => $this->selectedYear,
            'month' => $this->selectedMonth,
            'monthName' => $this->getMonthName($this->selectedMonth),
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "rapport-emcef-{$this->selectedYear}-{$this->selectedMonth}.pdf"
        );
    }

    protected function getMonthName(int $month): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        return $months[$month] ?? '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn () => $this->exportPdf()),
        ];
    }
}
