<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

define('DEBUG_MODE', false);

// =============================================
// INCLUDES — MODÈLES
// =============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Patient.php';
require_once __DIR__ . '/models/Medecin.php';
require_once __DIR__ . '/models/Admin.php';
require_once __DIR__ . '/models/Article.php';
require_once __DIR__ . '/models/Reply.php';

// Modèles optionnels
$optionalModels = [
    'RendezVous', 'Disponibilite', 'Event', 'Produit', 'Ordonnance',
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

// Contrôleurs optionnels
$optionalControllers = [
    'RendezVousController', 'EventController',
    'ProduitController', 'OrdonnanceController', 'DisponibiliteController',
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

$rendezVousCtrl    = class_exists('RendezVousController')    ? new RendezVousController()    : null;
$ordonnanceCtrl    = class_exists('OrdonnanceController')    ? new OrdonnanceController()    : null;
$disponibiliteCtrl = class_exists('DisponibiliteController') ? new DisponibiliteController() : null;

// =============================================
// PAGES PUBLIQUES / PROTÉGÉES
// =============================================
$publicPages = [
    'accueil', 'login', 'register', 'forgot_password', 'reset_password',
    'medecins', 'detail_medecin', 'blog_public', 'detail_article_public',
    'evenements', 'detail_evenement', 'contact', 'about',
];

$guestOnlyPages = ['register', 'forgot_password', 'reset_password', 'login'];

$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $_SESSION['user_role'] ?? '';

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

if ($page === 'register_face') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter d\'abord']);
            exit;
        }
        $auth->registerFace();
    } else {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    }
    exit;
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
        $articleCtrl->store();
    } elseif ($method === 'PUT' && $id) {
        requireLogin();
        $articleCtrl->update($id);
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
        $replyCtrl->store();
        exit;
    }
    
    if ($method === 'PUT' && $id) {
        requireLogin();
        $replyCtrl->update($id);
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

// =============================================
// VÉRIFICATION PAGES PROTÉGÉES
// =============================================

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

    case 'logout':
        $auth->logout();
        break;

    // ─── Profil utilisateur ────────────────
    case 'profil':
    case 'mon_profil':
    case 'modifier_profil':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actionPost = $_POST['action'] ?? '';
            if ($actionPost === 'change_password') {
                $userCtrl->changePassword();
            } else {
                $userCtrl->updateProfil();
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'update_avatar') {
            $userCtrl->updateAvatar();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete_avatar') {
            $userCtrl->deleteAvatar();
        } else {
            $userCtrl->showProfil();
        }
        break;

    case 'mes_notifications':
        requireLogin();
        $front->mesNotifications();
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
        $front->confirmerRendezVous($id);
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
        $action === 'delete' && $id ? $adminCtrl->deleteRendezVous($id) : $adminCtrl->listRendezVous();
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
        header('Location: index.php?page=blog_public');
    }
    break;

    case 'evenements_admin':
        adminOnly();
        if ($action === 'create') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->createEvent() : $adminCtrl->showCreateEvent();
        } elseif ($action === 'edit' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateEvent($id) : $adminCtrl->editEvent($id);
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteEvent($id);
        } else {
            $adminCtrl->listEvents();
        }
        break;

    case 'produits_admin':
        adminOnly();
        if ($action === 'create') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->createProduit() : $adminCtrl->showCreateProduit();
        } elseif ($action === 'edit' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $adminCtrl->updateProduit($id) : $adminCtrl->editProduit($id);
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteProduit($id);
        } else {
            $adminCtrl->listProduits();
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
            if ($userRole === 'patient') {
                $ordonnanceCtrl->indexPatient();
            } elseif ($userRole === 'medecin') {
                $action === 'create' ? $ordonnanceCtrl->createMedecin() : $ordonnanceCtrl->indexMedecin();
            } elseif ($userRole === 'admin') {
                $ordonnanceCtrl->indexAdmin();
            }
        } else {
            $front->page404();
        }
        break;

    // ─── Disponibilités ────────────────────
    case 'disponibilite':
    case 'disponibilites':
        requireLogin();
        if ($disponibiliteCtrl) {
            if ($userRole === 'medecin') {
                $action === 'create' ? $disponibiliteCtrl->createMedecin() : $disponibiliteCtrl->indexMedecin();
            } elseif ($userRole === 'admin') {
                $disponibiliteCtrl->indexAdmin();
            }
        } else {
            $front->page404();
        }
        break;

    // ─── API AJAX ──────────────────────────
    case 'api':
        requireLogin();
        header('Content-Type: application/json');
        switch ($action) {
            case 'get_disponibilites':
                $rendezVousCtrl
                    ? $rendezVousCtrl->getDisponibilitesJson($id)
                    : http_response_code(501);
                break;
            case 'check_email':
                $auth->checkEmail();
                break;
            case 'stats':
                adminOnly();
                $adminCtrl->apiStats();
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