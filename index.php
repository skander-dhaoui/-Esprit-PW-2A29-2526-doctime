<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (is_file(__DIR__ . '/error_handler.php')) {
    require_once __DIR__ . '/error_handler.php';
}
if (is_file(__DIR__ . '/config/env.php')) {
    require_once __DIR__ . '/config/env.php';
}
if (is_file(__DIR__ . '/config/recaptcha.php')) {
    require_once __DIR__ . '/config/recaptcha.php';
}
if (is_file(__DIR__ . '/config/app_logger.php')) {
    require_once __DIR__ . '/config/app_logger.php';
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

@ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

ob_start(static function ($buffer) {
    if (stripos($buffer, '</head>') === false) {
        return $buffer;
    }
    $inject = '';
    $hasThemeScript = strpos($buffer, 'assets/js/theme-mode.js') !== false;
    $hasThemeStyle = strpos($buffer, 'assets/css/theme-mode.css') !== false;
    if (!$hasThemeScript) {
        $inject .= "    <script src=\"assets/js/theme-mode.js\"></script>\n";
    }
    if (!$hasThemeStyle) {
        $inject .= "    <link rel=\"stylesheet\" href=\"assets/css/theme-mode.css\">\n";
    }
    /* Toujours après theme-mode.css : menu admin sans dégradé résiduel (même si thème déjà présent). */
    if (strpos($buffer, 'backoffice-sidebar-overrides.css') === false) {
        $inject .= "    <link rel=\"stylesheet\" href=\"assets/css/backoffice-sidebar-overrides.css\">\n";
    }
    if ($inject === '') {
        return $buffer;
    }
    return preg_replace('/<\/head>/i', $inject . '</head>', $buffer, 1);
});

define('DEBUG_MODE', false);

// =============================================
// INCLUDES — MODÈLES
// =============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/models/User.php';
if (is_file(__DIR__ . '/models/FaceRecognition.php')) {
    require_once __DIR__ . '/models/FaceRecognition.php';
}
require_once __DIR__ . '/models/Patient.php';
require_once __DIR__ . '/models/Medecin.php';
require_once __DIR__ . '/models/Admin.php';
require_once __DIR__ . '/models/Article.php';
require_once __DIR__ . '/models/Reply.php';

foreach (['ArticleRepository', 'UserRepository', 'EventRepository', 'ParticipationRepository'] as $repositoryClass) {
    $repositoryPath = __DIR__ . '/repositories/' . $repositoryClass . '.php';
    if (is_file($repositoryPath)) {
        require_once $repositoryPath;
    }
}

// Modèles optionnels
$optionalModels = [
    'RendezVous', 'Disponibilite', 'Event', 'Ordonnance',
    'Participation', 'Sponsor',
    'Categorie', 'Produit', 'Commande', 'Client', 'CommandeLigne',
];
foreach ($optionalModels as $model) {
    $path = __DIR__ . "/models/{$model}.php";
    if (file_exists($path)) require_once $path;
}

// =============================================
// INCLUDES — CONTRÔLEURS
// =============================================
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/FrontController.php';
require_once __DIR__ . '/controllers/PatientController.php';
require_once __DIR__ . '/controllers/MedecinController.php';
require_once __DIR__ . '/controllers/ArticleController.php';
require_once __DIR__ . '/controllers/ReplyController.php';
if (is_file(__DIR__ . '/controllers/GamificationController.php')) {
    require_once __DIR__ . '/controllers/GamificationController.php';
}

// Contrôleurs optionnels
$optionalControllers = [
    'RendezVousController', 'EventController', 'MapController',
    'ProduitController', 'OrdonnanceController', 'DisponibiliteController',
    'ParticipationController', 'SponsorController',
    'PharmacieController',
];
foreach ($optionalControllers as $ctrl) {
    $path = __DIR__ . "/controllers/{$ctrl}.php";
    if (file_exists($path)) require_once $path;
}

// =============================================
// RÉCUPÉRATION DES PARAMÈTRES
// =============================================
if (!isset($_GET['page'])) {
    $page = 'accueil';
} else {
    $page = preg_replace('/[^a-z0-9_]/', '', trim($_GET['page']));
}
$action = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/', '', trim($_GET['action'])) : 'index';
$id     = isset($_GET['id'])     ? (int)$_GET['id'] : null;
$slug   = isset($_GET['slug'])   ? preg_replace('/[^a-z0-9-]/', '', trim($_GET['slug'])) : null;

if (DEBUG_MODE) {
    echo "<!-- DEBUG PARAMS: page='$page' action='$action' id='$id' slug='$slug' -->\n";
}

// =============================================
// INITIALISATION DES CONTRÔLEURS
// =============================================
$auth        = new AuthController();
$userCtrl    = new UserController();
$adminCtrl   = new AdminController();
$front       = new FrontController();
$patientCtrl = new PatientController();
$medecinCtrl = new MedecinController();
$articleCtrl = new ArticleController();
$replyCtrl   = new ReplyController();
$gamificationCtrl = class_exists('GamificationController') ? new GamificationController() : null;

$rendezVousCtrl    = class_exists('RendezVousController')    ? new RendezVousController()    : null;
$ordonnanceCtrl    = class_exists('OrdonnanceController')    ? new OrdonnanceController()    : null;
$disponibiliteCtrl = class_exists('DisponibiliteController') ? new DisponibiliteController() : null;
$pharmacieCtrl     = class_exists('PharmacieController')     ? new PharmacieController()     : null;

// =============================================
// PAGES PUBLIQUES / PROTÉGÉES
// =============================================
$publicPages = [
    'accueil', 'login', 'register', 'forgot_password', 'reset_password',
    'verify_2fa', 'resend_2fa', 'social_login', 'social_callback',
    'medecins', 'detail_medecin', 'blog_public', 'detail_article_public',
    'evenements', 'detail_evenement', 'event_register', 'sponsors', 'contact', 'about',
    'get_face_photo',
];

$guestOnlyPages = ['register', 'forgot_password', 'reset_password', 'login', 'verify_2fa', 'resend_2fa'];

$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $_SESSION['user_role'] ?? '';

// Marquer une notification admin comme lue puis redirection (lien depuis la cloche)
if (!empty($_GET['mark_notif_read']) && $userRole === 'admin') {
    $mid = (int) $_GET['mark_notif_read'];
    if ($mid > 0 && is_file(__DIR__ . '/models/AdminNotification.php')) {
        try {
            require_once __DIR__ . '/models/AdminNotification.php';
            (new AdminNotification())->markRead($mid);
        } catch (Throwable $e) {
            /* table absente ou erreur */
        }
    }
    $dest = $_GET['notif_redirect'] ?? 'index.php?page=dashboard';
    if (!is_string($dest) || !preg_match('#^index\.php(\?.*)?$#', $dest)) {
        $dest = 'index.php?page=dashboard';
    }
    header('Location: ' . $dest);
    exit;
}

// =============================================
// ROUTES SPÉCIALES (sans vérification de connexion)
// =============================================

// Reconnaissance faciale - routes publiques
if ($page === 'face_login') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $auth->faceLogin();
    } else {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    }
    exit;
}

if ($page === 'get_face_photo') {
    header('Content-Type: application/json');
    $email = trim($_GET['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT face_photo, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $userFace = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$userFace || empty($userFace['face_photo'])) {
        echo json_encode(['success' => false, 'message' => 'Aucune photo enregistrée pour cet utilisateur']);
        exit;
    }
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') . '/';
    $photoUrl  = $protocol . '://' . $host . $scriptDir . $userFace['face_photo'];
    echo json_encode(['success' => true, 'photo_url' => $photoUrl, 'role' => $userFace['role']]);
    exit;
}

if ($page === 'register_face') {
    // Si c'est une requête POST (envoi de l'image)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter d\'abord']);
            exit;
        }
        $auth->registerFace();
    } else {
        // Si c'est une requête GET, afficher la page d'enregistrement
        $front->renderRegisterFace();
    }
    exit;
}

/**
 * Traduction via l'API MyMemory (requêtes fragmentées pour limites URL / quota).
 */
function valorys_mymemory_translate_chunk(string $text, string $langpair): string|false
{
    $url = 'https://api.mymemory.translated.net/get?q=' . rawurlencode($text) . '&langpair=' . rawurlencode($langpair);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header'  => "Accept: application/json\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return false;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['responseData']['translatedText'])) {
        return false;
    }
    $out = (string) $data['responseData']['translatedText'];
    if (stripos($out, 'MYMEMORY') !== false && (stripos($out, 'QUOTA') !== false || stripos($out, 'LIMIT') !== false)) {
        return false;
    }
    return $out;
}

function valorys_mymemory_translate(string $text, string $langpair): string|false
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $maxChunk = 400;
    $parts    = [];
    $len      = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    $offset   = 0;
    while ($offset < $len) {
        if (function_exists('mb_substr')) {
            $chunk = mb_substr($text, $offset, $maxChunk, 'UTF-8');
            $step  = mb_strlen($chunk, 'UTF-8');
        } else {
            $chunk = substr($text, $offset, $maxChunk);
            $step  = strlen($chunk);
        }
        $translated = valorys_mymemory_translate_chunk($chunk, $langpair);
        if ($translated === false) {
            return false;
        }
        $parts[] = $translated;
        $offset += $step;
    }
    return implode('', $parts);
}

// Routes API
if ($page === 'api_article') {
    $rawBody = file_get_contents('php://input');
    $bodyData = json_decode($rawBody, true) ?? [];
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    if ($method === 'POST' && !empty($bodyData['_method'])) {
        $method = strtoupper($bodyData['_method']);
    }
    if ($method === 'GET' && isset($_GET['list'])) {
        $articleCtrl->list();
    } elseif ($method === 'GET' && $id) {
        $articleCtrl->show($id);
    } elseif ($method === 'POST') {
        requireLogin();
        // Corps JSON déjà lu ci-dessus (php://input n’est lisible qu’une fois).
        $articleCtrl->store($bodyData);
    } elseif ($method === 'PUT' && $id) {
        requireLogin();
        $articleCtrl->update($id, $bodyData);
    } elseif ($method === 'DELETE' && $id) {
        requireLogin();
        $articleCtrl->destroy($id);
    } else {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
    exit;
}

if ($page === 'api_article_like') {
    $articleCtrl->toggleLikeArticle();
    exit;
}

if ($page === 'api_gamification' && $gamificationCtrl) {
    $act = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? 'stats'));
    if ($act === 'stats') {
        $gamificationCtrl->stats();
        exit;
    }
    if ($act === 'leaderboard') {
        $gamificationCtrl->leaderboard();
        exit;
    }
    if ($act === 'history') {
        $gamificationCtrl->history();
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    exit;
}

// Traduction article (serveur → API MyMemory, sans redirection Google)
if ($page === 'api_translate') {
    header('Content-Type: application/json; charset=utf-8');
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }
    $raw = json_decode(file_get_contents('php://input'), true) ?? [];
    $title = isset($raw['title']) ? (string)$raw['title'] : '';
    $body  = isset($raw['body']) ? (string)$raw['body'] : '';
    $lang  = preg_replace('/[^a-z]/', '', strtolower((string)($raw['lang'] ?? 'en')));
    if (!in_array($lang, ['en', 'ar'], true)) {
        echo json_encode(['success' => false, 'message' => 'Langue non supportée']);
        exit;
    }
    $source = 'fr';
    $pair = $source . '|' . $lang;
    $tTitle = valorys_mymemory_translate($title, $pair);
    $tBody  = valorys_mymemory_translate($body, $pair);
    if ($tTitle === false || $tBody === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Traduction temporairement indisponible. Réessayez dans quelques instants.',
        ]);
        exit;
    }
    echo json_encode([
        'success' => true,
        'title'   => $tTitle,
        'body'    => $tBody,
        'lang'    => $lang,
    ]);
    exit;
}

if ($page === 'api_reply') {
    $rawBody = file_get_contents('php://input');
    $bodyData = json_decode($rawBody, true) ?? [];
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    if ($method === 'POST' && !empty($bodyData['_method'])) {
        $method = strtoupper($bodyData['_method']);
    }
    $articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
    
    // GET avec ID - récupérer un commentaire spécifique (pour modification)
    if ($method === 'GET' && isset($_GET['id'])) {
        requireLogin();
        $replyCtrl->show((int)$_GET['id']);
        exit;
    }
    
    if ($method === 'GET' && isset($_GET['all'])) {
        requireLogin();
        $replyCtrl->all();
        exit;
    }
    
    if ($method === 'GET' && $articleId) {
        $replyCtrl->index($articleId);
        exit;
    }
    
    if ($method === 'POST') {
        requireLogin();
        $replyCtrl->store($bodyData);
        exit;
    }
    
    if ($method === 'PUT' && $id) {
        requireLogin();
        $replyCtrl->update($id, $bodyData);
        exit;
    }
    
    if ($method === 'DELETE' && $id) {
        requireLogin();
        $replyCtrl->destroy($id);
        exit;
    }
    
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

if ($page === 'api_rdv_chatbot') {
    if (!$rendezVousCtrl) {
        http_response_code(501);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'reply' => 'Le module rendez-vous est indisponible.']);
        exit;
    }
    $rendezVousCtrl->apiRendezVousChatbot();
    exit;
}

// =============================================
// VÉRIFICATION PAGES PROTÉGÉES
// =============================================

// Redirection si page protégée et non connecté
if (!in_array($page, $publicPages) && !$isLoggedIn) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error'] = 'Veuillez vous connecter pour accéder à cette page.';
    $front->showAccessDenied();
    exit;
}

// Redirection si connecté et sur page réservée aux guests
if ($isLoggedIn && in_array($page, $guestOnlyPages)) {
    if ($page === 'login') {
        $redirects = [
            'admin' => 'dashboard',
            'medecin' => 'accueil',
            'patient' => 'accueil',
        ];
        $redirectPage = $redirects[$userRole] ?? 'accueil';
        header('Location: index.php?page=' . $redirectPage);
        exit;
    } else {
        header('Location: index.php?page=accueil');
        exit;
    }
}

// =============================================
// FONCTIONS HELPERS
// =============================================
function adminOnly(): void {
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        $_SESSION['error'] = 'Accès non autorisé.';
        header('Location: index.php?page=login');
        exit;
    }
}

function patientOnly(): void {
    if (($_SESSION['user_role'] ?? '') !== 'patient') {
        $_SESSION['error'] = 'Accès réservé aux patients.';
        header('Location: index.php?page=login');
        exit;
    }
}

function medecinOnly(): void {
    if (($_SESSION['user_role'] ?? '') !== 'medecin') {
        $_SESSION['error'] = 'Accès réservé aux médecins.';
        header('Location: index.php?page=login');
        exit;
    }
}

function patientOrMedecinOnly(): void {
    if (!in_array($_SESSION['user_role'] ?? '', ['patient', 'medecin'])) {
        $_SESSION['error'] = 'Accès réservé aux patients et médecins.';
        header('Location: index.php?page=login');
        exit;
    }
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Veuillez vous connecter.';
        header('Location: index.php?page=login');
        exit;
    }
}

function showFlash(): void {
    foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $bsClass) {
        if (isset($_SESSION[$key])) {
            $icons = ['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'];
            echo '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show" role="alert">'
               . '<i class="fas fa-' . $icons[$key] . ' me-2"></i>'
               . htmlspecialchars($_SESSION[$key])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION[$key]);
        }
    }
    if (isset($_SESSION['flash'])) {
        $f   = $_SESSION['flash'];
        $map = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
        $bc  = $map[$f['type']] ?? 'secondary';
        echo '<div class="alert alert-' . $bc . ' alert-dismissible fade show" role="alert">'
           . htmlspecialchars($f['message'])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['flash']);
    }
}

if (DEBUG_MODE) {
    echo "<!-- DEBUG: Page = $page | Role = $userRole | Connecté = " . ($isLoggedIn ? 'OUI' : 'NON') . " -->\n";
}

// =============================================
// ROUTAGE PRINCIPAL
// =============================================
switch ($page) {

    // ─── Pages publiques ───────────────────
    case 'accueil':
        $front->accueilPublic();
        break;

    case 'medecins':
        $front->listeMedecins();
        break;

    case 'detail_medecin':
        $front->detailMedecin($id);
        break;

    case 'blog_public':
        $front->blogList();
        break;

    case 'detail_article_public':
        $front->blogDetail($id);
        break;

    case 'evenements':
        $front->listeEvenements();
        break;

    case 'detail_evenement':
        $front->detailEvenement($id);
        break;

    case 'event_register':
        $front->registerEventAction();
        break;

    case 'event_unregister':
        $front->unregisterEventAction();
        break;

    case 'mes_inscriptions':
        $front->mesInscriptionsEvenements();
        break;

    case 'sponsors':
        $front->listSponsors();
        break;

    case 'contact':
        $front->contact();
        break;

    case 'about':
        $front->about();
        break;
case 'admin_article_create':
    requireLogin();
    $front->adminArticleCreate();
    break;

    case 'admin_article_edit':
    requireLogin();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $front->adminArticleEdit($id);
    break;
    
    // ─── Authentification ──────────────────
    case 'login':
        if ($isLoggedIn) {
            header('Location: index.php?page=' . ($userRole === 'admin' ? 'dashboard' : 'accueil'));
            exit;
        }
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $auth->login() : $auth->showLogin();
        break;

    case 'register':
        if ($isLoggedIn) {
            header('Location: index.php?page=accueil');
            exit;
        }
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $auth->register() : $auth->showRegister();
        break;

    case 'forgot_password':
        if ($isLoggedIn) {
            header('Location: index.php?page=accueil');
            exit;
        }
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $auth->forgotPassword() : $auth->showForgotPassword();
        break;

    case 'reset_password':
        if ($isLoggedIn) {
            header('Location: index.php?page=accueil');
            exit;
        }
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $auth->resetPassword() : $auth->showResetPassword($_GET['token'] ?? null);
        break;

    case 'social_login':
        if ($isLoggedIn) {
            header('Location: index.php?page=' . ($userRole === 'admin' ? 'dashboard' : 'accueil'));
            exit;
        }
        $auth->startSocialLogin($_GET['provider'] ?? '');
        break;

    case 'social_callback':
        if ($isLoggedIn) {
            header('Location: index.php?page=' . ($userRole === 'admin' ? 'dashboard' : 'accueil'));
            exit;
        }
        $auth->handleSocialCallback($_GET['provider'] ?? '');
        break;

    case 'verify_2fa':
        if ($isLoggedIn) {
            header('Location: index.php?page=' . ($userRole === 'admin' ? 'dashboard' : 'accueil'));
            exit;
        }
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $auth->verifyTwoFactorCode() : $auth->showVerifyTwoFactor();
        break;

    case 'resend_2fa':
        if ($isLoggedIn) {
            header('Location: index.php?page=accueil');
            exit;
        }
        $auth->resendTwoFactorCode();
        break;

    case 'logout':
        $auth->logout();
        break;

    // ─── Profil utilisateur (même traitement : avatar, mot de passe, etc.) ─
    case 'profil':
    case 'mon_profil':
        requireLogin();
        $front->monProfil();
        break;

    case 'mes_notifications':
        requireLogin();
        $front->mesNotifications();
        break;
case 'modifier_profil':
    requireLogin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $actionPost = $_POST['action'] ?? '';
        if ($actionPost === 'change_password') {
            $userCtrl->changePassword();
        } else {
            $userCtrl->updateProfil();
        }
    } else {
        // Utiliser FrontController au lieu de UserController
        $front->modifierProfil();
    }
    break;
    // ─── Rendez-vous ───────────────────────
    case 'prendre_rendez_vous':
        patientOnly();
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $patientCtrl->createAppointment()
            : $front->prendreRendezVous($id);
        break;

    case 'mes_rendez_vous':
        patientOrMedecinOnly();
        $front->mesRendezVous();
        break;

    case 'annuler_rendez_vous':
        patientOnly();
        $patientCtrl->cancelAppointment($id ?? 0);
        break;

    case 'confirmer_rendez_vous':
        medecinOnly();
        if ($rendezVousCtrl && $id) {
            $rendezVousCtrl->medecinConfirmerRendezVous((int)$id);
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Confirmation impossible (module RDV ou identifiant manquant).'];
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        break;

    case 'mes_ordonnances':
        patientOnly();
        $front->mesOrdonnances();
        break;

    // ─── BACKOFFICE ADMIN ──────────────────
    case 'dashboard':
        adminOnly();
        $adminCtrl->dashboard();
        break;

    case 'users':
        adminOnly();
        if ($action === 'create') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->createUser() : $adminCtrl->showCreateUser();
        } elseif ($action === 'edit' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateUser($id) : $adminCtrl->editUser($id);
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteUser($id);
        } elseif ($action === 'toggle' && $id) {
            $adminCtrl->toggleStatus($id);
        } elseif ($action === 'show' && $id) {
            $adminCtrl->showUser($id);
        } else {
            $adminCtrl->listUsers();
        }
        break;

    case 'patients':
        adminOnly();
        if ($action === 'add') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->addPatient() : $adminCtrl->showAddPatient();
        } elseif ($action === 'edit' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updatePatient($id) : $adminCtrl->editPatient($id);
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deletePatient($id);
        } elseif ($action === 'show' && $id) {
            $adminCtrl->showPatient($id);
        } else {
            $adminCtrl->listPatients();
        }
        break;

    case 'medecins_admin':
        adminOnly();
        if ($action === 'add') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->addMedecin() : $adminCtrl->showAddMedecin();
        } elseif ($action === 'edit' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateMedecin($id) : $adminCtrl->editMedecin($id);
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteMedecin($id);
        } elseif ($action === 'show' && $id) {
            $adminCtrl->showMedecin($id);
        } elseif ($action === 'validate' && $id) {
            $adminCtrl->validateMedecin($id);
        } elseif ($action === 'approve' && $id) {
            $adminCtrl->approveMedecin($id);
        } elseif ($action === 'reject' && $id) {
            $adminCtrl->rejectMedecin($id);
        } else {
            $adminCtrl->listMedecins();
        }
        break;

    case 'rendez_vous_admin':
        adminOnly();
        if ($action === 'create') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->createRendezVous() : $adminCtrl->showCreateRendezVous();
        } elseif ($action === 'edit' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateRendezVous($id) : $adminCtrl->editRendezVous($id);
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteRendezVous($id);
        } elseif ($action === 'show' && $id) {
            $adminCtrl->showRendezVous($id);
        } else {
            $adminCtrl->listRendezVous();
        }
        break;

case 'articles_admin':
    requireLogin();
    if ($action === 'create') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $front->adminArticleCreate();
        } else {
            $front->adminArticleCreate();
        }
    } elseif ($action === 'edit' && $id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $front->adminArticleEdit($id);
        } else {
            $front->adminArticleEdit($id);
        }
    } elseif ($action === 'delete' && $id) {
        $front->adminArticleDelete($id);
    } else {
        // Liste admin : même écran que blog_public (mode admin), URL cohérente avec la sidebar
        $front->blogList();
    }
    break;

    case 'evenements_admin':
        adminOnly();
        if ($action === 'create') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->createEvent() : $adminCtrl->showCreateEvent();
        } elseif ($action === 'edit') {
            $editId = $id ?: (int)($_POST['id'] ?? 0);
            if ($editId > 0) {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateEvent($editId) : $adminCtrl->editEvent($editId);
            } else {
                $adminCtrl->listEvents();
            }
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteEvent($id);
        } else {
            $adminCtrl->listEvents();
        }
        break;

    // ─── Pharmacie — Produits (backoffice) ───────────────────
    case 'produits_admin':
        adminOnly();
        if ($pharmacieCtrl) {
            if ($action === 'add_promo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $pharmacieCtrl->addPromo();
                break;
            } elseif ($action === 'delete_promo' && isset($_GET['code'])) {
                $pharmacieCtrl->deletePromo();
                break;
            }
            if ($action === 'export_csv') {
                $pharmacieCtrl->exportProduitsCsv();
            } elseif ($action === 'create') {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->createProduit() : $pharmacieCtrl->showCreateProduit();
            } elseif ($action === 'edit' && $id) {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->updateProduit($id) : $pharmacieCtrl->editProduit($id);
            } elseif ($action === 'show' && $id) {
                $pharmacieCtrl->showProduit($id);
            } elseif ($action === 'delete' && $id) {
                $pharmacieCtrl->deleteProduit($id);
            } else {
                $pharmacieCtrl->listProduits();
            }
        }
        break;

    // ─── Pharmacie — Catégories (backoffice) ─────────────────
    case 'categories_admin':
        adminOnly();
        if ($pharmacieCtrl) {
            if ($action === 'export_csv') {
                $pharmacieCtrl->exportCategoriesCsv();
            }
            if ($action === 'create') {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->createCategorie() : $pharmacieCtrl->showCreateCategorie();
            } elseif ($action === 'edit' && $id) {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->updateCategorie($id) : $pharmacieCtrl->editCategorie($id);
            } elseif ($action === 'delete' && $id) {
                $pharmacieCtrl->deleteCategorie($id);
            } else {
                $pharmacieCtrl->listCategories();
            }
        }
        break;

    // ─── Pharmacie — Commandes (backoffice) ──────────────────
    case 'commandes_admin':
        adminOnly();
        if ($pharmacieCtrl) {
            if ($action === 'export_csv') {
                $pharmacieCtrl->exportCommandesCsv();
            }
            if ($action === 'create') {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->createCommande() : $pharmacieCtrl->showCreateCommande();
            } elseif ($action === 'edit' && $id) {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->updateCommande($id) : $pharmacieCtrl->editCommande($id);
            } elseif ($action === 'show' && $id) {
                $pharmacieCtrl->showCommande($id);
            } elseif ($action === 'delete' && $id) {
                $pharmacieCtrl->deleteCommande($id);
            } elseif ($action === 'update_statut' && $id) {
                $pharmacieCtrl->updateStatutCommande($id);
            } else {
                $pharmacieCtrl->listCommandes();
            }
        }
        break;

    // ─── Pharmacie — Catalogue (frontoffice) ─────────────────
    case 'parapharmacie':
    case 'pharmacie':
    case 'catalogue':
        if ($pharmacieCtrl) $pharmacieCtrl->pharmacieFront();
        break;

    case 'produit_detail':
        if ($pharmacieCtrl && $id) $pharmacieCtrl->produitDetail($id);
        break;

    // ─── Pharmacie — Commandes client (frontoffice) ──────────
    case 'commander':
        requireLogin();
        if ($pharmacieCtrl) {
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? $pharmacieCtrl->createCommandeFront()
                : header('Location: index.php?page=parapharmacie');
        }
        break;

    case 'mes_commandes':
        requireLogin();
        if ($pharmacieCtrl) {
            if ($action === 'edit' && $id) {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $pharmacieCtrl->updateCommandeFront($id) : $pharmacieCtrl->editCommandeFront($id);
            } elseif ($action === 'cancel' && $id) {
                $pharmacieCtrl->cancelCommandeFront($id);
            } else {
                $pharmacieCtrl->mesCommandes();
            }
        }
        break;

    case 'panier':
        requireLogin();
        if ($pharmacieCtrl) {
            if ($action === 'add' && $id) {
                $pharmacieCtrl->ajouterAuPanier($id);
            } elseif ($action === 'remove' && $id) {
                $pharmacieCtrl->retirerDuPanier($id);
            } elseif ($action === 'clear') {
                $pharmacieCtrl->viderPanier();
            } elseif ($action === 'promo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $pharmacieCtrl->appliquerCodePromoPanier();
            } elseif ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $pharmacieCtrl->validerPanier();
            } else {
                $pharmacieCtrl->panier();
            }
        }
        break;

    case 'blog':
        adminOnly();
        $articleCtrl->index();
        break;

    case 'sponsors_admin':
        adminOnly();
        if (class_exists('SponsorController')) {
            $sponsorCtrl = new SponsorController();
            if ($action === 'delete' && $id) {
                $sponsorCtrl->delete($id);
            } elseif ($action === 'create') {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $sponsorCtrl->store() : $sponsorCtrl->create();
            } elseif ($action === 'edit') {
                $editId = $id ?: (int)($_POST['id'] ?? $_GET['id'] ?? 0);
                if ($editId > 0) {
                    $_SERVER['REQUEST_METHOD'] === 'POST' ? $sponsorCtrl->update($editId) : $sponsorCtrl->edit($editId);
                } else {
                    $sponsorCtrl->index();
                }
            } elseif ($action === 'show' && $id) {
                $sponsorCtrl->show($id);
            } else {
                $sponsorCtrl->index();
            }
        } else {
            $front->page404();
        }
        break;

    case 'participations':
        adminOnly();
        if (class_exists('ParticipationController')) {
            $partCtrl = new ParticipationController();
            if ($action === 'delete' && $id) {
                $partCtrl->delete($id);
            } elseif ($action === 'edit') {
                $editId = $id ?: (int)($_POST['id'] ?? $_GET['id'] ?? 0);
                if ($editId > 0) {
                    $_SERVER['REQUEST_METHOD'] === 'POST' ? $partCtrl->update($editId) : $partCtrl->edit($editId);
                } else {
                    $partCtrl->indexAdmin();
                }
            } elseif ($action === 'create') {
                $_SERVER['REQUEST_METHOD'] === 'POST' ? $partCtrl->store() : $partCtrl->create();
            } else {
                $partCtrl->indexAdmin();
            }
        } else {
            $front->page404();
        }
        break;

    case 'carte':
        adminOnly();
        if (class_exists('MapController')) {
            $mapCtrl = new MapController();
            if ($action === 'metiers') {
                $mapCtrl->metiers();
            } else {
                $mapCtrl->carte();
            }
        } else {
            $front->page404();
        }
        break;

    case 'stats':
        adminOnly();
        $adminCtrl->stats();
        break;

    case 'logs':
        adminOnly();
        $action === 'export' ? $adminCtrl->exportLogs() : $adminCtrl->logs();
        break;

    case 'settings':
        adminOnly();
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateSettings() : $adminCtrl->settings();
        break;

// ─── Ordonnances ───────────────────────
case 'ordonnance':
case 'ordonnances':
    requireLogin();
    if ($ordonnanceCtrl) {
        if ($action === 'create') {
            // Création d'une ordonnance
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($userRole === 'medecin') {
                    $ordonnanceCtrl->storeMedecin();
                } else {
                    $ordonnanceCtrl->storeAdmin();
                }
            } else {
                if ($userRole === 'medecin') {
                    $ordonnanceCtrl->createMedecin();
                } else {
                    $ordonnanceCtrl->createAdmin();
                }
            }
        } elseif ($action === 'edit' && $id) {
            // Modification d'une ordonnance
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($userRole === 'medecin') {
                    $ordonnanceCtrl->updateMedecin($id);
                } else {
                    $ordonnanceCtrl->updateAdmin($id);
                }
            } else {
                if ($userRole === 'medecin') {
                    $ordonnanceCtrl->editMedecin($id);
                } else {
                    $ordonnanceCtrl->editAdmin($id);
                }
            }
        } elseif ($action === 'delete' && $id) {
            // Suppression d'une ordonnance
            $ordonnanceCtrl->deleteAdmin($id);
        } elseif ($action === 'show' && $id) {
            // Affichage d'une ordonnance
            if ($userRole === 'patient') {
                $ordonnanceCtrl->showPatient($id);
            } elseif ($userRole === 'medecin') {
                $ordonnanceCtrl->showMedecin($id);
            } else {
                $ordonnanceCtrl->showAdmin($id);
            }
        } elseif ($action === 'pdf' && $id) {
            // Téléchargement PDF
            $ordonnanceCtrl->downloadPatient($id);
        } else {
            // Liste des ordonnances
            if ($userRole === 'patient') {
                $ordonnanceCtrl->indexPatient();
            } elseif ($userRole === 'medecin') {
                $ordonnanceCtrl->indexMedecin();
            } else {
                $ordonnanceCtrl->indexAdmin();
            }
        }
    } else {
        $front->page404();
    }
    break;


   case 'admin_rendezvous':
    adminOnly();
    // Alias historique (même comportement que rendez_vous_admin ; POST conservé)
    if ($action === 'create') {
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->createRendezVous() : $adminCtrl->showCreateRendezVous();
    } elseif ($action === 'edit' && $id) {
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateRendezVous($id) : $adminCtrl->editRendezVous($id);
    } elseif ($action === 'delete' && $id) {
        $adminCtrl->deleteRendezVous($id);
    } elseif ($action === 'show' && $id) {
        $adminCtrl->showRendezVous($id);
    } else {
        $adminCtrl->listRendezVous();
    }
    break;
// ─── Disponibilités Front Office ───────────────────
case 'patient_disponibilites':
    patientOnly();
    $front->patientDisponibilites();
    break;

case 'medecin_disponibilites':
    requireLogin();
    medecinOnly();
 
    $dispoAction = trim($_GET['action'] ?? '');
    $dispoId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
 
    if ($dispoAction === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $front->medecinStoreDisponibilite();
    } elseif ($dispoAction === 'toggle' && $dispoId > 0) {
        $front->medecinToggleDisponibilite($dispoId);
    } elseif ($dispoAction === 'delete' && $dispoId > 0) {
        $front->medecinDeleteDisponibilite($dispoId);
    } else {
        $front->medecinDisponibilites();
    }
    break;
case 'disponibilites_admin':
    adminOnly();
    if ($action === 'create') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminCtrl->createDisponibilite();
        } else {
            $adminCtrl->showCreateDisponibilite();
        }
    } elseif ($action === 'edit' && $id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminCtrl->updateDisponibilite($id);
        } else {
            $adminCtrl->editDisponibilite($id);
        }
    } elseif ($action === 'delete' && $id) {
        $adminCtrl->deleteDisponibilite($id);
    } else {
        $adminCtrl->listDisponibilites();
    }
    break;

    // ─── Disponibilités ────────────────────
    case 'disponibilite':
case 'disponibilites':
    requireLogin();
    if ($action === 'store') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $disponibiliteCtrl->storeMedecin();
        } else {
            $disponibiliteCtrl->createMedecin();
        }
    } elseif ($action === 'toggle' && $id) {
        $disponibiliteCtrl->toggle($id);
    } elseif ($action === 'delete' && $id) {
        $disponibiliteCtrl->delete($id);
    } elseif ($action === 'edit' && $id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $disponibiliteCtrl->updateMedecin($id);
        } else {
            $disponibiliteCtrl->editMedecin($id);
        }
    } else {
        if ($userRole === 'medecin') {
            $disponibiliteCtrl->indexMedecin();
        } elseif ($userRole === 'admin') {
            $disponibiliteCtrl->indexAdmin();
        } else {
            $front->page403();
        }
    }
    break;
case 'detail_rendez_vous':
    requireLogin();
    if ($userRole === 'medecin') {
        $medecinCtrl->showRendezVous($id);
    } elseif ($userRole === 'patient') {
        $patientCtrl->showRendezVous($id);
    } else {
        $front->page403();
    }
    break;


    // ─── Ordonnances depuis rendez-vous ───────────────────
case 'creer_ordonnance_rdv':
    medecinOnly();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ordonnanceCtrl->storeFromRendezVous();
    }
    break;

case 'modifier_ordonnance_rdv':
    medecinOnly();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ordonnanceCtrl->updateFromRendezVous();
    }
    break;

case 'supprimer_ordonnance_rdv':
    medecinOnly();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $rdv_id = isset($_GET['rdv_id']) ? (int)$_GET['rdv_id'] : 0;
    $ordonnanceCtrl->deleteFromRendezVous($id, $rdv_id);
    break;

case 'api_ordonnance':
    requireLogin();
    $apiOrdId = (int)($_GET['id'] ?? 0);
    if ($apiOrdId > 0 && $ordonnanceCtrl) {
        $ordonnanceCtrl->apiGet($apiOrdId);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
    }
    break;

case 'download_ordonnance':
    requireLogin();
    $dlOrdId = (int)($_GET['id'] ?? 0);
    if ($dlOrdId > 0 && $ordonnanceCtrl) {
        $ordonnanceCtrl->downloadPdf($dlOrdId);
    } else {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Paramètre id manquant.';
    }
    break;

case 'modifier_rendez_vous':
    patientOnly();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $patientCtrl->updateRendezVous();
    }
    break;

case 'supprimer_rendez_vous':
    patientOnly();
    $patientCtrl->deleteRendezVous($id);
    break;

    case 'detail_rendez_vous':
    requireLogin();
    if ($userRole === 'medecin') {
        $medecinCtrl->showRendezVous($id);
    } elseif ($userRole === 'patient') {
        $patientCtrl->showRendezVous($id);
    } else {
        $front->page403();
    }
    break;

    // ─── API AJAX ──────────────────────────
// ─── API AJAX ──────────────────────────
// ─── API AJAX ──────────────────────────
// ─── API AJAX ──────────────────────────
case 'api':
    requireLogin();
    header('Content-Type: application/json');
    
    // Action : GET > POST > corps JSON (les fetch JSON ne remplissent pas $_POST)
    $apiAction = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/', '', trim((string)$_GET['action'])) : '';
    if ($apiAction === '' && isset($_POST['action'])) {
        $apiAction = preg_replace('/[^a-z0-9_]/', '', trim((string)$_POST['action']));
    }
    if ($apiAction === '') {
        $apiRaw = file_get_contents('php://input');
        if ($apiRaw !== '' && $apiRaw !== false) {
            $apiJson = json_decode($apiRaw, true);
            if (is_array($apiJson) && isset($apiJson['action'])) {
                $apiAction = preg_replace('/[^a-z0-9_]/', '', trim((string)$apiJson['action']));
            }
        }
    }
    
    switch ($apiAction) {
        case 'get_disponibilites':
            $rendezVousCtrl ? $rendezVousCtrl->getDisponibilitesJson($id) : http_response_code(501);
            break;
        case 'check_email':
            $auth->checkEmail();
            break;
        case 'stats':
            adminOnly();
            $adminCtrl->apiStats();
            break;
        case 'delete_face':
            $auth->deleteFace();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint introuvable']);
    }
    break;

    // ─── 404 ───────────────────────────────
    default:
        if (DEBUG_MODE) echo "<!-- DEBUG: 404 page='$page' -->\n";
        http_response_code(404);
        $front->page404();
        break;
}

if (DEBUG_MODE) echo "<!-- DEBUG: Switch terminé pour page='$page' -->\n";
?>
