<?php

namespace App\Filament\Superadmin\Pages;

use App\Services\GoogleDriveBackupService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

class Backups extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Système';

    protected static ?string $navigationLabel = 'Sauvegardes';

    protected static ?string $title = 'Sauvegardes de la base';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.superadmin.pages.backups';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backupNow')
                ->label('Sauvegarder maintenant')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Lancer une sauvegarde')
                ->modalDescription('Une sauvegarde de la base va être créée (et envoyée par email / Google Drive si configuré). Cela peut prendre quelques secondes.')
                ->action(function () {
                    try {
                        $code = Artisan::call('backup:database', [
                            '--email' => '',
                            '--gdrive' => true,
                        ]);

                        if ($code === 0) {
                            Notification::make()
                                ->title('Sauvegarde créée')
                                ->body('La sauvegarde a été générée avec succès.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Échec de la sauvegarde')
                                ->body('Consultez storage/logs/laravel.log pour le détail.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function deleteBackup(string $file): void
    {
        $file = basename($file);
        if (preg_match('/^backup-.+\.sql\.gz$/', $file) !== 1) {
            return;
        }

        $path = storage_path('app/backups/' . $file);
        if (is_file($path)) {
            @unlink($path);
            Notification::make()->title('Sauvegarde supprimée')->success()->send();
        }
    }

    /**
     * Liste des sauvegardes disponibles, de la plus récente à la plus ancienne.
     */
    public function getBackups(): array
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz') ?: [];

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map(function ($path) {
            $name = basename($path);
            return [
                'name' => $name,
                'size' => $this->humanSize(filesize($path)),
                'date' => Carbon::createFromTimestamp(filemtime($path))->format('d/m/Y H:i'),
                'url' => route('superadmin.backups.download', ['file' => $name]),
            ];
        }, $files);
    }

    public function getGoogleDriveStatus(): array
    {
        return [
            'ok' => GoogleDriveBackupService::isConfigured(),
            'reason' => GoogleDriveBackupService::isConfigured() ? 'Actif' : GoogleDriveBackupService::unavailableReason(),
        ];
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' Mo';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' Ko';
        }
        return $bytes . ' o';
    }
}
