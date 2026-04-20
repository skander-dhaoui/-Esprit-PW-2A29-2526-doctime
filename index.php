<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    'RendezVous', 'Disponibilite', 'Event', 'Produit', 'Ordonnance', 'Participation', 'Sponsor', 'Categorie',
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
    'ProduitController', 'OrdonnanceController', 'DisponibiliteController', 'ParticipationController', 'SponsorController', 'CategorieController',
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
    'evenements', 'detail_evenement', 'event_register', 'sponsors', 'contact', 'about',
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

    case 'event_register':
        $front->registerEventAction();
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

    case 'admin_article_delete':
        requireLogin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $front->adminArticleDelete($id);
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
    case 'mes_notifications':
        requireLogin();
        $front->mesNotifications();
        break;

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
        } else {
            $userCtrl->showProfil();
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
        if ($action === 'create') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $adminCtrl->createRendezVous();
            } else {
                $adminCtrl->showCreateRendezVous();
            }
        } elseif ($action === 'view' && $id) {
            $adminCtrl->viewRendezVous($id);
        } elseif ($action === 'add_comment' && $id) {
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? $adminCtrl->addCommentRendezVous($id)
                : header('Location: index.php?page=rendez_vous_admin&action=view&id=' . $id);
        } elseif ($action === 'edit' && $id) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $adminCtrl->updateRendezVous($id);
            } else {
                $adminCtrl->editRendezVous($id);
            }
        } elseif ($action === 'delete' && $id) {
            $adminCtrl->deleteRendezVous($id);
        } elseif ($action === 'show' && $id) {
            $adminCtrl->showRendezVous($id);
        } elseif ($action === 'advanced') {
            $adminCtrl->advancedRendezVous();
        } else {
            $adminCtrl->listRendezVous();
        }
        break;

case 'articles_admin':
    adminOnly();
    if ($action === 'create') {
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $adminCtrl->createArticle()
            : $adminCtrl->showCreateArticle();
    } elseif ($action === 'view' && $id) {
        $adminCtrl->viewArticle($id);
    } elseif ($action === 'add_comment' && $id) {
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $adminCtrl->addComment($id)
            : header('Location: index.php?page=articles_admin&action=view&id=' . $id);
    } elseif ($action === 'edit' && $id) {
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $adminCtrl->updateArticle($id)
            : $adminCtrl->editArticle($id);
    } elseif ($action === 'delete' && $id) {
        $adminCtrl->deleteArticle($id);
    } elseif ($action === 'advanced') {
        $adminCtrl->advancedArticles();
    } else {
        $adminCtrl->listArticles();
    }
    break;

case 'evenements_admin':
    adminOnly();
    $eventCtrl = class_exists('EventController') ? new EventController() : null;
    if (!$eventCtrl) { $front->page404(); break; }

    if ($action === 'create') {
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $eventCtrl->store() : $eventCtrl->create();
    } elseif ($action === 'edit' && $id) {
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $eventCtrl->update($id) : $eventCtrl->edit($id);
    } elseif ($action === 'delete' && $id) {
        $eventCtrl->delete($id);
    } elseif ($action === 'show' && $id) {
        $eventCtrl->showAdmin($id);
    } elseif ($action === 'advanced') {
        $eventCtrl->advanced();
    } else {
        $eventCtrl->listAdmin();  // ← méthode qui charge backoffice/evenements/list.php
    }
    break;

case 'participations':
    adminOnly();
    $partCtrl = class_exists('ParticipationController') ? new ParticipationController() : null;
    if (!$partCtrl) { $front->page404(); break; }

    if ($action === 'delete' && $id) {
        $partCtrl->delete($id);
    } elseif ($action === 'create') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $partCtrl->store(); // We will add store() method
        } else {
            $partCtrl->create(); // We will add create() method
        }
    } elseif ($action === 'edit' && $id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $partCtrl->update($id);
        } else {
            $partCtrl->edit($id);
        }
    } else {
        $partCtrl->indexAdmin();
    }
    break;

case 'sponsors':
    adminOnly();
    $sponsorCtrl = class_exists('SponsorController') ? new SponsorController() : null;
    if (!$sponsorCtrl) { $front->page404(); break; }

    if ($action === 'delete' && $id) {
        $sponsorCtrl->delete($id);
    } elseif ($action === 'create') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sponsorCtrl->store(); 
        } else {
            $sponsorCtrl->create();
        }
    } elseif ($action === 'edit' && $id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sponsorCtrl->update($id);
        } else {
            $sponsorCtrl->edit($id);
        }
    } else {
        $sponsorCtrl->index();
    }
    break;

    case 'produits_admin':
        adminOnly();
        $produitCtrl = class_exists('ProduitController') ? new ProduitController() : null;
        if (!$produitCtrl) { $front->page404(); break; }

        if ($action === 'create') {
            $produitCtrl->create();
        } elseif ($action === 'edit' && $id) {
            $produitCtrl->edit($id);
        } elseif ($action === 'delete' && $id) {
            $produitCtrl->delete($id);
        } else {
            $produitCtrl->manage();
        }
        break;

    case 'categories_admin':
        adminOnly();
        $catCtrl = class_exists('CategorieController') ? new CategorieController() : null;
        if (!$catCtrl) { $front->page404(); break; }

        if ($action === 'create') {
            $catCtrl->create();
        } elseif ($action === 'edit' && $id) {
            $catCtrl->edit($id);
        } elseif ($action === 'delete' && $id) {
            $catCtrl->delete($id);
        } else {
            $catCtrl->index();
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


// ─── Disponibilités Front Office ───────────────────
case 'patient_disponibilites':
    patientOnly();
    $front->patientDisponibilites();
    break;

case 'medecin_disponibilites':
    medecinOnly();
    if ($action === 'store') {
        $front->medecinStoreDisponibilite();
    } elseif ($action === 'toggle' && $id) {
        $front->medecinToggleDisponibilite($id);
    } elseif ($action === 'delete' && $id) {
        $front->medecinDeleteDisponibilite($id);
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
    if ($action === 'get' && $id) {
        $ordonnanceCtrl->apiGet($id);
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
    
    // Récupérer l'action depuis $_GET ou $_POST
    $apiAction = $action;
    if (empty($apiAction) && isset($_POST['action'])) {
        $apiAction = $_POST['action'];
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