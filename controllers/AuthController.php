<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/social_auth.php';
require_once __DIR__ . '/../config/recaptcha.php';
require_once __DIR__ . '/../config/app_logger.php';
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

    // ─────────────────────────────────────────
    //  Vérifier si connecté
    // ─────────────────────────────────────────
    public function requireAuth(): void {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = "Veuillez vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

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
        $this->ensureRecaptchaConfig();
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        if (!empty($_SESSION['error']) && empty($errors)) {
            $errors['__form'] = $_SESSION['error'];
        }
        unset($_SESSION['error']);

        if (!RecaptchaConfig::isConfigured()) {
            if (empty($_SESSION['captcha_code'])) {
                $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
                $captcha = '';
                for ($i = 0; $i < 6; $i++) {
                    $captcha .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $_SESSION['captcha_code'] = $captcha;
            }
        }

        $recaptchaSiteKey = RecaptchaConfig::siteKey();
        $socialButtons = [];
        foreach (['google', 'github', 'facebook'] as $p) {
            if (SocialAuthConfig::isConfigured($p)) {
                $cfg = SocialAuthConfig::get($p);
                $socialButtons[$p] = $cfg['label'] ?? ucfirst($p);
            }
        }

        $viewPath = __DIR__ . '/../views/frontoffice/login.php';
        $viewPathHtml = __DIR__ . '/../views/frontoffice/login.html';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } elseif (file_exists($viewPathHtml)) {
            require_once $viewPathHtml;
        } else {
            $this->renderLoginFallback($errors['__form'] ?? null);
        }
    }

    // ─────────────────────────────────────────
    //  Traiter la connexion
    // ─────────────────────────────────────────
    public function login(): void {
        $this->ensureRecaptchaConfig();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';
        if (!is_string($password)) {
            $password = '';
        }
        $captchaResponse = trim($_POST['captcha_response'] ?? '');
        $recaptchaToken  = trim($_POST['g-recaptcha-response'] ?? '');

        $loginErrors = [];

        if ($email === '') {
            $loginErrors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loginErrors['email'] = 'Adresse email invalide.';
        }
        if ($password === '') {
            $loginErrors['password'] = 'Le mot de passe est requis.';
        }

        if (RecaptchaConfig::isConfigured()) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $check = RecaptchaConfig::verify($recaptchaToken, is_string($ip) ? $ip : '');
            if (!$check['success']) {
                $loginErrors['recaptcha'] = $check['error'] ?? 'reCAPTCHA invalide.';
            }
        } else {
            if ($captchaResponse === '') {
                $loginErrors['captcha_response'] = 'Saisissez le code de vérification.';
            }
        }

        if (!empty($loginErrors)) {
            if (!empty($loginErrors['recaptcha'])) {
                AppLogger::log('captcha', 'login_recaptcha_fail', []);
            }
            if (!empty($loginErrors['captcha_response'])) {
                AppLogger::log('captcha', 'login_session_captcha_fail', []);
            }
            $_SESSION['errors'] = $loginErrors;
            $_SESSION['old']    = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        if (!RecaptchaConfig::isConfigured()) {
            if (empty($_SESSION['captcha_code'])) {
                $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
                $captcha = '';
                for ($i = 0; $i < 6; $i++) {
                    $captcha .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $_SESSION['captcha_code'] = $captcha;
            }
            if (strtoupper($captchaResponse) !== strtoupper((string)($_SESSION['captcha_code'] ?? ''))) {
                AppLogger::log('captcha', 'login_session_captcha_mismatch', []);
                $_SESSION['errors'] = ['captcha_response' => 'Code de vérification incorrect.'];
                $_SESSION['old']    = $_POST;
                unset($_SESSION['captcha_code']);
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }
        }

        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                AppLogger::log('auth', 'login_unknown_email', []);
                $_SESSION['errors'] = [
                    'credentials' => 'Aucun compte avec cet email. Vérifiez l’adresse ou inscrivez-vous.',
                ];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            if (!$this->userModel->verifyPasswordAndMigrate($user, $password)) {
                AppLogger::log('auth', 'login_bad_password', ['user_id' => (int) ($user['id'] ?? 0)]);
                $msg = 'Mot de passe incorrect. Vous pouvez utiliser « Mot de passe oublié » pour en définir un nouveau.';
                $sp = trim((string) ($user['social_provider'] ?? ''));
                if ($sp !== '') {
                    $labels = ['google' => 'Google', 'github' => 'GitHub', 'facebook' => 'Facebook'];
                    $lbl = $labels[$sp] ?? ucfirst($sp);
                    $msg .= ' Ce compte est lié à ' . $lbl . ' : utilisez le bouton « Continuer avec » ou la réinitialisation du mot de passe.';
                }
                $_SESSION['errors'] = ['credentials' => $msg];
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

            unset($_SESSION['captcha_code']);

            $target = $this->resolvePostLoginTarget($user);

            if (!$this->startEmailTwoFactorChallenge($user, $target)) {
                AppLogger::log('mail', 'login_2fa_send_failed', ['user_id' => (int) ($user['id'] ?? 0)]);
                $_SESSION['errors'] = ['__form' => 'Impossible d\'envoyer le code de vérification par email. Vérifiez la configuration SMTP.'];
                $_SESSION['old']    = ['email' => $email];
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            $_SESSION['success'] = 'Un code à 6 chiffres a été envoyé à votre adresse email.';
            AppLogger::log('auth', 'login_2fa_challenge_sent', ['user_id' => (int) ($user['id'] ?? 0)]);
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;

        } catch (Exception $e) {
            error_log('Erreur login: ' . $e->getMessage());
            $_SESSION['errors'] = ['__form' => 'Erreur serveur. Veuillez réessayer.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Déconnexion
    // ─────────────────────────────────────────
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

    // ─────────────────────────────────────────
    //  Inscription
    // ─────────────────────────────────────────
    public function showRegister(): void {
        $this->ensureRecaptchaConfig();
        $errors = $_SESSION['errors'] ?? [];
        $error  = $_SESSION['error']  ?? null;
        $old    = $_SESSION['old']    ?? null;
        unset($_SESSION['errors'], $_SESSION['error'], $_SESSION['old']);

        if ($error !== null && $error !== '' && empty($errors)) {
            $errors['__form'] = is_string($error) ? $error : '';
        }

        $recaptchaSiteKey = RecaptchaConfig::siteKey();
        $socialButtons = [];
        foreach (['google', 'github', 'facebook'] as $p) {
            if (SocialAuthConfig::isConfigured($p)) {
                $cfg = SocialAuthConfig::get($p);
                $socialButtons[$p] = $cfg['label'] ?? ucfirst($p);
            }
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
        $this->ensureRecaptchaConfig();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }

        if (RecaptchaConfig::isConfigured()) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $tok = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
            $chk = RecaptchaConfig::verify($tok, is_string($ip) ? $ip : '');
            if (!$chk['success']) {
                AppLogger::log('captcha', 'register_recaptcha_fail', []);
                $_SESSION['errors'] = ['__form' => $chk['error'] ?? 'reCAPTCHA invalide.'];
                $_SESSION['old'] = $_POST;
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
                exit;
            }
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
            $errors['prenom'] = 'Le prénom est requis.';
        }
        if ($email === '') {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }
        if ($telephone === '') {
            $errors['telephone'] = 'Le téléphone est requis.';
        }
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

        if (!in_array($role, ['patient', 'medecin'], true)) {
            $role = 'patient';
        }
        if ($role === 'medecin') {
            if ($specialite === '') {
                $errors['specialite'] = 'Veuillez sélectionner une spécialité.';
            }
            if ($numeroOrdre === '') {
                $errors['numero_ordre'] = 'Le numéro d\'ordre est requis.';
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
            if ($this->userModel->findByEmail($email)) {
                $_SESSION['errors'] = ['email' => 'Cet email est déjà utilisé.'];
                $_SESSION['old']    = $_POST;
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
                exit;
            }

            $statut = ($role === 'medecin') ? 'en_attente' : 'actif';

            $userId = $this->userModel->create([
                'nom'       => $nom,
                'prenom'    => $prenom,
                'email'     => $email,
                'telephone' => $telephone,
                'password'  => password_hash($password, PASSWORD_DEFAULT),
                'role'      => $role,
                'statut'    => $statut,
            ]);

            if (!$userId) {
                throw new Exception("Erreur lors de la création du compte.");
            }

            if ($role === 'medecin') {
                $this->userModel->createMedecin([
                    'user_id'         => $userId,
                    'specialite'      => $specialite,
                    'numero_ordre'    => $numeroOrdre,
                    'adresse_cabinet' => trim($_POST['adresse_cabinet'] ?? ''),
                ]);
            }

            // Envoyer email de bienvenue
            $welcomeBody = "
                <h1>Bienvenue sur DocTime !</h1>
                <p>Bonjour <strong>" . htmlspecialchars($prenom) . " " . htmlspecialchars($nom) . "</strong>,</p>
                <p>Votre compte a été créé avec succès sur DocTime.</p>
                <p>Vous pouvez dès maintenant :</p>
                <ul>
                    <li>Prendre des rendez-vous en ligne</li>
                    <li>Consulter vos ordonnances</li>
                    <li>Discuter avec vos médecins</li>
                </ul>
                <p style='margin-top: 30px;'>
                    <a href='" . $this->getBaseUrl() . "index.php?page=login' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Se connecter</a>
                </p>
                <hr>
                <p style='font-size:12px;color:#666;'>© 2024 DocTime - Plateforme médicale</p>
            ";
            
            MailConfig::send($email, $prenom . ' ' . $nom, 'Bienvenue sur DocTime !', $welcomeBody);

            AppLogger::log('auth', 'register_success', ['user_id' => (int) $userId, 'role' => $role]);

            if ($role === 'medecin') {
                $_SESSION['success'] = "Compte créé. En attente de validation par un administrateur.";
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            } else {
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_role']  = $role;
                $_SESSION['user_name']  = $nom . ' ' . $prenom;
                $_SESSION['user_email'] = $email;
                $_SESSION['success']    = "Compte créé avec succès !";
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=accueil');
            }
            exit;

        } catch (Exception $e) {
            error_log('Erreur register: ' . $e->getMessage());
            $_SESSION['errors'] = ['__form' => 'Erreur serveur. Veuillez réessayer.'];
            $_SESSION['old']     = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mot de passe oublié (avec envoi d'email)
    // ─────────────────────────────────────────
    public function showForgotPassword(): void {
        $this->ensureRecaptchaConfig();
        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        if (!RecaptchaConfig::isConfigured()) {
            if (empty($_SESSION['forgot_captcha_code'])) {
                $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
                $captcha = '';
                for ($i = 0; $i < 6; $i++) {
                    $captcha .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $_SESSION['forgot_captcha_code'] = $captcha;
            }
        }

        $recaptchaSiteKey = RecaptchaConfig::siteKey();
        $useRecaptcha = $recaptchaSiteKey !== '';

        $viewPath = __DIR__ . '/../views/frontoffice/forgot_password.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderForgotFallback($error, $success);
        }
    }

    public function forgotPassword(): void {
        $this->ensureRecaptchaConfig();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }

        if (RecaptchaConfig::isConfigured()) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $tok = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
            $chk = RecaptchaConfig::verify($tok, is_string($ip) ? $ip : '');
            if (!$chk['success']) {
                AppLogger::log('captcha', 'forgot_password_recaptcha_fail', []);
                $_SESSION['error'] = $chk['error'] ?? 'reCAPTCHA invalide.';
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
                exit;
            }
        } else {
            $captchaResponse = trim((string) ($_POST['forgot_captcha_response'] ?? ''));
            if ($captchaResponse === '') {
                AppLogger::log('captcha', 'forgot_password_captcha_empty', []);
                $_SESSION['error'] = 'Saisissez le code de vérification anti-robot.';
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
                exit;
            }
            if (strtoupper($captchaResponse) !== strtoupper((string) ($_SESSION['forgot_captcha_code'] ?? ''))) {
                AppLogger::log('captcha', 'forgot_password_captcha_mismatch', []);
                unset($_SESSION['forgot_captcha_code']);
                $_SESSION['error'] = 'Code de vérification incorrect.';
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
                exit;
            }
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            AppLogger::log('auth', 'forgot_password_invalid_email', []);
            $_SESSION['error'] = "Email invalide.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            AppLogger::log('auth', 'forgot_password_unknown_email', []);
            unset($_SESSION['forgot_captcha_code']);
            $_SESSION['success'] = 'Si cet email est enregistré sur la plateforme, un message sera envoyé.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }

        if (!MailConfig::isConfigured()) {
            AppLogger::log('mail', 'forgot_password_mail_not_configured', []);
            $_SESSION['error'] = 'Envoi d’e-mails désactivé : renseignez MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD et MAIL_FROM dans le fichier .env à la racine du projet (pour Gmail : mot de passe d’application).';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->userModel->update($user['id'], [
            'reset_token'   => $token,
            'reset_expires' => $expires,
        ]);

        $resetLink = $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token;

        $resetBody = "
                <h1>Réinitialisation de votre mot de passe</h1>
                <p>Bonjour <strong>" . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</strong>,</p>
                <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous :</p>
                <p style='margin: 30px 0;'>
                    <a href='" . htmlspecialchars($resetLink) . "' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Réinitialiser mon mot de passe</a>
                </p>
                <p>Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br><small>" . htmlspecialchars($resetLink) . "</small></p>
                <p>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p>
                <div style='background:#f8f9fa;padding:10px;border-left:4px solid #ffc107;margin-top:20px;'>
                    <strong>Ce lien expire dans 1 heure.</strong>
                </div>
                <hr>
                <p style='font-size:12px;color:#666;'>Valorys / DocTime</p>
            ";

        $sent = MailConfig::send(
            $user['email'],
            $user['prenom'] . ' ' . $user['nom'],
            'Réinitialisation de votre mot de passe - Valorys',
            $resetBody
        );

        unset($_SESSION['forgot_captcha_code']);

        if (!$sent) {
            AppLogger::log('mail', 'forgot_password_smtp_failed', ['user_id' => (int) ($user['id'] ?? 0)]);
            $_SESSION['error'] = 'L’e-mail n’a pas pu être envoyé (erreur SMTP). Vérifiez MAIL_* dans .env, les journaux PHP (logs/php_error.log), et pour Gmail utilisez un mot de passe d’application.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }

        AppLogger::log('mail', 'forgot_password_sent', ['user_id' => (int) ($user['id'] ?? 0)]);
        $_SESSION['success'] = 'Un e-mail avec le lien de réinitialisation a été envoyé. Vérifiez votre boîte de réception et le dossier courrier indésirable.';
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        exit;
    }

    public function showResetPassword($token = null): void {
        $error = null;
        $validToken = false;
        
        if ($token) {
            $token = preg_replace('/[^a-f0-9]/', '', $token);
            
            $stmt = $this->userModel->db->prepare(
                "SELECT id FROM users WHERE reset_token = :token AND reset_expires > NOW()"
            );
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
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
        
        $token = $_SESSION['reset_token'] ?? null;
        $newPassword = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        if (!$token) {
            $_SESSION['error'] = "Demande invalide. Veuillez refaire une demande.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
            exit;
        }
        
        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token);
            exit;
        }
        
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins une majuscule.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token);
            exit;
        }
        
        if (!preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins un chiffre.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token);
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token);
            exit;
        }
        
        $stmt = $this->userModel->db->prepare(
            "UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL 
             WHERE reset_token = :token AND reset_expires > NOW()"
        );
        $result = $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':token' => $token
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            unset($_SESSION['reset_token']);
            AppLogger::log('auth', 'password_reset_success', []);
            $_SESSION['success'] = "Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        } else {
            $_SESSION['error'] = "Lien invalide ou expiré.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        }
        exit;
    }

    // ─────────────────────────────────────────
    //  Double authentification (email)
    // ─────────────────────────────────────────
    public function showVerifyTwoFactor(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
        $viewPath = __DIR__ . '/../views/frontoffice/verify_2fa.php';
        if (is_file($viewPath)) {
            require $viewPath;
        } else {
            echo 'Vue verify_2fa.php manquante.';
        }
    }

    public function verifyTwoFactorCode(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $code = preg_replace('/\D/', '', trim((string) ($_POST['verification_code'] ?? $_POST['code'] ?? '')));
        $pending = $_SESSION['pending_2fa'];

        if (time() > (int) ($pending['expires_at'] ?? 0)) {
            unset($_SESSION['pending_2fa']);
            $_SESSION['errors'] = ['__form' => 'Le code a expiré. Reconnectez-vous pour en recevoir un nouveau.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        if ($code !== (string) ($pending['code'] ?? '')) {
            $_SESSION['errors'] = ['__form' => 'Code incorrect.'];
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
            exit;
        }

        $user = $pending['user'];
        $redirect = (string) ($pending['redirect'] ?? 'index.php?page=accueil');

        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['user_role']  = (string) ($user['role'] ?? 'patient');
        $_SESSION['user_name']  = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
        $_SESSION['user_email'] = (string) ($user['email'] ?? '');

        unset($_SESSION['pending_2fa']);

        try {
            $this->userModel->update((int) $user['id'], [
                'derniere_connexion' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // non bloquant
        }

        $_SESSION['success'] = 'Connexion réussie.';
        $this->redirectToPostLogin($redirect);
        exit;
    }

    public function resendTwoFactorCode(): void {
        if (empty($_SESSION['pending_2fa'])) {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $pending = $_SESSION['pending_2fa'];
        $user = $pending['user'] ?? [];
        $redirect = (string) ($pending['redirect'] ?? 'index.php?page=accueil');

        if (!$this->startEmailTwoFactorChallenge($user, $redirect)) {
            $_SESSION['errors'] = ['__form' => 'Impossible de renvoyer le code. Vérifiez la configuration email.'];
        } else {
            $_SESSION['success'] = 'Un nouveau code a été envoyé.';
        }

        header('Location: ' . $this->getBaseUrl() . 'index.php?page=verify_2fa');
        exit;
    }

    // ─────────────────────────────────────────
    //  Connexion sociale (Google, GitHub, Facebook)
    // ─────────────────────────────────────────
    public function startSocialLogin(string $provider): void {
        $this->ensureSocialAuthConfigLoaded();
        $provider = strtolower(trim($provider));
        $config = SocialAuthConfig::get($provider);

        if ($config === null) {
            $_SESSION['error'] = 'Fournisseur de connexion non pris en charge.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        if (!SocialAuthConfig::isConfigured($provider)) {
            $_SESSION['error'] = 'La connexion ' . ($config['label'] ?? $provider) . ' n\'est pas configurée (clés OAuth manquantes dans .env).';
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

        if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
            $_SESSION['error'] = 'Échec de vérification OAuth (' . ($config['label'] ?? $provider) . ').';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        if (!empty($_GET['error'])) {
            $_SESSION['error'] = 'Connexion ' . ($config['label'] ?? $provider) . ' annulée ou refusée.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            $_SESSION['error'] = 'Code OAuth manquant.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        try {
            $tokenData = $this->exchangeSocialCodeForToken($provider, $code, $config);
            $profile = $this->fetchSocialProfile($provider, $tokenData, $config);
            $user = $this->findOrCreateSocialUser($provider, $profile);

            if (empty($user['id'])) {
                throw new RuntimeException('Compte social introuvable.');
            }

            if (($user['statut'] ?? 'actif') !== 'actif') {
                $_SESSION['error'] = 'Votre compte est ' . ($user['statut'] ?? '') . '. Contactez l\'administrateur.';
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            $this->startUserSession($user);
            $_SESSION['success'] = 'Connexion ' . ($config['label'] ?? $provider) . ' réussie.';
            $this->redirectToPostLogin($this->resolvePostLoginTarget($user));
            exit;
        } catch (Throwable $e) {
            error_log('Erreur social login [' . $provider . ']: ' . $e->getMessage());
            $_SESSION['error'] = 'Impossible de finaliser la connexion ' . ($config['label'] ?? $provider) . '.';
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }
    }

    private function startEmailTwoFactorChallenge(array $user, string $redirect): bool {
        $code = (string) random_int(100000, 999999);
        $email = trim((string) ($user['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('2FA email: adresse invalide pour user id ' . ($user['id'] ?? ''));
            return false;
        }

        $emailBody = '
            <h1>Code de vérification</h1>
            <p>Bonjour ' . htmlspecialchars((string) ($user['prenom'] ?? '')) . ',</p>
            <p>Votre code à <strong>6 chiffres</strong> : <strong style="font-size:1.25em">' . htmlspecialchars($code) . '</strong></p>
            <p>Il expire dans <strong>5 minutes</strong>. Si vous n\'avez pas demandé cette connexion, ignorez ce message.</p>
        ';

        try {
            $ok = MailConfig::send(
                $email,
                trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
                'Votre code DocTime / Valorys',
                $emailBody
            );
        } catch (Throwable $e) {
            error_log('Erreur envoi email 2FA: ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            return false;
        }

        $safeUser = $user;
        unset($safeUser['password']);

        $_SESSION['pending_2fa'] = [
            'code'         => $code,
            'expires_at'   => time() + 300,
            'redirect'     => $redirect,
            'user'         => $safeUser,
            'masked_email' => $this->maskEmail($email),
        ];

        return true;
    }

    private function resolvePostLoginTarget(array $user): string {
        $raw = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);

        $default = ($user['role'] ?? '') === 'admin'
            ? 'index.php?page=dashboard'
            : 'index.php?page=accueil';

        if (!is_string($raw) || $raw === '') {
            return $default;
        }
        if (str_contains($raw, 'login') || str_contains($raw, 'register')) {
            return $default;
        }
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }
        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        return ltrim($raw, '/');
    }

    private function redirectToPostLogin(string $dest): void {
        if (str_starts_with($dest, 'http://') || str_starts_with($dest, 'https://')) {
            header('Location: ' . $dest);
            return;
        }
        if (str_starts_with($dest, '/')) {
            header('Location: ' . $dest);
            return;
        }
        header('Location: ' . $this->getBaseUrl() . $dest);
    }

    private function maskEmail(string $email): string {
        if (!str_contains($email, '@')) {
            return 'votre adresse email';
        }
        [$local, $domain] = explode('@', $email, 2);
        $len = strlen($local);
        $keep = max(1, min(3, $len));

        return substr($local, 0, $keep) . str_repeat('•', max(2, $len - $keep)) . '@' . $domain;
    }

    private function ensureSocialAuthConfigLoaded(): void {
        if (!class_exists('SocialAuthConfig', false)) {
            require_once __DIR__ . '/../config/social_auth.php';
        }
    }

    private function getSocialCallbackUrl(string $provider): string {
        return $this->getBaseUrl() . 'index.php?page=social_callback&provider=' . urlencode($provider);
    }

    /** @return array<string, mixed> */
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
            $headers = ['Accept: application/json', 'User-Agent: Valorys-OAuth'];
        }

        return $this->sendHttpRequest($config['token_url'], 'POST', $payload, $headers);
    }

    /** @return array<string, mixed> */
    private function fetchSocialProfile(string $provider, array $tokenData, array $config): array {
        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Jeton d\'accès vide.');
        }

        if ($provider === 'google') {
            return $this->sendHttpRequest($config['user_url'], 'GET', [], [
                'Authorization: Bearer ' . $accessToken,
            ]);
        }

        if ($provider === 'facebook') {
            return $this->sendHttpRequest($config['user_url'] . '&access_token=' . urlencode($accessToken), 'GET');
        }

        if ($provider === 'github') {
            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/vnd.github+json',
                'User-Agent: Valorys-OAuth',
            ];
            $profile = $this->sendHttpRequest($config['user_url'], 'GET', [], $headers);
            $emails = $this->sendHttpRequest($config['email_url'], 'GET', [], $headers);
            if (is_array($emails)) {
                $profile['emails'] = $emails;
            }

            return $profile;
        }

        throw new RuntimeException('Fournisseur OAuth non supporté.');
    }

    /** @return array<string, mixed> */
    private function findOrCreateSocialUser(string $provider, array $profile): array {
        $normalized = $this->normalizeSocialProfile($provider, $profile);
        $db = Database::getInstance()->getConnection();

        $providerMatch = null;
        if ($this->usersHasSocialColumns()) {
            $providerStmt = $db->prepare(
                'SELECT * FROM users WHERE social_provider = :provider AND social_provider_id = :provider_id LIMIT 1'
            );
            $providerStmt->execute([
                ':provider'    => $provider,
                ':provider_id' => $normalized['provider_id'],
            ]);
            $providerMatch = $providerStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($providerMatch) {
            $this->updateSocialUser((int) $providerMatch['id'], $provider, $normalized);

            return $this->userModel->findById((int) $providerMatch['id']) ?? $providerMatch;
        }

        $emailMatch = null;
        if ($normalized['email'] !== '') {
            $emailMatch = $this->userModel->findByEmail($normalized['email']);
        }

        if ($emailMatch) {
            $this->updateSocialUser((int) $emailMatch['id'], $provider, $normalized);

            return $this->userModel->findById((int) $emailMatch['id']) ?? $emailMatch;
        }

        return $this->createSocialUser($provider, $normalized);
    }

    /** @return array{provider_id: string, email: string, prenom: string, nom: string, avatar: string} */
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
            $emailFb = trim((string) ($profile['email'] ?? ''));
            if ($emailFb === '' && !empty($profile['id'])) {
                $emailFb = 'facebook_' . preg_replace('/\D/', '', (string) $profile['id']) . '@social.local';
            }

            return [
                'provider_id' => (string) ($profile['id'] ?? ''),
                'email'       => $emailFb,
                'prenom'      => trim((string) ($profile['first_name'] ?? 'Utilisateur')),
                'nom'         => trim((string) ($profile['last_name'] ?? 'Facebook')),
                'avatar'      => $picture,
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

        throw new RuntimeException('Profil social non supporté.');
    }

    /** @return array<string, mixed> */
    private function createSocialUser(string $provider, array $normalized): array {
        if ($normalized['provider_id'] === '') {
            throw new RuntimeException('Identifiant social manquant.');
        }

        $db = Database::getInstance()->getConnection();
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $hasSocial = $this->usersHasSocialColumns();

        $columns = 'nom, prenom, email, telephone, password, role, statut, avatar, created_at';
        $values  = ':nom, :prenom, :email, :telephone, :password, :role, :statut, :avatar, NOW()';
        $params = [
            ':nom'       => $normalized['nom'],
            ':prenom'    => $normalized['prenom'],
            ':email'     => $normalized['email'],
            ':telephone' => '',
            ':password'  => $password,
            ':role'      => 'patient',
            ':statut'    => 'actif',
            ':avatar'    => $normalized['avatar'] !== '' ? $normalized['avatar'] : null,
        ];

        if ($hasSocial) {
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
            throw new RuntimeException('Création du compte impossible.');
        }

        try {
            $this->userModel->createPatient(['user_id' => $userId, 'groupe_sanguin' => null]);
        } catch (Throwable $e) {
            // patient optionnel si table ou doublon
        }

        $row = $this->userModel->findById($userId);

        return is_array($row) ? $row : [];
    }

    private function updateSocialUser(int $userId, string $provider, array $normalized): void {
        $lastConnection = date('Y-m-d H:i:s');

        if ($this->usersHasSocialColumns()) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'UPDATE users
                 SET social_provider = :provider,
                     social_provider_id = :provider_id,
                     social_avatar = :social_avatar,
                     derniere_connexion = :derniere_connexion
                 WHERE id = :id'
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

        $this->userModel->update($userId, ['derniere_connexion' => $lastConnection]);
    }

    private function usersHasSocialColumns(): bool {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'social_provider'");
        $has = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

        return $has;
    }

    private function startUserSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['user_role']  = (string) ($user['role'] ?? 'patient');
        $_SESSION['user_name']  = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
        $_SESSION['user_email'] = (string) ($user['email'] ?? '');
        try {
            $this->userModel->update((int) $user['id'], ['derniere_connexion' => date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            // non bloquant
        }
    }

    /**
     * @param array<string, string|int|float> $data
     * @param list<string> $headers
     * @return array<string, mixed>
     */
    private function sendHttpRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): array {
        $method = strtoupper($method);

        if (function_exists('curl_init')) {
            $ch = curl_init();

            if ($method === 'GET' && $data !== []) {
                $sep = str_contains($url, '?') ? '&' : '?';
                $url .= $sep . http_build_query($data);
            }

            if ($method === 'POST' && !in_array('Content-Type: application/x-www-form-urlencoded', $headers, true)) {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_HTTPHEADER     => $headers,
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

            $raw = curl_exec($ch);
            if ($raw === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Erreur réseau: ' . $err);
            }
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                throw new RuntimeException('HTTP ' . $httpCode);
            }

            return $this->decodeOAuthResponse((string) $raw);
        }

        if ($method === 'GET' && $data !== []) {
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . http_build_query($data);
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
                'timeout'       => 25,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new RuntimeException('Échec HTTP.');
        }

        return $this->decodeOAuthResponse((string) $raw);
    }

    /** @return array<string, mixed> */
    private function decodeOAuthResponse(string $raw): array {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (str_contains($raw, 'access_token=')) {
            parse_str($raw, $parsed);

            return is_array($parsed) ? $parsed : [];
        }

        throw new RuntimeException('Réponse OAuth invalide.');
    }

// ─────────────────────────────────────────
//  Reconnaissance faciale
// ─────────────────────────────────────────
public function faceLogin(): void {
    header('Content-Type: application/json');

    $imageData = $_POST['face_image'] ?? '';
    if (empty($imageData)) {
        echo json_encode(['success' => false, 'message' => 'Aucune image reçue']);
        exit;
    }

    try {
        $user = $this->faceModel->findUserByFace($imageData);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        error_log('faceLogin PDO: ' . $msg);
        if (str_contains($msg, 'Unknown column') && str_contains($msg, 'face_')) {
            echo json_encode([
                'success' => false,
                'message' => 'Base de données incomplète pour la reconnaissance faciale. Dans phpMyAdmin, exécutez le fichier SQL database/alter_users_face_recognition.sql sur votre base.',
            ]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Erreur serveur lors de la reconnaissance.']);
        exit;
    } catch (Throwable $e) {
        error_log('faceLogin: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur lors de la reconnaissance.']);
        exit;
    }

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Visage non reconnu. Veuillez utiliser email/mot de passe.']);
        exit;
    }

    // Vérifier si le compte est actif
    if ($user['statut'] !== 'actif') {
        echo json_encode(['success' => false, 'message' => 'Votre compte est ' . $user['statut'] . '. Contactez l\'administrateur.']);
        exit;
    }

    // Démarrer la session
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = trim($user['nom'] . ' ' . $user['prenom']);
    $_SESSION['user_email'] = $user['email'];

    // Déterminer la redirection selon le rôle
    $redirect = match ($user['role']) {
        'admin'   => 'index.php?page=dashboard',
        'medecin' => 'index.php?page=accueil',
        default   => 'index.php?page=accueil',
    };

    echo json_encode([
        'success'  => true,
        'message'  => 'Reconnaissance faciale réussie !',
        'redirect' => $redirect,
        'role'     => $user['role'],
    ]);
    exit;
}
/**
 * Supprimer le visage enregistré de l'utilisateur
 */
public function deleteFace(): void {
    header('Content-Type: application/json; charset=utf-8');
    $this->requireAuth();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $userId = (int)$_SESSION['user_id'];

        $sel = $db->prepare('SELECT face_photo FROM users WHERE id = :id');
        $sel->execute([':id' => $userId]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        $rel = isset($row['face_photo']) ? trim((string)$row['face_photo']) : '';
        if ($rel !== '') {
            $full = __DIR__ . '/../' . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel), DIRECTORY_SEPARATOR);
            if (is_file($full)) {
                @unlink($full);
            }
        }

        $stmt = $db->prepare(
            'UPDATE users SET face_photo = NULL, face_encoding = NULL, face_descriptor = NULL WHERE id = :id'
        );
        $result = $stmt->execute([':id' => $userId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Visage supprimé avec succès']);
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
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter d\'abord.']);
        exit;
    }
    
    $imageData = $_POST['face_image'] ?? '';
    if (empty($imageData)) {
        echo json_encode(['success' => false, 'message' => 'Aucune image reçue']);
        exit;
    }
    
    $result = $this->faceModel->saveFacePhoto($_SESSION['user_id'], $imageData);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Visage enregistré avec succès ! Vous pourrez vous connecter par reconnaissance faciale.' : 'Erreur lors de l\'enregistrement'
    ]);
    exit;
}

    // ─────────────────────────────────────────
    //  Vérifier email (AJAX)
    // ─────────────────────────────────────────
    public function checkEmail(): void {
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? $_GET['email'] ?? '');

        if (empty($email)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        $user = $this->userModel->findByEmail($email);
        echo json_encode(['exists' => (bool)$user]);
        exit;
    }

    private function ensureRecaptchaConfig(): void {
        if (class_exists('RecaptchaConfig', false)) {
            return;
        }
        $path = __DIR__ . '/../config/recaptcha.php';
        if (is_readable($path)) {
            require_once $path;
        }
    }

    // ─────────────────────────────────────────
    //  Helper : URL de base
    // ─────────────────────────────────────────
    private function getBaseUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base     = rtrim($script, '/') . '/';
        return $protocol . '://' . $host . $base;
    }

    // ─────────────────────────────────────────
    //  Vues de secours
    // ─────────────────────────────────────────
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
                            <h4>Valorys — Connexion</h4>
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
                                <a href="index.php?page=register">Créer un compte</a> |
                                <a href="index.php?page=forgot_password">Mot de passe oublié</a>
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
                            <h4>Valorys — Inscription</h4>
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
                                        <label class="form-label">Prénom *</label>
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
                                        <option value="medecin">Médecin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">S'inscrire</button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a href="index.php?page=login">Déjà un compte ? Se connecter</a>
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
            <title>Mot de passe oublié - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark text-center">
                            <h4>Mot de passe oublié</h4>
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
                                <a href="index.php?page=login">Retour à la connexion</a>
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
            <title>Réinitialisation - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h4>Réinitialisation du mot de passe</h4>
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
                                        <small class="text-muted">Minimum 8 caractères, 1 majuscule, 1 chiffre</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Réinitialiser</button>
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
}
?>
