<?php
if (class_exists('UserController')) return;
require_once __DIR__ . '/../config/database.php';
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
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        $viewPath = __DIR__ . '/../views/frontoffice/login.php';
        $viewPathHtml = __DIR__ . '/../views/frontoffice/login.html';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } elseif (file_exists($viewPathHtml)) {
            require_once $viewPathHtml;
        } else {
            $this->renderLoginFallback($error);
        }
    }

    // ─────────────────────────────────────────
    //  Traiter la connexion
    // ─────────────────────────────────────────
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Email et mot de passe requis.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
            exit;
        }

        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                $_SESSION['error'] = "Email ou mot de passe incorrect.";
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            if (!password_verify($password, $user['password'])) {
                $_SESSION['error'] = "Email ou mot de passe incorrect.";
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            if ($user['statut'] !== 'actif') {
                $_SESSION['error'] = "Votre compte est " . $user['statut'] . ". Contactez l'administrateur.";
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
                exit;
            }

            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_name']  = trim($user['nom'] . ' ' . $user['prenom']);
            $_SESSION['user_email'] = $user['email'];

            try {
                $this->userModel->update($user['id'], [
                    'derniere_connexion' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                // Non bloquant
            }

            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($redirect && strpos($redirect, 'login') === false && strpos($redirect, 'register') === false) {
                header('Location: ' . $redirect);
            } elseif ($user['role'] === 'admin') {
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=dashboard');
            } else {
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=accueil');
            }
            exit;

        } catch (Exception $e) {
            error_log('Erreur login: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur serveur. Veuillez réessayer.";
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

        // ✅ CORRECTION : TOUJOURS REDIRIGER VERS LA PAGE DE CONNEXION
        if ($role === 'medecin') {
            $_SESSION['success'] = "Compte médecin créé avec succès. En attente de validation par un administrateur. Vous recevrez un email de confirmation.";
        } else {
            $_SESSION['success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        }
        
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
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

        $user = $this->userModel->findByEmail($email);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->userModel->update($user['id'], [
                'reset_token' => $token,
                'reset_expires' => $expires
            ]);
            
            $resetLink = $this->getBaseUrl() . 'index.php?page=reset_password&token=' . $token;
            
            $resetBody = "
                <h1>Réinitialisation de votre mot de passe</h1>
                <p>Bonjour <strong>" . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</strong>,</p>
                <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous :</p>
                <p style='margin: 30px 0;'>
                    <a href='" . $resetLink . "' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Réinitialiser mon mot de passe</a>
                </p>
                <p>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p>
                <div style='background:#f8f9fa;padding:10px;border-left:4px solid #ffc107;margin-top:20px;'>
                    <strong>⚠️ Ce lien expirera dans 1 heure.</strong>
                </div>
                <hr>
                <p style='font-size:12px;color:#666;'>© 2024 DocTime - Plateforme médicale</p>
            ";
            
            MailConfig::send($user['email'], $user['prenom'] . ' ' . $user['nom'], 'Réinitialisation de votre mot de passe - DocTime', $resetBody);
        }
        
        $_SESSION['success'] = "Si cet email existe, vous recevrez un lien de réinitialisation.";
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
            $_SESSION['success'] = "Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
        } else {
            $_SESSION['error'] = "Lien invalide ou expiré.";
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        }
        exit;
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
    
    // Vérifier l'utilisateur par reconnaissance faciale
    $user = $this->faceModel->findUserByFace($imageData);
    
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
}
/**
 * Supprimer le visage enregistré de l'utilisateur
 */
public function deleteFace(): void {
    $this->requireAuth();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $userId = (int)$_SESSION['user_id'];
        
        $stmt = $db->prepare("UPDATE users SET face_descriptor = NULL WHERE id = :id");
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