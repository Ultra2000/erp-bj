<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Téléverse les sauvegardes vers Google Drive via un compte de service.
 *
 * Nécessite le package google/apiclient et un compte de service :
 *   composer require google/apiclient
 * puis dans le .env :
 *   GOOGLE_DRIVE_BACKUP_ENABLED=true
 *   GOOGLE_DRIVE_CREDENTIALS=/chemin/vers/service-account.json
 *   GOOGLE_DRIVE_FOLDER_ID=xxxxxxxx   (dossier Drive partagé avec l'email du compte de service)
 */
class GoogleDriveBackupService
{
    /**
     * L'intégration est-elle prête (package installé + config valide) ?
     */
    public static function isConfigured(): bool
    {
        return class_exists(\Google\Client::class)
            && (bool) config('services.google_drive.enabled')
            && config('services.google_drive.credentials')
            && is_file((string) config('services.google_drive.credentials'));
    }

    /**
     * Message expliquant pourquoi l'upload n'est pas disponible (pour les logs/UI).
     */
    public static function unavailableReason(): string
    {
        if (! class_exists(\Google\Client::class)) {
            return "Package google/apiclient non installé (composer require google/apiclient).";
        }
        if (! config('services.google_drive.enabled')) {
            return "Google Drive désactivé (GOOGLE_DRIVE_BACKUP_ENABLED=false).";
        }
        if (! config('services.google_drive.credentials') || ! is_file((string) config('services.google_drive.credentials'))) {
            return "Fichier de credentials introuvable (GOOGLE_DRIVE_CREDENTIALS).";
        }
        return "Configuration Google Drive incomplète.";
    }

    /**
     * Téléverse un fichier et renvoie l'ID Drive, ou null en cas d'échec.
     */
    public function upload(string $filePath): ?string
    {
        if (! static::isConfigured() || ! is_file($filePath)) {
            return null;
        }

        try {
            $client = new \Google\Client();
            $client->setAuthConfig((string) config('services.google_drive.credentials'));
            $client->addScope(\Google\Service\Drive::DRIVE_FILE);

            $service = new \Google\Service\Drive($client);

            $metadata = new \Google\Service\Drive\DriveFile([
                'name' => basename($filePath),
                'parents' => array_values(array_filter([config('services.google_drive.folder_id')])),
            ]);

            $created = $service->files->create($metadata, [
                'data' => file_get_contents($filePath),
                'mimeType' => 'application/gzip',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);

            return $created->id ?? null;
        } catch (\Throwable $e) {
            Log::error('Upload Google Drive échoué: ' . $e->getMessage());
            return null;
        }
    }
}
