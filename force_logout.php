<?php
// Détruire complètement la session
session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Supprimer le cookie de session manuellement
setcookie('PHPSESSID', '', time() - 3600, '/');

// Rediriger vers login
header('Location: index.php?page=login');
exit;
