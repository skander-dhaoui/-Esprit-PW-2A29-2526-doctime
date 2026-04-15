<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {

    private User $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new User();
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
    //  Afficher le formulaire de connexion (toujours accessible)
    // ─────────────────────────────────────────
    public function showLogin(): void {
        // On ne redirige plus ici ! La page login reste accessible
        // même si l'utilisateur est connecté
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

            // Redirection après connexion
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
    $error = $_SESSION['error'] ?? null;
    $old   = $_SESSION['old']   ?? null;
    unset($_SESSION['error'], $_SESSION['old']);

    $viewPath     = __DIR__ . '/../views/frontoffice/register.php';
    $viewPathHtml = __DIR__ . '/../views/frontoffice/register.html';

    if (file_exists($viewPath)) {
        require_once $viewPath;
    } elseif (file_exists($viewPathHtml)) {
        require_once $viewPathHtml;
    } else {
        $this->renderRegisterFallback($error, $old);
    }
}

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }

        $nom      = trim($_POST['nom']      ?? '');
        $prenom   = trim($_POST['prenom']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = $_POST['role']          ?? 'patient';

        $errors = [];

        if (empty($nom))      $errors[] = "Le nom est requis.";
        if (empty($prenom))   $errors[] = "Le prénom est requis.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if (strlen($password) < 6)
            $errors[] = "Mot de passe : 6 caractères minimum.";
        if (!in_array($role, ['patient', 'medecin']))
            $role = 'patient';

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['old']   = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }

        try {
            if ($this->userModel->findByEmail($email)) {
                $_SESSION['error'] = "Cet email est déjà utilisé.";
                $_SESSION['old']   = $_POST;
                header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
                exit;
            }

            $statut = ($role === 'medecin') ? 'en_attente' : 'actif';

            $userId = $this->userModel->create([
                'nom'      => $nom,
                'prenom'   => $prenom,
                'email'    => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role'     => $role,
                'statut'   => $statut,
            ]);

            if (!$userId) {
                throw new Exception("Erreur lors de la création du compte.");
            }

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
            $_SESSION['error'] = "Erreur serveur : " . $e->getMessage();
            $_SESSION['old']   = $_POST;
            header('Location: ' . $this->getBaseUrl() . 'index.php?page=register');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mot de passe oublié
    // ─────────────────────────────────────────
    public function showForgotPassword(): void {
        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $viewPath = __DIR__ . '/../views/forgot_password.php';
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

        $_SESSION['success'] = "Si cet email existe, un lien de réinitialisation a été envoyé.";
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=forgot_password');
        exit;
    }

    public function showResetPassword($token = null): void {
        $viewPath = __DIR__ . '/../views/reset_password.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo '<p>Réinitialisation non disponible.</p>';
        }
    }

    public function resetPassword(): void {
        $_SESSION['success'] = "Mot de passe réinitialisé.";
        header('Location: ' . $this->getBaseUrl() . 'index.php?page=login');
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
                            <?php if (!empty($_SESSION['success'])): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                                <?php unset($_SESSION['success']); ?>
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
}
?>