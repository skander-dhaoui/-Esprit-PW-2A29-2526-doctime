<?php
if (class_exists('AuthController')) return;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/social_auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../models/FaceRecognition.php';

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

    public function requireAuth(): void {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = "Veuillez vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

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

    public function showLogin(): void {
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        if (!empty($_SESSION['error']) && empty($errors)) {
            $errors['__form'] = $_SESSION['error'];
        }
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

    public function generateCaptcha(): void {
        header('Content-Type: application/json');
        $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
        $captcha = "";
        for ($i = 0; $i < 6; $i++) {
            $captcha .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $_SESSION['captcha_code'] = $captcha;
        echo json_encode(['captcha' => $captcha]);
        exit;
    }

    public function getCaptcha(): void {
        header('Content-Type: application/json');
        $captcha = $_SESSION['captcha_code'] ?? null;
        if ($captcha) {
            echo json_encode(['captcha' => $captcha]);
        } else {
            echo json_encode(['error' => 'Pas de captcha en session']);
        }
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  LOGIN — SANS 2FA (connexion directe)
    // ═══════════════════════════════════════════════════════════
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $email           = trim($_POST['email']            ?? '');
        $password        = trim($_POST['password']         ?? '');
        $captchaResponse = trim($_POST['captcha_response'] ?? '');

        $loginErrors = [];

        if (empty($email)) {
            $loginErrors['email'] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loginErrors['email'] = 'Adresse email invalide.';
        }
        if (empty($password)) {
            $loginErrors['password'] = 'Le mot de passe est requis.';
        }
        if (empty($captchaResponse)) {
            $loginErrors['captcha_response'] = 'Le code de vérification est requis.';
        }

        if (!empty($loginErrors)) {
            $_SESSION['errors'] = $loginErrors;
            $_SESSION['old']    = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        // Valider le captcha
        if (empty($_SESSION['captcha_code'])) {
            $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
            $captcha = "";
            for ($i = 0; $i < 6; $i++) {
                $captcha .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $_SESSION['captcha_code'] = $captcha;
        }

        if (strtoupper($captchaResponse) !== $_SESSION['captcha_code']) {
            $_SESSION['errors'] = ['captcha_response' => 'Code de vérification incorrect.'];
            $_SESSION['old']    = ['email' => $email];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, nom, prenom, email, password, role, statut FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['errors'] = ['credentials' => 'Email ou mot de passe incorrect.'];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            if (!password_verify($password, $user['password'])) {
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

            // ── CONNEXION DIRECTE SANS 2FA ────────────────────
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['success']    = 'Connexion réussie !';

            // Vider le captcha
            unset($_SESSION['captcha_code']);

            // Redirection selon le rôle
            $redirects = [
                'admin'   => 'index.php?page=dashboard',
                'medecin' => 'index.php?page=accueil',
                'patient' => 'index.php?page=accueil',
            ];
            header('Location: ' . $this->getBaseUrl() . ($redirects[$user['role']] ?? 'index.php?page=accueil'));
            exit;

        } catch (\Exception $e) {
            error_log('Erreur login: ' . $e->getMessage());
            $_SESSION['errors'] = ['__form' => 'Erreur serveur. Veuillez réessayer.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['success'] = "Vous êtes déconnecté.";
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    public function showRegister(): void {
        $errors = $_SESSION['errors'] ?? [];
        $error  = $_SESSION['error']  ?? null;
        $old    = $_SESSION['old']    ?? null;
        unset($_SESSION['errors'], $_SESSION['error'], $_SESSION['old']);
        if ($error !== null && $error !== '' && empty($errors)) {
            $errors['__form'] = is_string($error) ? $error : '';
        }
        $viewPath     = __DIR__ . '/../views/frontoffice/register.php';
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

        $nom             = trim($_POST['nom']              ?? '');
        $prenom          = trim($_POST['prenom']           ?? '');
        $email           = trim($_POST['email']            ?? '');
        $telephone       = trim($_POST['telephone']        ?? '');
        $password        = trim($_POST['password']         ?? '');
        $passwordConfirm = trim($_POST['password_confirm'] ?? '');
        $role            = $_POST['role']                  ?? 'patient';
        $specialite      = trim($_POST['specialite']       ?? '');
        $numeroOrdre     = trim($_POST['numero_ordre']     ?? '');

        $errors = [];

        if ($nom === '')    $errors['nom']    = 'Le nom est requis.';
        if ($prenom === '') $errors['prenom'] = 'Le prénom est requis.';
        if ($email === '') {
            $errors['email'] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }
        if ($telephone === '') $errors['telephone'] = 'Le téléphone est requis.';
        if ($password === '') {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Au moins 8 caractères, une majuscule et un chiffre.';
        }
        if ($passwordConfirm === '') {
            $errors['password_confirm'] = 'Veuillez confirmer le mot de passe.';
        } elseif ($password !== '' && $password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }
        if (!in_array($role, ['patient', 'medecin'], true)) $role = 'patient';
        if ($role === 'medecin') {
            if ($specialite === '')  $errors['specialite']  = 'Veuillez sélectionner une spécialité.';
            if ($numeroOrdre === '') $errors['numero_ordre'] = "Le numéro d'ordre est requis.";
        }
        if (empty($_POST['terms'])) $errors['terms'] = "Vous devez accepter les conditions d'utilisation.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute([':email' => $email]);
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $_SESSION['errors'] = ['email' => 'Cet email est déjà utilisé.'];
                $_SESSION['old']    = $_POST;
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
                exit;
            }

            $statut = ($role === 'medecin') ? 'en_attente' : 'actif';
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

            if (!$userId) throw new Exception("Erreur lors de la création du compte.");

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

            try {
                $welcomeBody = "
                    <h1>Bienvenue sur DocTime !</h1>
                    <p>Bonjour <strong>" . htmlspecialchars($prenom . ' ' . $nom) . "</strong>,</p>
                    <p>Votre compte a été créé avec succès.</p>
                    <p><a href='" . $this->getBaseUrl() . "index.php?page=login' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Se connecter</a></p>
                ";
                MailConfig::send($email, $prenom . ' ' . $nom, 'Bienvenue sur DocTime !', $welcomeBody);
            } catch (\Throwable $mailErr) {
                error_log('Email bienvenue non envoyé : ' . $mailErr->getMessage());
            }

            $_SESSION['success'] = ($role === 'medecin')
                ? "Compte médecin créé. En attente de validation par un administrateur."
                : "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";

            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;

        } catch (\Exception $e) {
            error_log('Erreur register: ' . $e->getMessage());
            $_SESSION['errors'] = ['__form' => 'Erreur serveur. Veuillez réessayer.'];
            $_SESSION['old']     = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }
    }

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

        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, nom, prenom, email FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id")
               ->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);

            $resetLink = $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token;
            $resetBody = "
                <h1>Réinitialisation de votre mot de passe</h1>
                <p>Bonjour <strong>" . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</strong>,</p>
                <p><a href='" . $resetLink . "' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Réinitialiser mon mot de passe</a></p>
                <p>Ce lien expire dans 1 heure.</p>
            ";
            try {
                MailConfig::send($email, $user['prenom'] . ' ' . $user['nom'], 'Réinitialisation de votre mot de passe', $resetBody);
            } catch (\Throwable $e) {
                error_log('Email reset exception: ' . $e->getMessage());
            }
        }

        $_SESSION['success'] = "Si cet email existe, vous recevrez un lien de réinitialisation.";
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        exit;
    }

    public function showResetPassword($token = null): void {
        $error      = null;
        $validToken = false;
        if ($token) {
            $token = preg_replace('/[^a-f0-9]/', '', $token);
            $db    = Database::getInstance()->getConnection();
            $stmt  = $db->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_expires > NOW()");
            $stmt->execute([':token' => $token]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $validToken = true;
                $_SESSION['reset_token'] = $token;
            } else {
                $error = "Lien invalide ou expiré. Veuillez refaire une demande.";
            }
        }
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
        $token           = trim($_POST['token']            ?? '');
        $newPassword     = trim($_POST['password']         ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (!$token || !$newPassword || !$confirmPassword) {
            $_SESSION['error'] = "Données invalides.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }
        if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . urlencode($token));
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . urlencode($token));
            exit;
        }

        $db     = Database::getInstance()->getConnection();
        $stmt   = $db->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE reset_token = :token AND reset_expires > NOW()");
        $result = $stmt->execute([':password' => password_hash($newPassword, PASSWORD_DEFAULT), ':token' => $token]);

        if ($result && $stmt->rowCount() > 0) {
            unset($_SESSION['reset_token']);
            $_SESSION['success'] = "Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        } else {
            $_SESSION['error'] = "Lien invalide ou expiré.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        }
        exit;
    }

    // ── Reconnaissance faciale ────────────────────────────────
    public function faceLogin(): void {
        header('Content-Type: application/json');
        try {
            $imageData = $_POST['face_image'] ?? '';
            $role      = $_POST['role']       ?? 'patient';
            $email     = trim((string)($_POST['email'] ?? ''));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email invalide.']);
                exit;
            }
            if (empty($imageData)) {
                echo json_encode(['success' => false, 'message' => 'Aucune image reçue.']);
                exit;
            }

            $user = $this->faceModel->findUserByFace($imageData, $role, $email);
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Visage non reconnu.']);
                exit;
            }
            if ($user['statut'] !== 'actif') {
                echo json_encode(['success' => false, 'message' => 'Compte ' . $user['statut'] . '.']);
                exit;
            }

            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_name']  = trim($user['nom'] . ' ' . $user['prenom']);
            $_SESSION['user_email'] = $user['email'];

            $redirect = match($user['role']) {
                'admin'   => 'index.php?page=dashboard',
                'medecin' => 'index.php?page=accueil',
                default   => 'index.php?page=accueil'
            };

            echo json_encode(['success' => true, 'message' => 'Reconnaissance faciale réussie !', 'redirect' => $redirect, 'role' => $user['role']]);
            exit;
        } catch (Throwable $e) {
            error_log('Erreur faceLogin: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
            exit;
        }
    }

    public function deleteFace(): void {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        try {
            $db     = Database::getInstance()->getConnection();
            $userId = (int)$_SESSION['user_id'];
            $photoStmt = $db->prepare("SELECT face_photo FROM users WHERE id = :id LIMIT 1");
            $photoStmt->execute([':id' => $userId]);
            $facePhoto = $photoStmt->fetchColumn();
            if (is_string($facePhoto) && $facePhoto !== '') {
                $fullPath = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $facePhoto), '/');
                if (is_file($fullPath)) @unlink($fullPath);
            }
            $stmt   = $db->prepare("UPDATE users SET face_photo = NULL, face_descriptors = NULL WHERE id = :id");
            $result = $stmt->execute([':id' => $userId]);
            echo json_encode($result ? ['success' => true, 'message' => 'Visage supprimé.'] : ['success' => false, 'message' => 'Erreur.']);
        } catch (Exception $e) {
            error_log('Erreur deleteFace: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        exit;
    }

    public function registerFace(): void {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => "Veuillez vous connecter d'abord."]);
            exit;
        }
        $imageData = $_POST['face_image'] ?? '';
        if (empty($imageData)) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (is_array($data)) {
                $imageData = $data['image'] ?? $data['face_image'] ?? '';
            }
        }
        if (empty($imageData)) {
            echo json_encode(['success' => false, 'message' => 'Aucune image reçue.']);
            exit;
        }
        $result = $this->faceModel->saveFacePhoto($_SESSION['user_id'], $imageData);
        echo json_encode(['success' => $result, 'message' => $result ? 'Visage enregistré avec succès !' : 'Erreur lors de l\'enregistrement.']);
        exit;
    }

    public function renderRegisterFace(): void {
        $viewPath = __DIR__ . '/../views/frontoffice/register_face.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<div class="alert alert-info">Enregistrement facial non disponible.</div>';
        }
    }

    // ── Social Login ──────────────────────────────────────────
    public function startSocialLogin(string $provider): void {
        $this->ensureSocialAuthConfigLoaded();
        $provider = strtolower(trim($provider));
        $config   = SocialAuthConfig::get($provider);
        if ($config === null) {
            $_SESSION['error'] = 'Fournisseur non pris en charge.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        if (!SocialAuthConfig::isConfigured($provider)) {
            $_SESSION['error'] = 'La connexion ' . $config['label'] . ' n\'est pas encore configurée.';
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
        $this->ensureSocialAuthConfigLoaded();
        $provider = strtolower(trim($provider));
        $config   = SocialAuthConfig::get($provider);
        if ($config === null) {
            $_SESSION['error'] = 'Retour OAuth invalide.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        $stateKey      = 'oauth_state_' . $provider;
        $expectedState = $_SESSION[$stateKey] ?? '';
        $receivedState = trim((string)($_GET['state'] ?? ''));
        unset($_SESSION[$stateKey]);
        if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
            $_SESSION['error'] = 'Échec de vérification OAuth.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        if (!empty($_GET['error'])) {
            $_SESSION['error'] = 'Connexion annulée.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        $code = trim((string)($_GET['code'] ?? ''));
        if ($code === '') {
            $_SESSION['error'] = 'Code OAuth manquant.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        try {
            $tokenData = $this->exchangeSocialCodeForToken($provider, $code, $config);
            $profile   = $this->fetchSocialProfile($provider, $tokenData, $config);
            $user      = $this->findOrCreateSocialUser($provider, $profile);
            if (empty($user) || empty($user['id'])) throw new RuntimeException('Compte social introuvable.');
            if (($user['statut'] ?? 'actif') !== 'actif') {
                $_SESSION['error'] = 'Votre compte est ' . $user['statut'] . '.';
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }
            $this->startUserSession($user);
            $_SESSION['success'] = 'Connexion ' . $config['label'] . ' réussie.';
            header('Location: ' . $this->getBaseUrl() . $this->buildPostLoginRedirectPath($user));
            exit;
        } catch (\Throwable $e) {
            error_log('Erreur social login [' . $provider . ']: ' . $e->getMessage());
            $_SESSION['error'] = 'Impossible de finaliser la connexion.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

    public function checkEmail(): void {
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? $_GET['email'] ?? '');
        if (empty($email)) { echo json_encode(['exists' => false]); exit; }
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        echo json_encode(['exists' => (bool)$stmt->fetch(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ── 2FA désactivé — méthodes conservées pour compatibilité ─
    public function showVerifyTwoFactor(): void {
        // 2FA supprimé — redirection vers login
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    public function verifyTwoFactorCode(): void {
        // 2FA supprimé — redirection vers login
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    public function resendTwoFactorCode(): void {
        // 2FA supprimé — redirection vers login
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        exit;
    }

    // ── Helpers privés ────────────────────────────────────────
    private function ensureSocialAuthConfigLoaded(): void {
        if (!class_exists('SocialAuthConfig', false)) {
            require_once __DIR__ . '/../config/social_auth.php';
        }
        if (!class_exists('SocialAuthConfig', false)) {
            throw new RuntimeException('SocialAuthConfig introuvable.');
        }
    }

    private function getSocialCallbackUrl(string $provider): string {
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
        $headers = [];
        if ($provider === 'github') {
            $headers = ['Accept: application/json', 'User-Agent: DocTime'];
        }
        $response = $this->sendHttpRequest($config['token_url'], 'POST', $payload, $headers);
        if (empty($response['access_token'])) throw new RuntimeException('Access token non reçu.');
        return $response;
    }

    private function fetchSocialProfile(string $provider, array $tokenData, array $config): array {
        $accessToken = (string)$tokenData['access_token'];
        if ($provider === 'google') {
            return $this->sendHttpRequest($config['user_url'], 'GET', [], ['Authorization: Bearer ' . $accessToken]);
        }
        if ($provider === 'facebook') {
            return $this->sendHttpRequest($config['user_url'] . '&access_token=' . urlencode($accessToken), 'GET');
        }
        if ($provider === 'instagram') {
            return $this->sendHttpRequest($config['user_url'] . '&access_token=' . urlencode($accessToken), 'GET');
        }
        if ($provider === 'github') {
            $headers = ['Authorization: Bearer ' . $accessToken, 'Accept: application/vnd.github+json', 'User-Agent: DocTime'];
            $profile = $this->sendHttpRequest($config['user_url'], 'GET', [], $headers);
            $emails  = $this->sendHttpRequest($config['email_url'], 'GET', [], $headers);
            if (is_array($emails)) $profile['emails'] = $emails;
            return $profile;
        }
        throw new RuntimeException('Fournisseur social non supporté.');
    }

    private function findOrCreateSocialUser(string $provider, array $profile): array {
        $normalized = $this->normalizeSocialProfile($provider, $profile);
        $db = Database::getInstance()->getConnection();
        $providerMatch = null;
        if ($this->usersHasSocialColumns()) {
            $stmt = $db->prepare("SELECT * FROM users WHERE social_provider = :provider AND social_provider_id = :provider_id LIMIT 1");
            $stmt->execute([':provider' => $provider, ':provider_id' => $normalized['provider_id']]);
            $providerMatch = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if ($providerMatch) {
            $this->updateSocialUser((int)$providerMatch['id'], $provider, $normalized);
            return $this->userModel->findById((int)$providerMatch['id']) ?? $providerMatch;
        }
        $emailMatch = null;
        if ($normalized['email'] !== '') $emailMatch = $this->userModel->findByEmail($normalized['email']);
        if ($emailMatch) {
            $this->updateSocialUser((int)$emailMatch['id'], $provider, $normalized);
            return $this->userModel->findById((int)$emailMatch['id']) ?? $emailMatch;
        }
        return $this->createSocialUser($provider, $normalized);
    }

    private function normalizeSocialProfile(string $provider, array $profile): array {
        if ($provider === 'google') {
            return ['provider_id' => (string)($profile['sub'] ?? ''), 'email' => trim((string)($profile['email'] ?? '')), 'prenom' => trim((string)($profile['given_name'] ?? 'Utilisateur')), 'nom' => trim((string)($profile['family_name'] ?? 'Google')), 'avatar' => trim((string)($profile['picture'] ?? ''))];
        }
        if ($provider === 'facebook') {
            $picture = !empty($profile['picture']['data']['url']) ? (string)$profile['picture']['data']['url'] : '';
            return ['provider_id' => (string)($profile['id'] ?? ''), 'email' => trim((string)($profile['email'] ?? '')), 'prenom' => trim((string)($profile['first_name'] ?? 'Utilisateur')), 'nom' => trim((string)($profile['last_name'] ?? 'Facebook')), 'avatar' => $picture];
        }
        if ($provider === 'instagram') {
            $username = trim((string)($profile['username'] ?? 'instagram_user'));
            return ['provider_id' => (string)($profile['id'] ?? ''), 'email' => 'instagram_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '@social.local', 'prenom' => $username, 'nom' => 'Instagram', 'avatar' => ''];
        }
        if ($provider === 'github') {
            $email = trim((string)($profile['email'] ?? ''));
            if ($email === '' && !empty($profile['emails']) && is_array($profile['emails'])) {
                foreach ($profile['emails'] as $emailItem) {
                    if (!is_array($emailItem) || empty($emailItem['email'])) continue;
                    if (!empty($emailItem['primary']) || !empty($emailItem['verified'])) { $email = trim((string)$emailItem['email']); break; }
                    if ($email === '') $email = trim((string)$emailItem['email']);
                }
            }
            $fullName  = trim((string)($profile['name'] ?? ''));
            $firstName = 'Utilisateur'; $lastName = 'GitHub';
            if ($fullName !== '') {
                $nameParts = preg_split('/\s+/', $fullName) ?: [];
                $firstName = trim((string)($nameParts[0] ?? 'Utilisateur'));
                $lastName  = trim((string)implode(' ', array_slice($nameParts, 1))) ?: 'GitHub';
            } elseif (!empty($profile['login'])) {
                $firstName = trim((string)$profile['login']);
            }
            if ($email === '' && !empty($profile['login'])) {
                $email = 'github_' . preg_replace('/[^a-zA-Z0-9_]/', '', (string)$profile['login']) . '@social.local';
            }
            return ['provider_id' => (string)($profile['id'] ?? ''), 'email' => $email, 'prenom' => $firstName, 'nom' => $lastName, 'avatar' => trim((string)($profile['avatar_url'] ?? ''))];
        }
        throw new RuntimeException('Profil social non supporté.');
    }

    private function createSocialUser(string $provider, array $normalized): array {
        if ($normalized['provider_id'] === '') throw new RuntimeException('Identifiant social manquant.');
        $db = Database::getInstance()->getConnection();
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $hasSocialColumns = $this->usersHasSocialColumns();
        $columns = 'nom, prenom, email, telephone, password, role, statut, avatar, created_at';
        $values  = ':nom, :prenom, :email, :telephone, :password, :role, :statut, :avatar, NOW()';
        $params  = [':nom' => $normalized['nom'], ':prenom' => $normalized['prenom'], ':email' => $normalized['email'], ':telephone' => null, ':password' => $password, ':role' => 'patient', ':statut' => 'actif', ':avatar' => null];
        if ($hasSocialColumns) {
            $columns .= ', social_provider, social_provider_id, social_avatar';
            $values  .= ', :social_provider, :social_provider_id, :social_avatar';
            $params[':social_provider']    = $provider;
            $params[':social_provider_id'] = $normalized['provider_id'];
            $params[':social_avatar']      = $normalized['avatar'] !== '' ? $normalized['avatar'] : null;
        }
        $stmt = $db->prepare("INSERT INTO users ($columns) VALUES ($values)");
        $stmt->execute($params);
        $userId = (int)$db->lastInsertId();
        if ($userId <= 0) throw new RuntimeException('Création du compte social impossible.');
        return $this->userModel->findById($userId) ?? [];
    }

    private function updateSocialUser(int $userId, string $provider, array $normalized): void {
        if ($this->usersHasSocialColumns()) {
            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE users SET social_provider=:provider, social_provider_id=:provider_id, social_avatar=:social_avatar, derniere_connexion=:derniere_connexion WHERE id=:id")
               ->execute([':provider' => $provider, ':provider_id' => $normalized['provider_id'], ':social_avatar' => $normalized['avatar'] !== '' ? $normalized['avatar'] : null, ':derniere_connexion' => date('Y-m-d H:i:s'), ':id' => $userId]);
            return;
        }
        $this->userModel->update($userId, ['derniere_connexion' => date('Y-m-d H:i:s')]);
    }

    private function usersHasSocialColumns(): bool {
        static $hasColumns = null;
        if ($hasColumns !== null) return $hasColumns;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'social_provider'");
        $hasColumns = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        return $hasColumns;
    }

    private function sendHttpRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): array {
        $method = strtoupper($method);
        if (function_exists('curl_init')) {
            $ch = curl_init();
            if ($method === 'GET' && !empty($data)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
            }
            if ($method === 'POST' && !in_array('Content-Type: application/x-www-form-urlencoded', $headers, true)) {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
            curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $headers]);
            if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
            $raw = curl_exec($ch);
            if ($raw === false) { $error = curl_error($ch); curl_close($ch); throw new RuntimeException('Erreur réseau: ' . $error); }
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode >= 400) throw new RuntimeException('HTTP ' . $httpCode);
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) throw new RuntimeException('JSON invalide.');
            return $decoded;
        }
        if ($method === 'GET' && !empty($data)) $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
        $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $method === 'POST' ? http_build_query($data) : '', 'ignore_errors' => true, 'timeout' => 20]]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) throw new RuntimeException('Échec de la requête HTTP.');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('JSON invalide.');
        return $decoded;
    }

    private function startUserSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = trim(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? ''));
        $_SESSION['user_email'] = $user['email'] ?? '';
        $this->userModel->update((int)$user['id'], ['derniere_connexion' => date('Y-m-d H:i:s')]);
    }

    private function buildPostLoginRedirectPath(array $user): string {
        $redirect = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);
        if (is_string($redirect) && $redirect !== '' && strpos($redirect, 'login') === false && strpos($redirect, 'register') === false) {
            return ltrim($redirect, '/');
        }
        return match($user['role'] ?? 'patient') {
            'admin' => 'index.php?page=dashboard',
            default => 'index.php?page=accueil',
        };
    }

    private function getBaseUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $protocol . '://' . $host . rtrim($script, '/') . '/';
    }

    // ── Vues de secours ───────────────────────────────────────
    private function renderLoginFallback(?string $error): void { ?>
        <!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Connexion</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
        <body class="bg-light"><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-5">
        <div class="card shadow"><div class="card-header bg-primary text-white text-center"><h4>Valorys — Connexion</h4></div>
        <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" action="index.php?page=login">
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
        <hr><div class="text-center"><a href="index.php?page=register">Créer un compte</a> | <a href="index.php?page=forgot_password">Mot de passe oublié</a></div>
        </div></div></div></div></div></body></html>
    <?php }

    private function renderRegisterFallback(?string $error, ?array $old): void { ?>
        <!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Inscription</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
        <body class="bg-light"><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6">
        <div class="card shadow"><div class="card-header bg-success text-white text-center"><h4>Valorys — Inscription</h4></div>
        <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="POST" action="index.php?page=register">
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Prénom *</label><input type="text" name="prenom" class="form-control" required></div></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Mot de passe *</label><input type="password" name="password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Je suis</label><select name="role" class="form-select"><option value="patient">Patient</option><option value="medecin">Médecin</option></select></div>
            <button type="submit" class="btn btn-success w-100">S'inscrire</button>
        </form>
        <hr><div class="text-center"><a href="index.php?page=login">Déjà un compte ?</a></div>
        </div></div></div></div></div></body></html>
    <?php }

    private function renderForgotFallback(?string $error, ?string $success): void { ?>
        <!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Mot de passe oublié</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
        <body class="bg-light"><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-5">
        <div class="card shadow"><div class="card-header bg-warning text-dark text-center"><h4>Mot de passe oublié</h4></div>
        <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="POST" action="index.php?page=forgot_password">
            <div class="mb-3"><label class="form-label">Votre email</label><input type="email" name="email" class="form-control" required></div>
            <button type="submit" class="btn btn-warning w-100">Envoyer le lien</button>
        </form>
        <hr><div class="text-center"><a href="index.php?page=login">Retour à la connexion</a></div>
        </div></div></div></div></div></body></html>
    <?php }

    private function renderResetFallback(?string $error, bool $validToken): void { ?>
        <!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Réinitialisation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
        <body class="bg-light"><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-5">
        <div class="card shadow"><div class="card-header bg-primary text-white text-center"><h4>Réinitialisation</h4></div>
        <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($validToken): ?>
        <form method="POST" action="index.php?page=reset_password">
            <div class="mb-3"><label class="form-label">Nouveau mot de passe *</label><input type="password" name="password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Confirmer *</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100">Réinitialiser</button>
        </form>
        <?php else: ?><a href="index.php?page=forgot_password" class="btn btn-primary w-100">Nouvelle demande</a><?php endif; ?>
        </div></div></div></div></div></body></html>
    <?php }
}
?>