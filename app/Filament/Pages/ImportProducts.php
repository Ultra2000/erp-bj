<?php

namespace App\Filament\Pages;

use App\Exports\ProductTemplateExport;
use App\Imports\ProductImport;
use Filament\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportProducts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Importer Produits';
    protected static ?string $title = 'Importer des Produits';
    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.import-products';

    public ?array $data = [];
    public ?string $uploadedFile = null;
    public bool $importCompleted = false;
    public int $importedCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    protected static function isCashierUser(): bool
    {
        $user = auth()->user();
        return $user && $user->hasWarehouseRestriction();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (static::isCashierUser()) {
            return false;
        }
        return Filament::getTenant()?->isModuleEnabled('stock') ?? true;
    }

    public static function canAccess(): bool
    {
        if (static::isCashierUser()) {
            return false;
        }
        return Filament::getTenant()?->isModuleEnabled('stock') ?? true;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('Fichier Excel')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->maxSize(10240) // 10 MB
                    ->required()
                    ->disk('local')
                    ->directory('imports')
                    ->helperText('Formats acceptés: .xlsx, .xls, .csv - Max 10 Mo')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->resetImportState()),
            ])
            ->statePath('data');
    }

    protected function resetImportState(): void
    {
        $this->importCompleted = false;
        $this->importedCount = 0;
        $this->updatedCount = 0;
        $this->skippedCount = 0;
        $this->errors = [];
    }

    public function import(): void
    {
        $data = $this->form->getState();

        if (empty($data['file'])) {
            Notification::make()
                ->title('Erreur')
                ->body('Veuillez sélectionner un fichier.')
                ->danger()
                ->send();
            return;
        }

        try {
            $filePath = Storage::disk('local')->path($data['file']);
            $companyId = Filament::getTenant()->id;

            $import = new ProductImport($companyId);
            Excel::import($import, $filePath);

            $this->importedCount = $import->getImportedCount();
            $this->updatedCount = $import->getUpdatedCount();
            $this->skippedCount = $import->getSkippedCount();
            $this->errors = $import->getImportErrors();

            $this->importCompleted = true;

            // Supprimer le fichier temporaire
            Storage::disk('local')->delete($data['file']);

            $totalProcessed = $this->importedCount + $this->updatedCount;
            
            if ($totalProcessed > 0) {
                Notification::make()
                    ->title('Import terminé')
                    ->body("{$this->importedCount} produit(s) créé(s), {$this->updatedCount} mis à jour.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Aucun produit importé')
                    ->body('Vérifiez le format de votre fichier.')
                    ->warning()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de l\'import')
                ->body($e->getMessage())
                ->danger()
                ->send();

            \Log::error('Import produits erreur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new ProductTemplateExport(), 'template_import_produits.xlsx');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Télécharger le modèle')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('downloadTemplate'),
        ];
    }
}
