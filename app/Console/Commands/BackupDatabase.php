<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--keep=14 : Nombre de jours de rétention des sauvegardes}
                            {--path= : Dossier de destination (défaut: storage/app/backups)}';

    protected $description = 'Sauvegarde la base de données MySQL (mysqldump + gzip) avec rétention.';

    public function handle(): int
    {
        $connectionName = config('database.default');
        $db = config("database.connections.{$connectionName}");

        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error("La sauvegarde ne supporte que MySQL (connexion actuelle: {$connectionName}).");
            return self::FAILURE;
        }

        $dir = $this->option('path') ?: storage_path('app/backups');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("Impossible de créer le dossier de sauvegarde: {$dir}");
            return self::FAILURE;
        }

        $host = $db['host'] ?? '127.0.0.1';
        $port = (string) ($db['port'] ?? 3306);
        $user = $db['username'] ?? 'root';
        $password = (string) ($db['password'] ?? '');
        $database = $db['database'] ?? '';

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . "backup-{$database}-{$timestamp}.sql.gz";

        // Options mysqldump :
        //  --single-transaction : dump cohérent (InnoDB) sans verrouiller les tables
        //  --quick              : streaming ligne par ligne (peu de mémoire)
        //  --no-tablespaces     : évite l'erreur PROCESS privilege sur hébergement mutualisé (cPanel)
        //  --routines/--events  : inclut procédures stockées et événements
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --no-tablespaces --routines --events %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($database),
            escapeshellarg($file)
        );

        $this->info("Sauvegarde de « {$database} » en cours...");

        // Le mot de passe est transmis via MYSQL_PWD pour ne pas apparaître dans la liste des processus.
        $process = Process::fromShellCommandline($command, base_path(), ['MYSQL_PWD' => $password], null, 3600);

        try {
            $process->run();
        } catch (\Throwable $e) {
            $this->error('Échec du lancement de mysqldump: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $process->isSuccessful() || ! file_exists($file) || filesize($file) === 0) {
            @unlink($file);
            $this->error('La sauvegarde a échoué.');
            $this->line(trim($process->getErrorOutput()) ?: 'Aucune sortie d\'erreur (mysqldump introuvable ?).');
            return self::FAILURE;
        }

        $sizeMo = round(filesize($file) / 1048576, 2);
        $this->info("Sauvegarde créée: {$file} ({$sizeMo} Mo)");

        $this->pruneOldBackups($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    /**
     * Supprime les sauvegardes plus anciennes que $keepDays jours.
     */
    protected function pruneOldBackups(string $dir, int $keepDays): void
    {
        if ($keepDays <= 0) {
            return;
        }

        $cutoff = Carbon::now()->subDays($keepDays)->getTimestamp();
        $deleted = 0;

        foreach (glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'backup-*.sql.gz') ?: [] as $backup) {
            if (filemtime($backup) < $cutoff) {
                if (@unlink($backup)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            $this->line("Rétention: {$deleted} ancienne(s) sauvegarde(s) supprimée(s) (> {$keepDays} jours).");
        }
    }
}
