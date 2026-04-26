<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

// ── Autoload des classes ──────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [
        __DIR__ . '/config/',
        __DIR__ . '/model/',
        __DIR__ . '/controller/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Routage ───────────────────────────────────────────────────────────────────
$controller = $_GET['controller'] ?? 'home';
$action     = $_GET['action']     ?? 'index';

// Sécuriser : n'autoriser que des noms alphanumériques
$controller = preg_replace('/[^a-zA-Z0-9]/', '', $controller);
$action     = preg_replace('/[^a-zA-Z0-9]/', '', $action);

$map = [
    'home'          => ['class' => null,                      'front' => true],
    'dashboard'     => ['class' => 'DashboardController',     'front' => false],
    'sponsor'       => ['class' => 'SponsorController',       'front' => false],
    'evenement'     => ['class' => 'EvenementController',     'front' => false],
    'participation' => ['class' => 'ParticipationController', 'front' => false],
    'sponsors'      => ['class' => 'SponsorController',       'front' => true],
    'evenements'    => ['class' => 'EvenementController',     'front' => true],
    'inscrire'      => ['class' => 'ParticipationController', 'front' => true],
    'mesinscriptions' => ['class' => 'ParticipationController', 'front' => true],
    // ── Métiers avancés événements (ajout sans modification de l'existant) ──
    'evenementavance' => ['class' => 'EvenementAvanceController', 'front' => false],
    // ── Carte interactive + Assistant IA Métiers (nouveaux) ──────────────────
    'map'             => ['class' => 'MapController',             'front' => false],
    // ── Proxy IA (appels API côté serveur) ───────────────────────────────────
    'aiproxy'         => ['class' => 'AiProxyController',          'front' => false],
];

if (!array_key_exists($controller, $map)) {
    http_response_code(404);
    echo "<h1>404 – Page introuvable</h1>";
    exit;
}

// Page d'accueil backoffice → dashboard
if ($controller === 'home' || $controller === '') {
    require __DIR__ . '/view/frontoffice/home.php';
    exit;
}

$entry      = $map[$controller];
$className  = $entry['class'];
$ctrl       = new $className();

// Actions frontoffice spéciales
$frontActions = [
    'evenements'    => ['list', 'detail'],
    'sponsors'      => ['list'],
    'inscrire'      => ['inscrire', 'inscrireStore'],
    'evenement'     => ['list', 'detail'],
    'sponsor'       => ['list'],
    'participation' => ['inscrire', 'inscrireStore'],
];

if (method_exists($ctrl, $action)) {
    $ctrl->$action();
} else {
    http_response_code(404);
    echo "<h1>404 – Action introuvable</h1>";
}
