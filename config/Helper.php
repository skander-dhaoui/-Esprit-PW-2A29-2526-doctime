<?php
declare(strict_types=1);

/**
 * Classe Helper — Utilitaires généraux en approche OOP
 * Remplace toutes les fonctions globales par des méthodes statiques
 */
class Helper {

    /**
     * Charger les variables d'environnement depuis le fichier .env
     */
    public static function loadEnv(string $path): void {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Temps écoulé depuis une date passée, en français (ex. « il y a 3 jours »).
     */
    public static function tempsEcouleFr(?string $dateTime): string {
        if ($dateTime === null || trim($dateTime) === '') {
            return '';
        }
        $ts = strtotime($dateTime);
        if ($ts === false) {
            return '';
        }
        $sec = time() - $ts;
        if ($sec < 0) {
            return '';
        }
        if ($sec < 60) {
            return 'à l\'instant';
        }
        $min = (int) floor($sec / 60);
        if ($min < 60) {
            return $min === 1 ? 'il y a 1 minute' : "il y a {$min} minutes";
        }
        $h = (int) floor($min / 60);
        if ($h < 24) {
            return $h === 1 ? 'il y a 1 heure' : "il y a {$h} heures";
        }
        $d = (int) floor($h / 24);
        if ($d < 7) {
            return $d === 1 ? 'il y a 1 jour' : "il y a {$d} jours";
        }
        $w = (int) floor($d / 7);
        if ($w < 5) {
            return $w === 1 ? 'il y a 1 semaine' : "il y a {$w} semaines";
        }
        $m = (int) floor($d / 30);
        if ($m < 12) {
            return $m <= 1 ? 'il y a 1 mois' : "il y a {$m} mois";
        }
        $y = (int) floor($d / 365);
        return $y <= 1 ? 'il y a plus d\'un an' : "il y a {$y} ans";
    }

    /**
     * Nombre de jours calendaires entre deux dates (inclus).
     */
    public static function dureeEvenementJours(string $dateDebut, string $dateFin): int {
        $d1 = DateTime::createFromFormat('Y-m-d', $dateDebut);
        $d2 = DateTime::createFromFormat('Y-m-d', $dateFin);
        if (!$d1 || !$d2) {
            return 0;
        }
        $d1->setTime(0, 0, 0);
        $d2->setTime(0, 0, 0);
        return (int) $d1->diff($d2)->days + 1;
    }

    /**
     * Formater une date en français (ex. « 26 avril 2026 »)
     */
    public static function formateDateFr(?string $dateTime): string {
        if (!$dateTime || trim($dateTime) === '') {
            return '';
        }
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime) 
                   ?? DateTime::createFromFormat('Y-m-d', $dateTime);
        if (!$datetime) {
            return '';
        }
        $mois = [
            'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
        ];
        $m = (int) $datetime->format('n') - 1;
        return $datetime->format('j') . ' ' . $mois[$m] . ' ' . $datetime->format('Y');
    }

    /**
     * Valider une adresse email
     */
    public static function validateEmail(string $email): bool {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valider un numéro de téléphone (format français)
     */
    public static function validatePhoneFr(string $phone): bool {
        $cleaned = preg_replace('/[^0-9]/', '', trim($phone));
        return strlen($cleaned) === 10 && in_array($cleaned[0], ['0']);
    }

    /**
     * Valider une URL
     */
    public static function validateUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Nettoyer une chaîne (trim + htmlspecialchars)
     */
    public static function sanitizeString(string $str): string {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Générer un slug à partir d'une chaîne
     */
    public static function generateSlug(string $str): string {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);
        $str = trim($str, '-');
        return $str;
    }

    /**
     * Obtenir la variable d'environnement avec valeur par défaut
     */
    public static function getEnv(string $key, string $default = ''): string {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Formater une devise (montant en euros)
     */
    public static function formatMontant(float $montant): string {
        return number_format($montant, 2, ',', ' ') . ' €';
    }

    /**
     * Vérifier si une chaîne est vide
     */
    public static function isEmpty(?string $str): bool {
        return $str === null || trim($str) === '';
    }

    /**
     * Obtenir les paramètres GET de manière sécurisée
     */
    public static function getParam(string $key, string $default = ''): string {
        return isset($_GET[$key]) ? self::sanitizeString($_GET[$key]) : $default;
    }

    /**
     * Obtenir les paramètres POST de manière sécurisée
     */
    public static function postParam(string $key, string $default = ''): string {
        return isset($_POST[$key]) ? self::sanitizeString($_POST[$key]) : $default;
    }

    /**
     * Rediriger vers une URL
     */
    public static function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Retourner du JSON
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Charger le fichier .env au démarrage
Helper::loadEnv(__DIR__ . '/../.env');
