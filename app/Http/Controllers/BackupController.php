<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BackupController extends Controller
{
    /**
     * Télécharge une sauvegarde (réservé aux super admins).
     */
    public function download(Request $request, string $file)
    {
        $user = auth()->user();
        abort_unless($user && $user->is_super_admin, 403);

        // Anti path-traversal : uniquement un nom de fichier de sauvegarde valide.
        $file = basename($file);
        abort_unless(preg_match('/^backup-.+\.sql\.gz$/', $file) === 1, 404);

        $path = storage_path('app/backups/' . $file);
        abort_unless(is_file($path), 404);

        return response()->download($path, $file, ['Content-Type' => 'application/gzip']);
    }
}
