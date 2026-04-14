<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

define('DEBUG_MODE', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Patient.php';
require_once __DIR__ . '/models/Medecin.php';
require_once __DIR__ . '/models/Admin.php';
require_once __DIR__ . '/models/Article.php';
require_once __DIR__ . '/models/Reply.php';

$optionalModels = ['RendezVous', 'Disponibilite', 'Event', 'Produit', 'Ordonnance'];
foreach ($optionalModels as $model) {
    $path = __DIR__ . "/models/{$model}.php";
    if (file_exists($path)) require_once $path;
}

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/FrontController.php';
require_once __DIR__ . '/controllers/PatientController.php';
require_once __DIR__ . '/controllers/MedecinController.php';
require_once __DIR__ . '/controllers/ArticleController.php';
require_once __DIR__ . '/controllers/ReplyController.php';
require_once __DIR__ . '/controllers/RendezVousController.php';
require_once __DIR__ . '/controllers/DisponibiliteController.php';
require_once __DIR__ . '/controllers/OrdonnanceController.php';
require_once __DIR__ . '/controllers/EventController.php';

$optionalControllers = ['ProduitController'];
foreach ($optionalControllers as $ctrl) {
    $path = __DIR__ . "/controllers/{$ctrl}.php";
    if (file_exists($path)) require_once $path;
}

// ── Paramètres ──────────────────────────────────────────────
$page   = isset($_GET['page'])   ? preg_replace('/[^a-z0-9_]/', '', trim($_GET['page'])) : 'accueil';
$action = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/', '', trim($_GET['action'])) : 'index';
$id     = isset($_GET['id'])     ? (int)$_GET['id'] : null;
$slug   = isset($_GET['slug'])   ? preg_replace('/[^a-z0-9-]/', '', trim($_GET['slug'])) : null;

$isApiRoute = in_array($page, ['api_article', 'api_reply', 'api', 'api_slots', 'api_ordonnance']);

if (DEBUG_MODE && !$isApiRoute) {
    echo "<!-- DEBUG: page=$page action=$action id=$id slug=$slug -->\n";
}

// ── Contrôleurs ──────────────────────────────────────────────
$auth        = new AuthController();
$userCtrl    = new UserController();
$adminCtrl   = new AdminController();
$front       = new FrontController();
$patientCtrl = new PatientController();
$medecinCtrl = new MedecinController();
$articleCtrl = new ArticleController();
$replyCtrl   = new ReplyController();
$rdvCtrl     = new RendezVousController();
$dispoCtrl   = new DisponibiliteController();
$ordCtrl     = new OrdonnanceController();
$eventCtrl   = new EventController();

$rendezVousCtrl    = class_exists('RendezVousController')    ? $rdvCtrl : null;
$ordonnanceCtrl    = class_exists('OrdonnanceController')    ? $ordCtrl : null;
$disponibiliteCtrl = class_exists('DisponibiliteController') ? $dispoCtrl : null;

// ── Pages publiques ──────────────────────────────────────────
$publicPages = [
    'accueil','login','register','forgot_password','reset_password',
    'medecins','detail_medecin','articles','detail_article',
    'evenements','detail_evenement','contact','about',
    'blog_public','detail_article_public',
    'api_article', 'api_slots',
];

$guestOnlyPages = ['register','forgot_password','reset_password','login'];
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $_SESSION['user_role'] ?? '';

// Redirection si non connecté sur page protégée
if (!in_array($page, $publicPages) && !$isLoggedIn) {
    if ($isApiRoute) {
        while (ob_get_level()>0) ob_end_clean();
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'message'=>'Connexion requise.']);
        exit;
    }
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error'] = 'Veuillez vous connecter pour accéder à cette page.';
    $front->showAccessDenied();
    exit;
}

if ($isLoggedIn && in_array($page, $guestOnlyPages)) {
    $redirects = ['admin'=>'dashboard','medecin'=>'accueil','patient'=>'accueil'];
    header('Location: index.php?page=' . ($page==='login' ? ($redirects[$userRole]??'accueil') : 'accueil'));
    exit;
}

// ── Helpers ──────────────────────────────────────────────────
function adminOnly(): void {
    if (($_SESSION['user_role']??'')!=='admin') {
        $_SESSION['error']='Accès non autorisé.';
        header('Location: index.php?page=login');
        exit;
    }
}
function patientOnly(): void {
    if (($_SESSION['user_role']??'')!=='patient') {
        $_SESSION['error']='Accès réservé aux patients.';
        header('Location: index.php?page=login');
        exit;
    }
}
function medecinOnly(): void {
    if (($_SESSION['user_role']??'')!=='medecin') {
        $_SESSION['error']='Accès réservé aux médecins.';
        header('Location: index.php?page=login');
        exit;
    }
}
function patientOrMedecinOnly(): void {
    if (!in_array($_SESSION['user_role']??'',['patient','medecin'])) {
        $_SESSION['error']='Accès réservé.';
        header('Location: index.php?page=login');
        exit;
    }
}
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json')!==false) {
            while (ob_get_level()>0) ob_end_clean();
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false,'message'=>'Connexion requise.']);
            exit;
        }
        $_SESSION['error']='Veuillez vous connecter.';
        header('Location: index.php?page=login');
        exit;
    }
}

function showFlash(): void {
    foreach (['success'=>'success','error'=>'danger','warning'=>'warning'] as $key=>$bsClass) {
        if (isset($_SESSION[$key])) {
            $icons=['success'=>'check-circle','error'=>'exclamation-circle','warning'=>'exclamation-triangle'];
            echo '<div class="alert alert-'.$bsClass.' alert-dismissible fade show" role="alert">'
               .'<i class="fas fa-'.$icons[$key].' me-2"></i>'.htmlspecialchars($_SESSION[$key])
               .'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION[$key]);
        }
    }
    if (isset($_SESSION['flash'])) {
        $f=$_SESSION['flash'];
        $bc=['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'][$f['type']]??'secondary';
        echo '<div class="alert alert-'.$bc.' alert-dismissible fade show" role="alert">'.htmlspecialchars($f['message']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['flash']);
    }
}

if (DEBUG_MODE && !$isApiRoute) {
    echo "<!-- DEBUG: Page=$page | Role=$userRole | Connecté=".($isLoggedIn?'OUI':'NON')." -->\n";
}

// ════════════════════════════════════════════════════════════
// ROUTAGE
// ════════════════════════════════════════════════════════════
switch ($page) {

    // ─── Pages publiques ───────────────────────────────────
    case 'accueil':        $front->accueilPublic(); break;
    case 'medecins':       $front->listeMedecins(); break;
    case 'detail_medecin': $front->detailMedecin($id); break;
    case 'blog_public':    $front->blogList(); break;
    case 'detail_article_public': $front->blogDetail($id); break;
    case 'articles':       $front->blogList(); break;
    case 'detail_article': $front->blogDetail($id); break;

    // ─── ROUTES ÉVÉNEMENTS publiques ───────────────────────
    case 'evenements':
        $front->listeEvenements();
        break;

    case 'detail_evenement':
    case 'evenement':
        if ($slug) {
            $eventCtrl->frontShow($slug);
        } elseif ($id) {
            $event = $eventCtrl->getEventById($id);
            if ($event) {
                $eventCtrl->frontShow($event['slug']);
            } else {
                $front->page404();
            }
        } else {
            $front->page404();
        }
        break;

    // ─── CRUD ÉVÉNEMENTS FRONT (tous utilisateurs connectés) ─
    case 'evenement_create':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $eventCtrl->frontStore();
        } else {
            $eventCtrl->frontCreate();
        }
        break;

    case 'evenement_edit':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $eventCtrl->frontUpdate($id);
        } else {
            $eventCtrl->frontEdit($id);
        }
        break;

    case 'evenement_delete':
        requireLogin();
        $eventCtrl->frontDelete($id);
        break;

    // ─── INSCRIPTIONS ÉVÉNEMENTS ───────────────────────────
    case 'evenement_inscrire':
        requireLogin();
        $eventCtrl->frontRegister();
        break;

    case 'evenement_desinscrire':
        requireLogin();
        $eventCtrl->frontUnregister();
        break;

    case 'mes_inscriptions':
        requireLogin();
        $eventCtrl->mesInscriptions();
        break;

    case 'contact':        $front->contact(); break;
    case 'about':          $front->about(); break;

    // ─── Authentification ──────────────────────────────────
    case 'login':
        $_SERVER['REQUEST_METHOD']==='POST' ? $auth->login() : $auth->showLogin();
        break;
    case 'register':
        $_SERVER['REQUEST_METHOD']==='POST' ? $auth->register() : $auth->showRegister();
        break;
    case 'forgot_password':
        $_SERVER['REQUEST_METHOD']==='POST' ? $auth->forgotPassword() : $auth->showForgotPassword();
        break;
    case 'reset_password':
        $_SERVER['REQUEST_METHOD']==='POST' ? $auth->resetPassword() : $auth->showResetPassword($id);
        break;
    case 'logout': $auth->logout(); break;

    // ─── Profil utilisateur ────────────────────────────────
    case 'profil':
    case 'mon_profil':
    case 'modifier_profil':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            ($_POST['action']??'')==='change_password' ? $userCtrl->changePassword() : $userCtrl->updateProfil();
        } else {
            $userCtrl->showProfil();
        }
        break;

    case 'mes_notifications':
        requireLogin();
        $front->mesNotifications();
        break;

    // ════════════════════════════════════════════════════════
    // ════ BACKOFFICE ÉVÉNEMENTS — ADMIN UNIQUEMENT ═══════════
    // ════════════════════════════════════════════════════════
    case 'admin_evenements':
    case 'admin_events':
    case 'evenements_admin':
        adminOnly();
        if ($action === 'create')            { $eventCtrl->adminCreate(); }
        elseif ($action === 'store')         { $eventCtrl->adminStore(); }
        elseif ($action === 'edit' && $id)   { $eventCtrl->adminEdit($id); }
        elseif ($action === 'update' && $id) { $eventCtrl->adminUpdate($id); }
        elseif ($action === 'delete' && $id) { $eventCtrl->adminDelete($id); }
        elseif ($action === 'show' && $id)   { $eventCtrl->adminShow($id); }
        elseif ($action === 'toggle' && $id) { $eventCtrl->adminToggleStatus($id); }
        else                                 { $eventCtrl->adminIndex(); }
        break;

    // ════════════════════════════════════════════════════════
    // ════ RENDEZ-VOUS (FrontOffice Patient) ══════════════════
    // ════════════════════════════════════════════════════════
    case 'mes_rendez_vous':
    case 'mes_rendezvous_new':
        patientOnly();
        $rdvCtrl->patientMesRendezVous();
        break;

    case 'prendre_rendez_vous':
    case 'prendre_rendezvous':
    case 'prendre_rendezvous_new':
        patientOnly();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rdvCtrl->patientStoreRendezVous();
        } else {
            $rdvCtrl->patientPrendreRendezVous();
        }
        break;

    case 'annuler_rendez_vous':
    case 'annuler_rendezvous_new':
        patientOnly();
        $rdvCtrl->patientAnnulerRendezVous($id);
        break;

    case 'modifier_rendezvous':
        patientOnly();
        $rdvCtrl->patientModifierRendezVous($id);
        break;

    // ════════════════════════════════════════════════════════
    // ════ RENDEZ-VOUS (FrontOffice Médecin) ══════════════════
    // ════════════════════════════════════════════════════════
    case 'medecin_rendezvous':
        medecinOnly();
        if ($action === 'create')            { $rdvCtrl->medecinCreate(); }
        elseif ($action === 'store')         { $rdvCtrl->medecinStore(); }
        elseif ($action === 'edit' && $id)   { $rdvCtrl->medecinEdit($id); }
        elseif ($action === 'update' && $id) { $rdvCtrl->medecinUpdate($id); }
        elseif ($action === 'delete' && $id) { $rdvCtrl->medecinDelete($id); }
        elseif ($action === 'confirm' && $id){ $rdvCtrl->medecinConfirmerRendezVous($id); }
        elseif ($action === 'complete' && $id){ $rdvCtrl->medecinTerminerRendezVous($id); }
        elseif ($action === 'cancel' && $id) { $rdvCtrl->medecinAnnulerRendezVous($id); }
        elseif ($action === 'note' && $id)   { $rdvCtrl->medecinAjouterNote($id); }
        elseif ($action === 'save_note' && $id){ $rdvCtrl->medecinSaveNote($id); }
        else                                 { $rdvCtrl->medecinMesRendezVous(); }
        break;

    // ════════════════════════════════════════════════════════
    // ════ RENDEZ-VOUS (BackOffice Admin) ═════════════════════
    // ════════════════════════════════════════════════════════
    case 'admin_rendezvous':
        adminOnly();
        if ($action==='create')            { $rdvCtrl->adminCreate(); }
        elseif ($action==='store')         { $rdvCtrl->adminStore(); }
        elseif ($action==='edit' && $id)   { $rdvCtrl->adminEdit($id); }
        elseif ($action==='update' && $id) { $rdvCtrl->adminUpdate($id); }
        elseif ($action==='delete' && $id) { $rdvCtrl->adminDelete($id); }
        elseif ($action==='show' && $id)   { $rdvCtrl->adminShow($id); }
        else                               { $rdvCtrl->adminIndex(); }
        break;

    // ════════════════════════════════════════════════════════
    // ════ DISPONIBILITES (FrontOffice Médecin) ═══════════════
    // ════════════════════════════════════════════════════════
    case 'medecin_disponibilites':
    case 'disponibilites':
        medecinOnly();
        if ($action==='store')            { $dispoCtrl->medecinStore(); }
        elseif ($action==='toggle' && $id){ $dispoCtrl->medecinToggle($id); }
        elseif ($action==='delete' && $id){ $dispoCtrl->medecinDelete($id); }
        else                              { $dispoCtrl->medecinMesDisponibilites(); }
        break;

    // ════════════════════════════════════════════════════════
    // ════ DISPONIBILITES (BackOffice Admin) ══════════════════
    // ════════════════════════════════════════════════════════
    case 'admin_disponibilite':
        adminOnly();
        if ($action==='create')            { $dispoCtrl->adminCreate(); }
        elseif ($action==='store')         { $dispoCtrl->adminStore(); }
        elseif ($action==='edit' && $id)   { $dispoCtrl->adminEdit($id); }
        elseif ($action==='update' && $id) { $dispoCtrl->adminUpdate($id); }
        elseif ($action==='toggle' && $id) { $dispoCtrl->adminToggle($id); }
        elseif ($action==='delete' && $id) { $dispoCtrl->adminDelete($id); }
        else                               { $dispoCtrl->adminIndex(); }
        break;

    // ════════════════════════════════════════════════════════
    // ════ ORDONNANCES (FrontOffice Patient) ══════════════════
    // ════════════════════════════════════════════════════════
    case 'mes_ordonnances':
    case 'mes_ordonnances_new':
        patientOnly();
        $ordCtrl->patientMesOrdonnances();
        break;

    // ════════════════════════════════════════════════════════
    // ════ ORDONNANCES (FrontOffice Médecin) ══════════════════
    // ════════════════════════════════════════════════════════
    case 'medecin_ordonnances':
        medecinOnly();
        if ($action==='create')  { $ordCtrl->medecinCreate(); }
        elseif ($action==='store'){ $ordCtrl->medecinStore(); }
        else                     { $ordCtrl->medecinMesOrdonnances(); }
        break;

    // ════════════════════════════════════════════════════════
    // ════ ORDONNANCES (BackOffice Admin) ═════════════════════
    // ════════════════════════════════════════════════════════
    case 'admin_ordonnance':
        adminOnly();
        if ($action==='create')           { $ordCtrl->adminCreate(); }
        elseif ($action==='store')        { $ordCtrl->adminStore(); }
        elseif ($action==='show' && $id)  { $ordCtrl->adminShow($id); }
        elseif ($action==='delete' && $id){ $ordCtrl->adminDelete($id); }
        else                              { $ordCtrl->adminIndex(); }
        break;

    case 'ordonnances':
        adminOnly();
        $ordCtrl->adminIndex();
        break;

    // ─── Dashboard Admin ───────────────────────────────────
    case 'dashboard': adminOnly(); $adminCtrl->dashboard(); break;

    // ─── Gestion utilisateurs ──────────────────────────────
    case 'users':
        adminOnly();
        if ($action==='create')           { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->createUser():$adminCtrl->showCreateUser(); }
        elseif ($action==='edit'&&$id)    { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->updateUser($id):$adminCtrl->editUser($id); }
        elseif ($action==='delete'&&$id)  { $adminCtrl->deleteUser($id); }
        elseif ($action==='toggle'&&$id)  { $adminCtrl->toggleStatus($id); }
        elseif ($action==='show'&&$id)    { $adminCtrl->showUser($id); }
        else                              { $adminCtrl->listUsers(); }
        break;

    // ─── Gestion patients ──────────────────────────────────
    case 'patients':
        adminOnly();
        if ($action==='add')             { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->addPatient():$adminCtrl->showAddPatient(); }
        elseif ($action==='edit'&&$id)   { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->updatePatient($id):$adminCtrl->editPatient($id); }
        elseif ($action==='delete'&&$id) { $adminCtrl->deletePatient($id); }
        elseif ($action==='show'&&$id)   { $adminCtrl->showPatient($id); }
        else                             { $adminCtrl->listPatients(); }
        break;

    // ─── Gestion médecins ──────────────────────────────────
    case 'medecins_admin':
        adminOnly();
        if ($action==='add')              { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->addMedecin():$adminCtrl->showAddMedecin(); }
        elseif ($action==='edit'&&$id)    { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->updateMedecin($id):$adminCtrl->editMedecin($id); }
        elseif ($action==='delete'&&$id)  { $adminCtrl->deleteMedecin($id); }
        elseif ($action==='show'&&$id)    { $adminCtrl->showMedecin($id); }
        elseif ($action==='validate'&&$id){ $adminCtrl->validateMedecin($id); }
        elseif ($action==='approve'&&$id) { $adminCtrl->approveMedecin($id); }
        elseif ($action==='reject'&&$id)  { $adminCtrl->rejectMedecin($id); }
        else                              { $adminCtrl->listMedecins(); }
        break;

    case 'rendez_vous_admin':
        adminOnly();
        ($action==='delete'&&$id)?$adminCtrl->deleteRendezVous($id):$adminCtrl->listRendezVous();
        break;

    // ─── Gestion articles admin ────────────────────────────
    case 'articles_admin':
        adminOnly();
        if ($action==='create')          { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->createArticle():$adminCtrl->showCreateArticle(); }
        elseif ($action==='edit'&&$id)   { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->updateArticle($id):$adminCtrl->editArticle($id); }
        elseif ($action==='delete'&&$id) { $adminCtrl->deleteArticle($id); }
        else                             { $adminCtrl->listArticles(); }
        break;

    // ─── Gestion produits admin ────────────────────────────
    case 'produits_admin':
        adminOnly();
        if ($action==='create')          { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->createProduit():$adminCtrl->showCreateProduit(); }
        elseif ($action==='edit'&&$id)   { $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->updateProduit($id):$adminCtrl->editProduit($id); }
        elseif ($action==='delete'&&$id) { $adminCtrl->deleteProduit($id); }
        else                             { $adminCtrl->listProduits(); }
        break;

    // ─── Stats, logs, settings ─────────────────────────────
    case 'stats':    adminOnly(); $adminCtrl->stats(); break;
    case 'logs':     adminOnly(); $action==='export'?$adminCtrl->exportLogs():$adminCtrl->logs(); break;
    case 'settings': adminOnly(); $_SERVER['REQUEST_METHOD']==='POST'?$adminCtrl->updateSettings():$adminCtrl->settings(); break;

    // ─── Blog BackOffice ───────────────────────────────────
    case 'blog': adminOnly(); $articleCtrl->index(); break;

    // ════════════════════════════════════════════════════════
    // ════ API ═══════════════════════════════════════════════
    // ════════════════════════════════════════════════════════
    case 'api_slots':
        $rdvCtrl->apiGetSlots();
        break;

    case 'api_ordonnance':
        $ordCtrl->apiGetOrdonnance();
        break;

    case 'download_ordonnance':
        $ordCtrl->apiDownloadPDF();
        break;

    case 'api_article':
        $rawBody  = file_get_contents('php://input');
        $bodyData = json_decode($rawBody, true) ?? [];
        $method   = strtoupper($_SERVER['REQUEST_METHOD']);
        if ($method==='POST' && !empty($bodyData['_method'])) {
            $method = strtoupper($bodyData['_method']);
        }
        if ($method==='GET' && isset($_GET['list']))  { $articleCtrl->list(); }
        elseif ($method==='GET' && $id)               { $articleCtrl->show($id); }
        elseif ($method==='POST')                     { requireLogin(); $articleCtrl->store(); }
        elseif ($method==='PUT' && $id)               { requireLogin(); $articleCtrl->update($id); }
        elseif ($method==='DELETE' && $id)            { requireLogin(); $articleCtrl->destroy($id); }
        else {
            while (ob_get_level()>0) ob_end_clean();
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false,'error'=>'Méthode non autorisée']);
        }
        break;

    case 'api_reply':
        $rawBody   = file_get_contents('php://input');
        $bodyData  = json_decode($rawBody, true) ?? [];
        $method    = strtoupper($_SERVER['REQUEST_METHOD']);
        if ($method==='POST' && !empty($bodyData['_method'])) {
            $method = strtoupper($bodyData['_method']);
        }
        $articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
        if ($method==='GET' && isset($_GET['all']))   { requireLogin(); $replyCtrl->all(); }
        elseif ($method==='GET' && $id)               { requireLogin(); $replyCtrl->show($id); }
        elseif ($method==='GET' && $articleId)        { $replyCtrl->index($articleId); }
        elseif ($method==='POST')                     { requireLogin(); $replyCtrl->store(); }
        elseif ($method==='PUT' && $id)               { requireLogin(); $replyCtrl->update($id); }
        elseif ($method==='DELETE' && $id)            { requireLogin(); $replyCtrl->destroy($id); }
        else {
            while (ob_get_level()>0) ob_end_clean();
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false,'error'=>'Méthode non autorisée']);
        }
        break;

    case 'api':
        requireLogin();
        header('Content-Type: application/json');
        switch ($action) {
            case 'get_disponibilites': $rendezVousCtrl?$rendezVousCtrl->getDisponibilitesJson($id):http_response_code(501); break;
            case 'check_email':        $auth->checkEmail(); break;
            case 'stats':              adminOnly(); $adminCtrl->apiStats(); break;
            default:                   http_response_code(404); echo json_encode(['error'=>'Endpoint introuvable']);
        }
        break;

    // ─── 404 ────────────────────────────────────────────────
    default:
        if (DEBUG_MODE && !$isApiRoute) echo "<!-- DEBUG: 404 page='$page' -->\n";
        http_response_code(404);
        $front->page404();
        break;
}

if (DEBUG_MODE && !$isApiRoute) echo "<!-- DEBUG: Switch terminé page='$page' -->\n";