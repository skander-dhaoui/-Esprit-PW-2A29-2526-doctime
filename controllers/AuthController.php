<?php
if (class_exists('AuthController')) return;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/social_auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../models/FaceRecognition.php';

use App\Models\User;
use App\Models\FaceRecognition;

class AuthController {

    private User $userModel;
    private FaceRecognition $faceModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new User();
        $this->faceModel = new FaceRecognition();
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  VÃ©rifier si connectÃ©
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function requireAuth(): void {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = "Veuillez vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  VÃ©rifier le rÃ´le
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // ─────────────────────────────────────────
    //  Vérifier le rôle
    // ─────────────────────────────────────────
    public function requireRole(string|array $role): void {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = "Veuillez vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $userRole = $_SESSION['user_role'] ?? '';
        $allowedRoles = is_array($role) ? $role : [$role];

        if (!in_array($userRole, $allowedRoles)) {
            $_SESSION['error'] = "Accès non autorisé.";
            if ($userRole === 'admin') {
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=dashboard');
            } else {
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=accueil');
            }
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher le formulaire de connexion
    // ─────────────────────────────────────────
    public function showLogin(): void {
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        // Support ancien format
        if (!empty($_SESSION['error']) && empty($errors)) {
            $errors['__form'] = $_SESSION['error'];
        }

        // Générer un captcha pour cette session si absent
        // NE PAS régénérer si déjà présent - garder le même captcha pour la session actuelle
        if (empty($_SESSION['captcha_code'])) {
            $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
            $captcha = "";
            for ($i = 0; $i < 6; $i++) {
                $captcha .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $_SESSION['captcha_code'] = $captcha;
        }

        $viewPath = __DIR__ . '/../views/frontoffice/login.php';
        $viewPathHtml = __DIR__ . '/../views/frontoffice/login.html';
        if (file_exists($viewPath)) {
            require $viewPath;
        } elseif (file_exists($viewPathHtml)) {
            require $viewPathHtml;
        } else {
            $errorMsg = $errors['__form'] ?? null;
            $this->renderLoginFallback($errorMsg);
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  GÃ©nÃ©rer un nouveau captcha (AJAX)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function generateCaptcha(): void {
        header('Content-Type: application/json');
        
        // GÃ©nÃ©rer un nouveau code de 6 caractÃ¨res
        $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
        $captcha = "";
        for ($i = 0; $i < 6; $i++) {
            $captcha .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Stocker dans la session
        $_SESSION['captcha_code'] = $captcha;
        
        echo json_encode(['captcha' => $captcha]);
        exit;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  RÃ©cupÃ©rer le captcha actuel (AJAX)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getCaptcha(): void {
        header('Content-Type: application/json');
        
        // Retourner le captcha actuel SANS le rÃ©gÃ©nÃ©rer
        $captcha = $_SESSION['captcha_code'] ?? null;
        
        if ($captcha) {
            echo json_encode(['captcha' => $captcha]);
        } else {
            echo json_encode(['error' => 'Pas de captcha en session']);
        }
        exit;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Traiter la connexion
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $captchaResponse = trim($_POST['captcha_response'] ?? '');
        
        // DEBUG
        error_log('LOGIN ATTEMPT - Email: ' . $email . ' | CaptchaResponse empty: ' . (empty($captchaResponse) ? 'YES' : 'NO'));
        error_log('SESSION captcha_code: ' . ($_SESSION['captcha_code'] ?? 'EMPTY'));
        error_log('POST data: ' . json_encode($_POST));
        
        $loginErrors = [];

        if (empty($email)) {
            $loginErrors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loginErrors['email'] = 'Adresse email invalide.';
        }
        if (empty($password)) {
            $loginErrors['password'] = 'Le mot de passe est requis.';
        }
        if (empty($captchaResponse)) {
            $loginErrors['captcha_response'] = 'Le code de vÃ©rification est requis.';
        }

        if (!empty($loginErrors)) {
            error_log('Validation errors: ' . json_encode($loginErrors));
            $_SESSION['errors'] = $loginErrors;
            $_SESSION['old']    = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        
        // Valider le captcha cÃ´tÃ© serveur
        // Si pas de captcha en session, le gÃ©nÃ©rer (premiÃ¨re visite)
        if (empty($_SESSION['captcha_code'])) {
            $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
            $captcha = "";
            for ($i = 0; $i < 6; $i++) {
                $captcha .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $_SESSION['captcha_code'] = $captcha;
            error_log('CAPTCHA GENERATED (was empty): ' . $captcha);
        }
        
        if (strtoupper($captchaResponse) !== $_SESSION['captcha_code']) {
            error_log('CAPTCHA FAIL - Session: ' . ($_SESSION['captcha_code'] ?? 'EMPTY') . ' | ReÃ§u: ' . strtoupper($captchaResponse) . ' | Match: ' . (strtoupper($captchaResponse) === $_SESSION['captcha_code'] ? 'YES' : 'NO'));
            $_SESSION['errors'] = ['captcha_response' => 'Code de verification incorrect.'];
            $_SESSION['old']    = ['email' => $email];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        unset($_SESSION['captcha_code']);

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, nom, prenom, email, password, role, statut FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                error_log('USER NOT FOUND: ' . $email);
                $_SESSION['errors'] = ['credentials' => 'Email ou mot de passe incorrect.'];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            if (!password_verify($password, $user['password'])) {
                error_log('PASSWORD WRONG for: ' . $email);
                $_SESSION['errors'] = ['credentials' => 'Email ou mot de passe incorrect.'];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            if ($user['statut'] !== 'actif') {
                $_SESSION['errors'] = ['compte' => 'Votre compte est ' . $user['statut'] . '. Contactez l\'administrateur.'];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            $redirect = $this->buildPostLoginRedirect($user);
            if (!$this->startEmailTwoFactorChallenge($user, $redirect)) {
                $_SESSION['errors'] = ['__form' => 'Impossible d\'envoyer le code 2FA par email. Verifiez la configuration email.'];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            $_SESSION['success'] = 'Un code de verification a ete envoye par email.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;

        } catch (\Exception $e) {
            error_log('Erreur login: ' . $e->getMessage());
            $_SESSION['errors'] = ['__form' => 'Erreur serveur. Veuillez rÃ©essayer.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  DÃ©connexion
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function logout(): void {
        // Destroy session
        session_unset();
        session_destroy();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['success'] = "Vous êtes déconnecté.";
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login&logout=1');
        exit;
    }

    // ─────────────────────────────────────────
    //  Inscription
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function showRegister(): void {
        $errors = $_SESSION['errors'] ?? [];
        $error  = $_SESSION['error']  ?? null;
        $old    = $_SESSION['old']    ?? null;
        unset($_SESSION['errors'], $_SESSION['error'], $_SESSION['old']);

        if ($error !== null && $error !== '' && empty($errors)) {
            $errors['__form'] = is_string($error) ? $error : '';
        }

        $viewPath = __DIR__ . '/../views/frontoffice/register.php';
        $viewPathHtml = __DIR__ . '/../views/frontoffice/register.html';

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } elseif (file_exists($viewPathHtml)) {
            require_once $viewPathHtml;
        } else {
            $fallbackMsg = !empty($errors) ? implode(' ', $errors) : null;
            $this->renderRegisterFallback($fallbackMsg, $old);
        }
    }

public function register(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
        exit;
    }

    $nom             = trim($_POST['nom']               ?? '');
    $prenom          = trim($_POST['prenom']            ?? '');
    $email           = trim($_POST['email']             ?? '');
    $telephone       = trim($_POST['telephone']         ?? '');
    $password        = trim($_POST['password']          ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');
    $role            = $_POST['role']                  ?? 'patient';

    $specialite     = trim($_POST['specialite']      ?? '');
    $numeroOrdre    = trim($_POST['numero_ordre']    ?? '');

    $errors = [];

    if ($nom === '') {
        $errors['nom'] = 'Le nom est requis.';
    }
    if ($prenom === '') {
        $errors['prenom'] = 'Le prÃ©nom est requis.';
    }
    if ($email === '') {
        $errors['email'] = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Adresse email invalide.';
    }
    if ($telephone === '') {
        $errors['telephone'] = 'Le tÃ©lÃ©phone est requis.';
    }
    if ($password === '') {
        $errors['password'] = 'Le mot de passe est requis.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Au moins 8 caractÃ¨res, une majuscule et un chiffre.';
    }
    if ($passwordConfirm === '') {
        $errors['password_confirm'] = 'Veuillez confirmer le mot de passe.';
    } elseif ($password !== '' && $password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
    }

    if (!in_array($role, ['patient', 'medecin'], true)) {
        $role = 'patient';
    }
    if ($role === 'medecin') {
        if ($specialite === '') {
            $errors['specialite'] = 'Veuillez sÃ©lectionner une spÃ©cialitÃ©.';
        }
        if ($numeroOrdre === '') {
            $errors['numero_ordre'] = 'Le numÃ©ro d\'ordre est requis.';
        }
    }

    if (empty($_POST['terms'])) {
        $errors['terms'] = "Vous devez accepter les conditions d'utilisation.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old']    = $_POST;
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
        exit;
    }

    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if email already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['errors'] = ['email' => 'Cet email est dÃ©jÃ  utilisÃ©.'];
            $_SESSION['old']    = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }

        $statut = ($role === 'medecin') ? 'en_attente' : 'actif';

        // Create user
        $createStmt = $db->prepare("
            INSERT INTO users (nom, prenom, email, telephone, password, role, statut, created_at)
            VALUES (:nom, :prenom, :email, :telephone, :password, :role, :statut, NOW())
        ");
        $createStmt->execute([
            ':nom'       => $nom,
            ':prenom'    => $prenom,
            ':email'     => $email,
            ':telephone' => $telephone,
            ':password'  => password_hash($password, PASSWORD_DEFAULT),
            ':role'      => $role,
            ':statut'    => $statut,
        ]);
        $userId = $db->lastInsertId();

        if (!$userId) {
            throw new Exception("Erreur lors de la crÃ©ation du compte.");
        }

        // Create medecin entry if applicable
        if ($role === 'medecin') {
            $medecinStmt = $db->prepare("
                INSERT INTO medecins (user_id, specialite, numero_ordre, adresse_cabinet, created_at)
                VALUES (:user_id, :specialite, :numero_ordre, :adresse_cabinet, NOW())
            ");
            $medecinStmt->execute([
                ':user_id'         => $userId,
                ':specialite'      => $specialite,
                ':numero_ordre'    => $numeroOrdre,
                ':adresse_cabinet' => trim($_POST['adresse_cabinet'] ?? ''),
            ]);
        }

        // Compte crÃ©Ã© â€” envoyer l'email de bienvenue (non bloquant)
        try {
            $welcomeBody = "
                <h1>Bienvenue sur DocTime !</h1>
                <p>Bonjour <strong>" . htmlspecialchars($prenom) . " " . htmlspecialchars($nom) . "</strong>,</p>
                <p>Votre compte a Ã©tÃ© crÃ©Ã© avec succÃ¨s sur DocTime.</p>
                <p>Vous pouvez dÃ¨s maintenant :</p>
                <ul>
                    <li>Prendre des rendez-vous en ligne</li>
                    <li>Consulter vos ordonnances</li>
                    <li>Discuter avec vos mÃ©decins</li>
                </ul>
                <p style='margin-top: 30px;'>
                    <a href='" . $this->getBaseUrl() . "index.php?page=login' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Se connecter</a>
                </p>
                <hr>
                <p style='font-size:12px;color:#666;'>Â© 2024 DocTime - Plateforme mÃ©dicale</p>
            ";
            MailConfig::send($email, $prenom . ' ' . $nom, 'Bienvenue sur DocTime !', $welcomeBody);
        } catch (\Throwable $mailErr) {
            // L'email Ã©choue silencieusement â€” l'inscription reste valide
            error_log('Email bienvenue non envoyÃ© : ' . $mailErr->getMessage());
        }

        if ($role === 'medecin') {
            $_SESSION['success'] = "Compte mÃ©decin crÃ©Ã© avec succÃ¨s. En attente de validation par un administrateur.";
        } else {
            $_SESSION['success'] = "Compte crÃ©Ã© avec succÃ¨s ! Vous pouvez maintenant vous connecter.";
        }

        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;

    } catch (\Exception $e) {
        error_log('Erreur register: ' . $e->getMessage());
        $_SESSION['errors'] = ['__form' => 'Erreur serveur (' . $e->getMessage() . '). Veuillez rÃ©essayer.'];
        $_SESSION['old']     = $_POST;
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
        exit;
    }
}

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Mot de passe oubliÃ© (avec envoi d'email)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function showForgotPassword(): void {
        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $viewPath = __DIR__ . '/../views/frontoffice/forgot_password.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderForgotFallback($error, $success);
        }
    }

    public function forgotPassword(): void {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }

        error_log('FORGOT_PASSWORD REQUEST for: ' . $email);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, nom, prenom, email FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            error_log('USER FOUND for forgot password: ' . $email . ' (id=' . $user['id'] . ')');
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $updateStmt = $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
            $updateStmt->execute([
                ':token' => $token,
                ':expires' => $expires,
                ':id' => $user['id']
            ]);
            
            error_log('Reset token generated and stored');
            
            $resetLink = $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token;
            
            $resetBody = "
                <h1>RÃ©initialisation de votre mot de passe</h1>
                <p>Bonjour <strong>" . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</strong>,</p>
                <p>Vous avez demandÃ© Ã  rÃ©initialiser votre mot de passe. Cliquez sur le bouton ci-dessous :</p>
                <p style='margin: 30px 0;'>
                    <a href='" . $resetLink . "' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>RÃ©initialiser mon mot de passe</a>
                </p>
                <p>Si vous n'Ãªtes pas Ã  l'origine de cette demande, ignorez simplement cet email.</p>
                <div style='background:#f8f9fa;padding:10px;border-left:4px solid #ffc107;margin-top:20px;'>
                    <strong>âš ï¸ Ce lien expirera dans 1 heure.</strong>
                </div>
                <hr>
                <p style='font-size:12px;color:#666;'>Â© 2024 DocTime - Plateforme mÃ©dicale</p>
            ";
            
            try {
                error_log('Attempting to send reset email to: ' . $email);
                $sendResult = MailConfig::send($email, $user['prenom'] . ' ' . $user['nom'], 'RÃ©initialisation de votre mot de passe - DocTime', $resetBody);
                if ($sendResult) {
                    error_log('âœ… EMAIL RESET ENVOYÃ‰ AVEC SUCCÃˆS');
                } else {
                    error_log('âŒ EMAIL RESET Ã‰CHOUÃ‰ - Check logs above for details');
                }
            } catch (\Throwable $mailErr) {
                error_log('ðŸ’¥ EMAIL RESET EXCEPTION: ' . $mailErr->getMessage());
                error_log('Exception file: ' . $mailErr->getFile() . ':' . $mailErr->getLine());
            }
        } else {
            error_log('USER NOT FOUND for forgot password: ' . $email);
        }
        
        $_SESSION['success'] = "Si cet email existe, vous recevrez un lien de rÃ©initialisation.";
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        exit;
    }

    public function showResetPassword($token = null): void {
        $error = null;
        $validToken = false;
        
        error_log('=== RESET PASSWORD LINK ===');
        error_log('Token reÃ§u: ' . ($token ? htmlspecialchars($token) : 'NULL'));
        
        if ($token) {
            $originalToken = $token;
            $token = preg_replace('/[^a-f0-9]/', '', $token);
            
            error_log('Token aprÃ¨s regex: ' . htmlspecialchars($token));
            error_log('Token avant regex: ' . htmlspecialchars($originalToken));
            
            $db = Database::getInstance()->getConnection();
            
            // PremiÃ¨re vÃ©rification: le token existe en base?
            $checkStmt = $db->prepare("SELECT id, email, reset_expires FROM users WHERE reset_token = :token");
            $checkStmt->execute([':token' => $token]);
            $checkUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($checkUser) {
                error_log('âœ… Token trouvÃ© en base pour email: ' . $checkUser['email']);
                error_log('   Expires: ' . $checkUser['reset_expires']);
                error_log('   Now: ' . date('Y-m-d H:i:s'));
            } else {
                error_log('âŒ Token NOT trouvÃ© en base');
            }
            
            // DeuxiÃ¨me vÃ©rification: avec vÃ©rification de date
            $stmt = $db->prepare(
                "SELECT id FROM users WHERE reset_token = :token AND reset_expires > NOW()"
            );
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $validToken = true;
                $_SESSION['reset_token'] = $token;
                error_log('âœ… TOKEN VALIDE ET NON EXPIRÃ‰');
            } else {
                $error = "Lien invalide ou expirÃ©. Veuillez refaire une demande.";
                error_log('âŒ TOKEN INVALIDE OU EXPIRÃ‰');
            }
        }
        
        error_log('=== END RESET PASSWORD LINK ===');
        
        $viewPath = __DIR__ . '/../views/frontoffice/reset_password.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderResetFallback($error, $validToken);
        }
    }

    public function resetPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        
        $token = trim($_POST['token'] ?? '');
        $newPassword = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        if (!$token || strlen($newPassword) === 0 || strlen($confirmPassword) === 0) {
            $_SESSION['error'] = "DonnÃ©es invalides.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }
        
        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractÃ¨res.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . urlencode($token));
            exit;
        }
        
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins une majuscule.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . urlencode($token));
            exit;
        }
        
        if (!preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins un chiffre.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . urlencode($token));
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . urlencode($token));
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL 
             WHERE reset_token = :token AND reset_expires > NOW()"
        );
        $result = $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':token' => $token
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            unset($_SESSION['reset_token']);
            $_SESSION['success'] = "Mot de passe rÃ©initialisÃ© avec succÃ¨s. Vous pouvez maintenant vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        } else {
            $_SESSION['error'] = "Lien invalide ou expirÃ©.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        }
        exit;
    }

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
//  Reconnaissance faciale
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
public function faceLogin(): void {
    header('Content-Type: application/json');
    try {
        error_log("[FACE] faceLogin called at " . date('Y-m-d H:i:s'));
    
        $imageData = $_POST['face_image'] ?? '';
        $role = $_POST['role'] ?? 'patient';
        $email = trim((string) ($_POST['email'] ?? ''));
        $liveDescriptor = $_POST['face_descriptor'] ?? '';
        
        error_log("[FACE] faceLogin params: email=$email, role=$role, has_image=" . (!empty($imageData) ? 'YES' : 'NO') . ", has_descriptor=" . (!empty($liveDescriptor) ? 'YES' : 'NO'));

        if (empty($imageData) && empty($liveDescriptor)) {
            error_log("[FACE] No facial data received");
            echo json_encode(['success' => false, 'message' => 'Aucune donnée faciale reçue']);
            exit;
        }

        $db = \Database::getInstance()->getConnection();
        $user = null;

        $liveVector = !empty($liveDescriptor) ? json_decode($liveDescriptor, true) : null;
        
        if ($liveVector && is_array($liveVector)) {
            error_log("[FACE] Live descriptor decoded, vector dimensions: " . count($liveVector));
        } else {
            error_log("[FACE] FAILED to decode live descriptor or not an array");
        }

        if ($liveVector && is_array($liveVector)) {
            $minDist = 0.55; // Distance threshold
            error_log("[FACE] Starting face comparison with threshold=$minDist");

            if (!empty($email)) {
                // Mode 1:1 — Specific user comparison
                error_log("[FACE] Mode 1:1 - searching for email=$email, role=$role");
                $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1");
                $stmt->execute([':email' => $email, ':role' => $role]);
                $u = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($u) {
                    error_log("[FACE] User found: " . $u['email'] . ", has_descriptors=" . (!empty($u['face_descriptors']) ? 'YES' : 'NO'));
                    if (!empty($u['face_descriptors'])) {
                        $savedVector = json_decode($u['face_descriptors'], true);
                        if ($savedVector && is_array($savedVector)) {
                            error_log("[FACE] Saved vector dimensions: " . count($savedVector));
                            
                            // Ensure both vectors have 128 dimensions
                            $liveCount = count($liveVector);
                            $savedCount = count($savedVector);
                            if ($liveCount !== $savedCount) {
                                error_log("[FACE] Vector size mismatch: live=$liveCount, saved=$savedCount");
                            }
                            
                            $dist = 0;
                            $maxDim = min(128, min(count($liveVector), count($savedVector)));
                            for ($i = 0; $i < $maxDim; $i++) {
                                $diff = (float)$liveVector[$i] - (float)$savedVector[$i];
                                $dist += $diff * $diff;
                            }
                            $dist = sqrt($dist);
                            error_log("[FACE] Distance calculation: $dist (threshold=$minDist, match=" . ($dist < $minDist ? 'YES' : 'NO') . ")");
                            
                            if ($dist < $minDist) {
                                $user = $u;
                                error_log("[FACE] MATCH FOUND at distance $dist");
                            } else {
                                error_log("[FACE] Distance too high: $dist >= $minDist");
                            }
                        } else {
                            error_log("[FACE] Failed to decode saved vector");
                        }
                    } else {
                        error_log("[FACE] User has no face_descriptors in database");
                    }
                } else {
                    error_log("[FACE] User not found with email=$email and role=$role");
                }
            } else {
                // Mode 1:N — Automatic search
                error_log("[FACE] Mode 1:N - searching all users with face data");
                $stmt = $db->query("SELECT id, email, role, statut, nom, prenom, face_descriptors FROM users WHERE face_descriptors IS NOT NULL");
                $allUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("[FACE] Found " . count($allUsers) . " users with face data");
                $bestMatch = null;

                foreach ($allUsers as $u) {
                    $savedVector = json_decode($u['face_descriptors'], true);
                    if (!$savedVector || !is_array($savedVector)) continue;
                    
                    $dist = 0;
                    $maxDim = min(128, min(count($liveVector), count($savedVector)));
                    for ($i = 0; $i < $maxDim; $i++) {
                        $diff = (float)$liveVector[$i] - (float)$savedVector[$i];
                        $dist += $diff * $diff;
                    }
                    $dist = sqrt($dist);
                    
                    error_log("[FACE] Comparing with " . $u['email'] . ": distance=$dist");

                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $bestMatch = $u;
                        error_log("[FACE] New best match: " . $u['email'] . " at distance=$dist");
                    }
                }
                $user = $bestMatch;
                if ($user) {
                    error_log("[FACE] MATCH FOUND: " . $user['email'] . " at distance=$minDist");
                }
            }
        }

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Visage non reconnu ou utilisateur introuvable.']);
        exit;
    }
    
    // Vérifier si le compte est actif
    $statut = $user['statut'] ?? 'en_attente';
    error_log("[FACE] User account status: $statut");
    
    if (!in_array($statut, ['actif', 'ACTIF', 'active', 'ACTIVE'])) {
        $statusMessage = match(strtolower($statut)) {
            'inactif' => 'désactivé',
            'en_attente' => 'en attente d\'activation',
            'suspend' => 'suspendu',
            default => $statut
        };
        echo json_encode(['success' => false, 'message' => 'Votre compte est ' . $statusMessage . '. Contactez l\'administrateur.']);
        exit;
    }
    
    // Démarrer la session
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = trim(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? ''));
    $_SESSION['user_email'] = $user['email'];
    
    error_log("[FACE] Session started for user: " . $user['email'] . " (ID: " . $user['id'] . ")");
    
    // Déterminer la redirection selon le rôle
    $redirect = match($user['role']) {
        'admin'   => 'index.php?page=dashboard',
        'medecin' => 'index.php?page=accueil',
        default   => 'index.php?page=accueil'
    };
    
    echo json_encode([
        'success' => true,
        'message' => 'Reconnaissance faciale réussie !',
        'redirect' => $redirect,
        'role' => $user['role']
    ]);
    exit;
    } catch (Throwable $e) {
        error_log('Erreur faceLogin: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur lors de la reconnaissance faciale.'
        ]);
        exit;
    }
}
/**
 * Supprimer le visage enregistrÃ© de l'utilisateur
 */
public function deleteFace(): void {
    $this->requireAuth();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'MÃ©thode non autorisÃ©e']);
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $userId = (int)$_SESSION['user_id'];

        $photoStmt = $db->prepare("SELECT face_photo FROM users WHERE id = :id LIMIT 1");
        $photoStmt->execute([':id' => $userId]);
        $facePhoto = $photoStmt->fetchColumn();
        if (is_string($facePhoto) && $facePhoto !== '') {
            $fullPath = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $facePhoto), '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
        
        $stmt = $db->prepare(
            "UPDATE users
             SET face_photo = NULL, face_descriptors = NULL
             WHERE id = :id"
        );
        $result = $stmt->execute([':id' => $userId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Visage supprimÃ© avec succÃ¨s']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    } catch (Exception $e) {
        error_log('Erreur deleteFace: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
    exit;
}
public function registerFace(): void {
    header('Content-Type: application/json');
    error_log("[FACE] registerFace called at " . date('Y-m-d H:i:s'));
    
    if (!isset($_SESSION['user_id'])) {
        error_log("[FACE] registerFace - No session user_id");
        echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter d\'abord.']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    error_log("[FACE] registerFace userId=$userId");
    error_log("[FACE] registerFace payload keys=" . implode(", ", array_keys($data ?? [])));
    
    if (isset($data['descriptor']) && is_array($data['descriptor'])) {
        error_log("[FACE] descriptor array found, elements=" . count($data['descriptor']));
    }

    $imageData = $_POST['face_image'] ?? $data['image'] ?? $data['face_image'] ?? '';
    $descriptor = null;
    
    if (is_array($data) && isset($data['descriptor']) && is_array($data['descriptor'])) {
        $descriptor = json_encode($data['descriptor']);
        $descriptorArray = json_decode($descriptor, true);
        error_log("[FACE] descriptor encoded, array_count=" . count($descriptorArray ?? []) . ", json_length=" . strlen($descriptor));
    } else {
        error_log("[FACE] WARNING: NO descriptor found in JSON payload");
        error_log("[FACE] data type: " . gettype($data) . ", has descriptor key: " . (isset($data['descriptor']) ? 'YES' : 'NO'));
    }

    if (empty($imageData)) {
        error_log("[FACE] NO image data received");
        echo json_encode(['success' => false, 'message' => 'Aucune image reçue']);
        exit;
    }
    
    error_log("[FACE] Image data size: " . strlen($imageData) . " bytes");
    
    $uploadDir = __DIR__ . '/../uploads/faces/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = 'face_' . $userId . '_' . time() . '.jpg';
    $filepath = $uploadDir . $filename;
    $relativePath = 'uploads/faces/' . $filename;

    $cleanData = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,', ' '], ['', '', '+'], $imageData);
    $imageBinary = base64_decode($cleanData);
    
    error_log("[FACE] Image binary size after decode: " . strlen($imageBinary) . " bytes");

    $result = false;
    if (file_put_contents($filepath, $imageBinary)) {
        error_log("[FACE] Face photo saved to $filepath");
        
        $db = \Database::getInstance()->getConnection();
        
        // Log what we're about to save
        error_log("[FACE] About to save: descriptor=" . ($descriptor ? substr($descriptor, 0, 100) . "..." : "NULL"));
        
        $stmt = $db->prepare("UPDATE users SET face_photo = :photo, face_descriptors = :descriptors WHERE id = :id");
        $result = $stmt->execute([
            ':photo' => $relativePath, 
            ':descriptors' => $descriptor,
            ':id' => $userId
        ]);
        
        error_log("[FACE] DB update " . ($result ? "SUCCESS" : "FAILED"));
        
        // Verify what was actually saved
        if ($result) {
            $verifyStmt = $db->prepare("SELECT face_descriptors FROM users WHERE id = :id LIMIT 1");
            $verifyStmt->execute([':id' => $userId]);
            $saved = $verifyStmt->fetchColumn();
            error_log("[FACE] Verification - descriptors in DB: " . ($saved ? substr($saved, 0, 100) . "..." : "NULL"));
        }
    } else {
        error_log("[FACE] Failed to write file: $filepath");
    }
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Visage enregistré avec succès !' : 'Erreur lors de l\'enregistrement'
    ]);
    exit;
}

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  VÃ©rifier email (AJAX)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
public function startSocialLogin(string $provider): void {
    $this->ensureSocialAuthConfigLoaded();
    $provider = strtolower(trim($provider));
    $config = SocialAuthConfig::get($provider);

    if ($config === null) {
        $_SESSION['error'] = 'Fournisseur de connexion sociale non pris en charge.';
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    if (!SocialAuthConfig::isConfigured($provider)) {
        $_SESSION['error'] = 'La connexion ' . $config['label'] . ' n\'est pas encore configurÃ©e sur ce serveur.';
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state_' . $provider] = $state;

    $params = [
        'client_id'     => $config['client_id'],
        'redirect_uri'  => $this->getSocialCallbackUrl($provider),
        'response_type' => 'code',
        'scope'         => $config['scope'],
        'state'         => $state,
    ];

    header('Location: ' . $config['auth_url'] . '?' . http_build_query($params));
    exit;
}

public function handleSocialCallback(string $provider): void {
    error_log('=== SOCIAL CALLBACK START ===');
    error_log('Provider: ' . $provider);
    error_log('GET params: ' . json_encode($_GET));
    error_log('Session state: ' . ($_SESSION['oauth_state_' . $provider] ?? 'NOT SET'));
    
    $this->ensureSocialAuthConfigLoaded();
    $provider = strtolower(trim($provider));
    $config = SocialAuthConfig::get($provider);

    if ($config === null) {
        $_SESSION['error'] = 'Retour OAuth invalide.';
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    $stateKey = 'oauth_state_' . $provider;
    $expectedState = $_SESSION[$stateKey] ?? '';
    $receivedState = trim((string) ($_GET['state'] ?? ''));
    unset($_SESSION[$stateKey]);

    error_log('State validation: expected=' . $expectedState . ' | received=' . $receivedState);

    if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
        $_SESSION['error'] = 'Échec de vérification de la connexion ' . $config['label'] . '.';
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    if (!empty($_GET['error'])) {
        $_SESSION['error'] = 'Connexion ' . $config['label'] . ' annulée ou refusée.';
        error_log('OAuth User canceled: ' . $_GET['error']);
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    $code = trim((string) ($_GET['code'] ?? ''));
    if ($code === '') {
        $_SESSION['error'] = 'Code de retour ' . $config['label'] . ' manquant.';
        error_log('Authorization code is empty');
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    try {
        error_log('Attempting to exchange code for token...');
        $tokenData = $this->exchangeSocialCodeForToken($provider, $code, $config);
        error_log('Successfully got token data');
        
        $profile = $this->fetchSocialProfile($provider, $tokenData, $config);
        error_log('Successfully fetched profile: ' . json_encode($profile));
        
        $user = $this->findOrCreateSocialUser($provider, $profile);
        error_log('User found/created: id=' . ($user['id'] ?? 'null'));

        if (empty($user) || empty($user['id'])) {
            throw new RuntimeException('Compte social introuvable ou non créé.');
        }

        if (($user['statut'] ?? 'actif') !== 'actif') {
            $_SESSION['error'] = 'Votre compte est ' . $user['statut'] . '. Contactez l\'administrateur.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $this->startUserSession($user);

        $_SESSION['success'] = 'Connexion ' . $config['label'] . ' réussie.';
        error_log('=== SOCIAL CALLBACK SUCCESS ===');
        header('Location: ' . $this->getBaseUrl() . $this->buildPostLoginRedirectPath($user));
        exit;
    } catch (\Throwable $e) {
        error_log('❌ Erreur social login [' . $provider . ']: ' . $e->getMessage());
        error_log('Exception file: ' . $e->getFile() . ':' . $e->getLine());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $_SESSION['error'] = 'Impossible de finaliser la connexion ' . $config['label'] . '.';
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }
}

    public function checkEmail(): void {
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? $_GET['email'] ?? '');

        if (empty($email)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['exists' => (bool)$user]);
        exit;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Helper : URL de base
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    private function ensureSocialAuthConfigLoaded(): void {
        if (!class_exists('SocialAuthConfig', false)) {
            require_once __DIR__ . '/../config/social_auth.php';
        }

        if (!class_exists('SocialAuthConfig', false)) {
            throw new RuntimeException('La configuration SocialAuthConfig est introuvable.');
        }
    }

    private function getSocialCallbackUrl(string $provider): string {
        $this->ensureSocialAuthConfigLoaded();
        return $this->getBaseUrl() . 'index.php?page=social_callback&provider=' . urlencode($provider);
    }

    private function exchangeSocialCodeForToken(string $provider, string $code, array $config): array {
        $payload = [
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $this->getSocialCallbackUrl($provider),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ];

        error_log('=== OAUTH TOKEN EXCHANGE START ===');
        error_log('Provider: ' . $provider);
        error_log('Code: ' . substr($code, 0, 20) . '...');
        error_log('Callback URL: ' . $this->getSocialCallbackUrl($provider));
        error_log('Payload: ' . json_encode($payload));

        $headers = [];
        if ($provider === 'github') {
            $headers = [
                'Accept: application/json',
                'User-Agent: DocTime',
            ];
        }

        try {
            $response = $this->sendHttpRequest($config['token_url'], 'POST', $payload, $headers);
            error_log('Token response: ' . json_encode($response));
        } catch (RuntimeException $e) {
            error_log('HTTP Error during token exchange: ' . $e->getMessage());
            throw $e;
        }

        if (empty($response['access_token'])) {
            error_log('Access token missing from response. Full response: ' . json_encode($response));
            throw new RuntimeException('Access token non reçu. Response: ' . json_encode($response));
        }

        error_log('=== OAUTH TOKEN EXCHANGE SUCCESS ===');
        return $response;
    }

    private function fetchSocialProfile(string $provider, array $tokenData, array $config): array {
        $accessToken = (string) $tokenData['access_token'];

        if ($provider === 'google') {
            return $this->sendHttpRequest($config['user_url'], 'GET', [], [
                'Authorization: Bearer ' . $accessToken,
            ]);
        }

        if ($provider === 'facebook') {
            return $this->sendHttpRequest($config['user_url'] . '&access_token=' . urlencode($accessToken), 'GET');
        }

        if ($provider === 'instagram') {
            return $this->sendHttpRequest($config['user_url'] . '&access_token=' . urlencode($accessToken), 'GET');
        }

        if ($provider === 'github') {
            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/vnd.github+json',
                'User-Agent: DocTime',
            ];

            $profile = $this->sendHttpRequest($config['user_url'], 'GET', [], $headers);
            $emails = $this->sendHttpRequest($config['email_url'], 'GET', [], $headers);

            if (is_array($emails)) {
                $profile['emails'] = $emails;
            }

            return $profile;
        }

        throw new RuntimeException('Fournisseur social non supportÃ©.');
    }

    private function findOrCreateSocialUser(string $provider, array $profile): array {
        $normalized = $this->normalizeSocialProfile($provider, $profile);
        $db = Database::getInstance()->getConnection();

        $providerMatch = null;
        if ($this->usersHasSocialColumns()) {
            $providerStmt = $db->prepare(
                "SELECT * FROM users WHERE social_provider = :provider AND social_provider_id = :provider_id LIMIT 1"
            );
            $providerStmt->execute([
                ':provider'    => $provider,
                ':provider_id' => $normalized['provider_id'],
            ]);
            $providerMatch = $providerStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($providerMatch) {
            $this->updateSocialUser((int) $providerMatch['id'], $provider, $normalized);
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $providerMatch['id']]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: $providerMatch;
        }

        $emailMatch = null;
        if ($normalized['email'] !== '') {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $normalized['email']]);
            $emailMatch = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        if ($emailMatch) {
            $this->updateSocialUser((int) $emailMatch['id'], $provider, $normalized);
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $emailMatch['id']]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: $emailMatch;
        }

        return $this->createSocialUser($provider, $normalized);
    }

    private function normalizeSocialProfile(string $provider, array $profile): array {
        if ($provider === 'google') {
            return [
                'provider_id' => (string) ($profile['sub'] ?? ''),
                'email'       => trim((string) ($profile['email'] ?? '')),
                'prenom'      => trim((string) ($profile['given_name'] ?? 'Utilisateur')),
                'nom'         => trim((string) ($profile['family_name'] ?? 'Google')),
                'avatar'      => trim((string) ($profile['picture'] ?? '')),
            ];
        }

        if ($provider === 'facebook') {
            $picture = '';
            if (!empty($profile['picture']['data']['url'])) {
                $picture = (string) $profile['picture']['data']['url'];
            }

            return [
                'provider_id' => (string) ($profile['id'] ?? ''),
                'email'       => trim((string) ($profile['email'] ?? '')),
                'prenom'      => trim((string) ($profile['first_name'] ?? 'Utilisateur')),
                'nom'         => trim((string) ($profile['last_name'] ?? 'Facebook')),
                'avatar'      => $picture,
            ];
        }

        if ($provider === 'instagram') {
            $username = trim((string) ($profile['username'] ?? 'instagram_user'));

            return [
                'provider_id' => (string) ($profile['id'] ?? ''),
                'email'       => 'instagram_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '@social.local',
                'prenom'      => $username,
                'nom'         => 'Instagram',
                'avatar'      => '',
            ];
        }

        if ($provider === 'github') {
            $email = trim((string) ($profile['email'] ?? ''));
            if ($email === '' && !empty($profile['emails']) && is_array($profile['emails'])) {
                foreach ($profile['emails'] as $emailItem) {
                    if (!is_array($emailItem) || empty($emailItem['email'])) {
                        continue;
                    }

                    if (!empty($emailItem['primary']) || !empty($emailItem['verified'])) {
                        $email = trim((string) $emailItem['email']);
                        break;
                    }

                    if ($email === '') {
                        $email = trim((string) $emailItem['email']);
                    }
                }
            }

            $fullName = trim((string) ($profile['name'] ?? ''));
            $firstName = 'Utilisateur';
            $lastName = 'GitHub';

            if ($fullName !== '') {
                $nameParts = preg_split('/\s+/', $fullName) ?: [];
                $firstName = trim((string) ($nameParts[0] ?? 'Utilisateur'));
                $lastName = trim((string) implode(' ', array_slice($nameParts, 1))) ?: 'GitHub';
            } elseif (!empty($profile['login'])) {
                $firstName = trim((string) $profile['login']);
            }

            if ($email === '' && !empty($profile['login'])) {
                $email = 'github_' . preg_replace('/[^a-zA-Z0-9_]/', '', (string) $profile['login']) . '@social.local';
            }

            return [
                'provider_id' => (string) ($profile['id'] ?? ''),
                'email'       => $email,
                'prenom'      => $firstName,
                'nom'         => $lastName,
                'avatar'      => trim((string) ($profile['avatar_url'] ?? '')),
            ];
        }

        throw new RuntimeException('Profil social non supportÃ©.');
    }

    private function createSocialUser(string $provider, array $normalized): array {
        if ($normalized['provider_id'] === '') {
            throw new RuntimeException('Identifiant social manquant.');
        }

        $db = Database::getInstance()->getConnection();
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $hasSocialColumns = $this->usersHasSocialColumns();

        $columns = 'nom, prenom, email, telephone, password, role, statut, avatar, created_at';
        $values  = ':nom, :prenom, :email, :telephone, :password, :role, :statut, :avatar, NOW()';
        $params = [
            ':nom'       => $normalized['nom'],
            ':prenom'    => $normalized['prenom'],
            ':email'     => $normalized['email'],
            ':telephone' => null,
            ':password'  => $password,
            ':role'      => 'patient',
            ':statut'    => 'actif',
            ':avatar'    => null,
        ];

        if ($hasSocialColumns) {
            $columns .= ', social_provider, social_provider_id, social_avatar';
            $values  .= ', :social_provider, :social_provider_id, :social_avatar';
            $params[':social_provider'] = $provider;
            $params[':social_provider_id'] = $normalized['provider_id'];
            $params[':social_avatar'] = $normalized['avatar'] !== '' ? $normalized['avatar'] : null;
        }

        $stmt = $db->prepare("INSERT INTO users ($columns) VALUES ($values)");
        $stmt->execute($params);

        $userId = (int) $db->lastInsertId();
        if ($userId <= 0) {
            throw new RuntimeException('CrÃ©ation du compte social impossible.');
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    private function updateSocialUser(int $userId, string $provider, array $normalized): void {
        $lastConnection = date('Y-m-d H:i:s');

        if ($this->usersHasSocialColumns()) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "UPDATE users
                 SET social_provider = :provider,
                     social_provider_id = :provider_id,
                     social_avatar = :social_avatar,
                     derniere_connexion = :derniere_connexion
                 WHERE id = :id"
            );
            $stmt->execute([
                ':provider'           => $provider,
                ':provider_id'        => $normalized['provider_id'],
                ':social_avatar'      => $normalized['avatar'] !== '' ? $normalized['avatar'] : null,
                ':derniere_connexion' => $lastConnection,
                ':id'                 => $userId,
            ]);
            return;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET derniere_connexion = :derniere_connexion WHERE id = :id");
        $stmt->execute([
            ':derniere_connexion' => $lastConnection,
            ':id'                 => $userId,
        ]);
    }

    private function usersHasSocialColumns(): bool {
        static $hasColumns = null;

        if ($hasColumns !== null) {
            return $hasColumns;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'social_provider'");
        $hasColumns = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

        return $hasColumns;
    }

    private function sendHttpRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): array {
        $method = strtoupper($method);

        if (function_exists('curl_init')) {
            $ch = curl_init();

            if ($method === 'GET' && !empty($data)) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . http_build_query($data);
            }

            if ($method === 'POST' && !in_array('Content-Type: application/x-www-form-urlencoded', $headers, true)) {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_HTTPHEADER     => $headers,
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

            $raw = curl_exec($ch);
            if ($raw === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Erreur réseau: ' . $error);
            }

            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            error_log('HTTP Response [' . $httpCode . ']: ' . substr($raw, 0, 500));

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                if ($httpCode >= 400) {
                    throw new RuntimeException('Réponse HTTP ' . $httpCode . ' reçue. Réponse: ' . substr($raw, 0, 200));
                }
                throw new RuntimeException('Réponse JSON invalide: ' . substr($raw, 0, 200));
            }

            if ($httpCode >= 400) {
                $errorMsg = $decoded['error'] ?? 'Unknown error';
                $errorDesc = $decoded['error_description'] ?? '';
                throw new RuntimeException('OAuth Error [' . $httpCode . ']: ' . $errorMsg . '. ' . $errorDesc);
            }

            return $decoded;
        }

        if ($method === 'GET' && !empty($data)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($data);
        }

        if ($method === 'POST' && !in_array('Content-Type: application/x-www-form-urlencoded', $headers, true)) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $context = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'content'       => $method === 'POST' ? http_build_query($data) : '',
                'ignore_errors' => true,
                'timeout'       => 20,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new RuntimeException('Échec de la requête HTTP (stream).');
        }

        error_log('Stream Response: ' . substr($raw, 0, 500));

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Réponse JSON invalide (stream): ' . substr($raw, 0, 200));
        }

        if (!empty($decoded['error'])) {
            $errorMsg = $decoded['error'] ?? 'Unknown error';
            $errorDesc = $decoded['error_description'] ?? '';
            throw new RuntimeException('OAuth Error: ' . $errorMsg . '. ' . $errorDesc);
        }

        return $decoded;
    }

    private function startUserSession(array $user): void {
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = trim(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? ''));
        $_SESSION['user_email'] = $user['email'] ?? '';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET derniere_connexion = :date WHERE id = :id");
        $stmt->execute([
            ':date' => date('Y-m-d H:i:s'),
            ':id'   => $user['id'],
        ]);
    }

    private function buildPostLoginRedirectPath(array $user): string {
        $redirect = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);

        if (is_string($redirect) && $redirect !== '' && strpos($redirect, 'login') === false && strpos($redirect, 'register') === false) {
            return ltrim($redirect, '/');
        }

        return match ($user['role'] ?? 'patient') {
            'admin'   => 'index.php?page=dashboard',
            default   => 'index.php?page=accueil',
        };
    }

    private function getBaseUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base     = rtrim($script, '/') . '/';
        return $protocol . '://' . $host . $base;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Vues de secours
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    private function renderLoginFallback(?string $error): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Connexion - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h4>Valorys â€” Connexion</h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <form method="POST" action="index.php?page=login">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mot de passe</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a href="index.php?page=register">CrÃ©er un compte</a> |
                                <a href="index.php?page=forgot_password">Mot de passe oubliÃ©</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    private function renderRegisterFallback(?string $error, ?array $old): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Inscription - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white text-center">
                            <h4>Valorys â€” Inscription</h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            <form method="POST" action="index.php?page=register">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" name="nom" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">PrÃ©nom *</label>
                                        <input type="text" name="prenom" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mot de passe *</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Je suis</label>
                                    <select name="role" class="form-select">
                                        <option value="patient">Patient</option>
                                        <option value="medecin">MÃ©decin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">S'inscrire</button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a href="index.php?page=login">DÃ©jÃ  un compte ? Se connecter</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    private function renderForgotFallback(?string $error, ?string $success): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Mot de passe oubliÃ© - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark text-center">
                            <h4>Mot de passe oubliÃ©</h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>
                            <form method="POST" action="index.php?page=forgot_password">
                                <div class="mb-3">
                                    <label class="form-label">Votre email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100">Envoyer le lien</button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a href="index.php?page=login">Retour Ã  la connexion</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }
    
    private function renderResetFallback(?string $error, bool $validToken): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>RÃ©initialisation - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h4>RÃ©initialisation du mot de passe</h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <?php if ($validToken): ?>
                                <form method="POST" action="index.php?page=reset_password">
                                    <div class="mb-3">
                                        <label class="form-label">Nouveau mot de passe *</label>
                                        <input type="password" name="password" class="form-control" required>
                                        <small class="text-muted">Minimum 8 caractÃ¨res, 1 majuscule, 1 chiffre</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">RÃ©initialiser</button>
                                </form>
                            <?php else: ?>
                                <a href="index.php?page=forgot_password" class="btn btn-primary w-100">Faire une nouvelle demande</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    public function showVerifyTwoFactor(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        $viewPath = __DIR__ . '/../views/frontoffice/verify_2fa.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Vue verify_2fa.php manquante.";
        }
    }

    public function verifyTwoFactorCode(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $code = trim((string)($_POST['verification_code'] ?? $_POST['code'] ?? ''));
        $pending = $_SESSION['pending_2fa'];

        if (time() > $pending['expires_at']) {
            $_SESSION['errors'] = ['__form' => 'Le code a expiré. Veuillez demander un nouveau code.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;
        }

        if ($code !== $pending['code']) {
            $_SESSION['errors'] = ['__form' => 'Code de vérification incorrect.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;
        }

        $user = $pending['user'];
        $redirect = $pending['redirect'];

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['success']   = 'Connexion réussie.';

        unset($_SESSION['pending_2fa']);

        header('Location: ' . $this->getBaseUrl() . ltrim($redirect, '/'));
        exit;
    }

    public function resendTwoFactorCode(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $pending = $_SESSION['pending_2fa'];
        if (!$this->startEmailTwoFactorChallenge($pending['user'], $pending['redirect'])) {
            $_SESSION['errors'] = ['__form' => 'Impossible de renvoyer le code.'];
        } else {
            $_SESSION['success'] = 'Un nouveau code a été envoyé.';
        }

        if (!empty($_SESSION['success'])) {
            $_SESSION['success'] = 'Un nouveau code a ete envoye par email.';
        }
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
        exit;
    }

    private function buildPostLoginRedirect(array $user): string {
        return match($user['role'] ?? '') {
            'admin'   => 'index.php?page=dashboard',
            'medecin' => 'index.php?page=accueil',
            default   => 'index.php?page=accueil'
        };
    }

    private function startEmailTwoFactorChallenge(array $user, string $redirect): bool {
        $code = (string) random_int(100000, 999999);
        $email = trim((string) ($user['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('2FA email impossible: adresse email utilisateur manquante ou invalide.');
            return false;
        }

        $emailBody = "
            <h1>Code de verification</h1>
            <p>Bonjour " . htmlspecialchars((string) ($user['prenom'] ?? '')) . ",</p>
            <p>Votre code de verification a 6 chiffres est : <strong>" . htmlspecialchars($code) . "</strong></p>
            <p>Il expirera dans 5 minutes.</p>
        ";

        try {
            error_log('2FA email: tentative envoi a ' . $email);
            $emailSuccess = MailConfig::send(
                $email,
                trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
                'Votre code de verification DocTime',
                $emailBody
            );
        } catch (\Throwable $e) {
            error_log('Erreur envoi email 2FA: ' . $e->getMessage());
            $emailSuccess = false;
        }

        if (!$emailSuccess) {
            return false;
        }

        $_SESSION['pending_2fa'] = [
            'code' => $code,
            'expires_at' => time() + 300,
            'redirect' => $redirect,
            'user' => $user,
            'phone' => '',
            'email' => $email,
            'masked_email' => $this->maskEmailAddress($email),
            'masked_phone' => $this->maskEmailAddress($email),
            'method' => 'Email'
        ];

        error_log('2FA email: code envoye a ' . $email);
        return true;
    }

    private function startTwoFactorChallenge(array $user, string $redirect): bool {
        $code = (string) random_int(100000, 999999);
        $phone = $this->normalizeWhatsappNumber((string) ($user['telephone'] ?? ''));
        
        $whatsappSuccess = false;
        if ($phone !== null) {
            $whatsappSuccess = $this->sendWhatsAppVerificationCode($phone, $code, (string) ($user['prenom'] ?? ''));
        } else {
            error_log('2FA WhatsApp impossible: numéro utilisateur manquant ou invalide. Tentative par email.');
        }

        $emailSuccess = false;
        if (!$whatsappSuccess) {
            $email = $user['email'] ?? '';
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailBody = "
                    <h1>Code de vérification</h1>
                    <p>Bonjour " . htmlspecialchars((string)($user['prenom'] ?? '')) . ",</p>
                    <p>Votre code de vérification à 6 chiffres est : <strong>" . $code . "</strong></p>
                    <p>Il expirera dans 5 minutes.</p>
                ";
                try {
                    $emailSuccess = MailConfig::send($email, ($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''), 'Votre code de vérification DocTime', $emailBody);
                } catch (\Throwable $e) {
                    error_log('Erreur envoi email 2FA: ' . $e->getMessage());
                }
            }
        }

        if (!$whatsappSuccess && !$emailSuccess) {
            return false;
        }

        $_SESSION['pending_2fa'] = [
            'code' => $code,
            'expires_at' => time() + 300,
            'redirect' => $redirect,
            'user' => $user,
            'phone' => $phone ?? '',
            'masked_phone' => $phone !== null ? $this->maskPhoneNumber($phone) : 'Email',
            'method' => $whatsappSuccess ? 'WhatsApp' : 'Email'
        ];

        return true;
    }

    private function sendWhatsAppVerificationCode(string $phone, string $code, string $firstName = ''): bool {
        $token = $this->getEnvValue('WHATSAPP_ACCESS_TOKEN');
        $phoneNumberId = $this->getEnvValue('WHATSAPP_PHONE_NUMBER_ID');

        if ($token === '' || $phoneNumberId === '') {
            error_log('WhatsApp 2FA non configuré: token ou phone number id manquant.');
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => trim(sprintf(
                    "Bonjour%s, votre code de vérification DocTime est : %s. Il expire dans 5 minutes.",
                    $firstName !== '' ? ' ' . $firstName : '',
                    $code
                )),
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init('https://graph.facebook.com/v25.0/' . rawurlencode($phoneNumberId) . '/messages');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);

                $raw = curl_exec($ch);
                if ($raw === false) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    throw new \RuntimeException($error);
                }

                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 400) {
                    throw new \RuntimeException('HTTP ' . $httpCode . ' - ' . $raw);
                }

                return true;
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'ignore_errors' => true,
                    'timeout' => 20,
                ],
            ]);

            $raw = @file_get_contents('https://graph.facebook.com/v25.0/' . rawurlencode($phoneNumberId) . '/messages', false, $context);
            if ($raw === false) {
                throw new \RuntimeException('Échec de l’envoi WhatsApp.');
            }

            return true;
        } catch (\Throwable $e) {
            error_log('Erreur WhatsApp 2FA: ' . $e->getMessage());
            return false;
        }
    }

    private function normalizeWhatsappNumber(string $phone): ?string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 8) return null;
        if (!str_starts_with($phone, '216') && strlen($phone) == 8) {
            $phone = '216' . $phone;
        }
        return $phone;
    }

    private function maskPhoneNumber(string $phone): string {
        $len = strlen($phone);
        if ($len <= 4) return $phone;
        return str_repeat('*', $len - 4) . substr($phone, -4);
    }

    private function maskEmailAddress(string $email): string {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return $email;
        }

        $visible = min(2, strlen($local));
        return substr($local, 0, $visible) . str_repeat('*', max(1, strlen($local) - $visible)) . '@' . $domain;
    }

    private function getEnvValue(string $key, string $default = ''): string {
        $value = getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return trim((string) $value);
    }
}
?>
