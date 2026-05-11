<?php
/**
 * Diagnostic connexion (localhost uniquement).
 * Usage : http://localhost/valorys_Copie/debug_login_help.php?email=votre@email.com
 */
declare(strict_types=1);

$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
if (
    $host === ''
    || (strpos($host, 'localhost') === false
        && strpos($host, '127.0.0.1') === false
        && substr($host, -6) !== '.local')
) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

$email = trim((string) ($_GET['email'] ?? ''));
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Aide connexion</title>';
echo '<style>body{font-family:system-ui,Segoe UI,sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem;} code{background:#f4f4f4;padding:2px 6px;}</style></head><body>';
echo '<h1>Diagnostic connexion (dev)</h1>';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo '<p>Indiquez un email : <code>?email=votre@adresse.com</code></p>';
    echo '</body></html>';
    exit;
}

$userModel = new User();
$row = $userModel->findByEmail($email);

if ($row === false) {
    echo '<p><strong>Aucun utilisateur</strong> avec cet email dans <code>doctime_db</code>.</p>';
    echo '<p>Solutions : créer un compte (inscription), ou importer <code>database/seed_demo_6x.sql</code> (ex. <code>patient1@demo.doctime</code> / mot de passe <code>doctime123</code>).</p>';
    echo '</body></html>';
    exit;
}

$hash = (string) ($row['password'] ?? '');
$type = 'inconnu';
if ($hash !== '' && ($hash[0] ?? '') === '$') {
    if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0) {
        $type = 'bcrypt (password_hash)';
    } elseif (strpos($hash, '$argon2') === 0) {
        $type = 'Argon2';
    } else {
        $type = 'hash bcrypt/argon (autre préfixe)';
    }
} elseif (strlen($hash) === 32 && ctype_xdigit($hash)) {
    $type = 'MD5 (ancien — migration auto au prochain login OK)';
} elseif (strlen($hash) === 40 && ctype_xdigit($hash)) {
    $type = 'SHA1 (ancien — migration auto au prochain login OK)';
}

echo '<p><strong>Compte trouvé.</strong></p>';
echo '<ul>';
echo '<li>Email en base : <code>' . htmlspecialchars((string) $row['email']) . '</code></li>';
echo '<li>Rôle : <code>' . htmlspecialchars((string) ($row['role'] ?? '')) . '</code></li>';
echo '<li>Statut : <code>' . htmlspecialchars((string) ($row['statut'] ?? '')) . '</code></li>';
echo '<li>Type mot de passe : <code>' . htmlspecialchars($type) . '</code></li>';
$sp = trim((string) ($row['social_provider'] ?? ''));
if ($sp !== '') {
    echo '<li>Connexion sociale : <code>' . htmlspecialchars($sp) . '</code> — le mot de passe « classique » a été généré aléatoirement : utilisez le bouton social ou <a href="index.php?page=forgot_password">mot de passe oublié</a>.</li>';
}
echo '</ul>';
echo '<p>Réinitialiser le mot de passe : <a href="fix_password.php"><code>fix_password.php</code></a></p>';
echo '</body></html>';
