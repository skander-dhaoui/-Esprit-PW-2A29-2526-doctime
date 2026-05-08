<?php
/**
 * Environment Loader
 * Charge les variables d'environnement depuis le fichier .env
 */

function loadEnv(string $filePath = __DIR__ . '/../.env'): void
{
    if (!file_exists($filePath)) {
        error_log("Warning: .env file not found at: {$filePath}");
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Parser les variables
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Ne pas remplacer si déjà défini dans l'environnement
            if (!getenv($key)) {
                putenv("{$key}={$value}");
            }
        }
    }
}

// Charger automatiquement les variables d'environnement
loadEnv();
