<?php
if (class_exists('UserController')) return;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Medecin.php';

class UserController {

    private User           $userModel;
    private Patient        $patientModel;
    private Medecin        $medecinModel;
    private AuthController $auth;

    public function __construct() {
        $this->userModel    = new User();
        $this->patientModel = new Patient();
        $this->medecinModel = new Medecin();
        $this->auth         = new AuthController();
    }

    // ─────────────────────────────────────────
    //  Profil utilisateur connecté
    // ─────────────────────────────────────────
    public function showProfil(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? 'patient';

        $user   = $this->userModel->findById($userId);
        $extras = $this->userModel->getExtras($userId, $userRole);

        if (!empty($extras)) {
            $user = array_merge($user, $extras);
        }

        $stats = [];
        if ($userRole === 'patient') {
            $stats = $this->patientModel->getStats($userId);
            $stats['total_rdv']   = $stats['rdv_total']   ?? 0;
            $stats['rdv_avenir']  = $stats['rdv_a_venir'] ?? 0;
            $stats['note_moyenne'] = '—';
        } elseif ($userRole === 'medecin') {
            $raw   = $this->medecinModel->getStats($userId);
            $stats = [
                'total_rdv'    => $raw['rdv_total']  ?? 0,
                'rdv_avenir'   => $raw['rdv_pending'] ?? 0,
                'note_moyenne' => '4.8',
            ];
        }

        $success         = $_SESSION['success_profil']         ?? null;
        $error           = $_SESSION['error_profil']           ?? null;
        $successPassword = $_SESSION['success_password_profil'] ?? null;
        $errorPassword   = $_SESSION['error_password_profil']  ?? null;
        unset(
            $_SESSION['success_profil'],
            $_SESSION['error_profil'],
            $_SESSION['success_password_profil'],
            $_SESSION['error_password_profil']
        );

        $viewPath = __DIR__ . '/../views/frontoffice/profil.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $viewPath = __DIR__ . '/../views/frontoffice/profil.html';
            file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
        }
    }

    /**
     * Afficher le formulaire de modification de profil
     */
    public function editProfilForm(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? 'patient';

        $user = $this->userModel->findById($userId);
        if (!$user) {
            $_SESSION['error'] = "Utilisateur non trouvé";
            header('Location: index.php?page=profil');
            exit;
        }

        $extras = $this->userModel->getExtras($userId, $userRole);
        if (!empty($extras)) {
            $user = array_merge($user, $extras);
        }

        $success = $_SESSION['success'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        $viewPath = __DIR__ . '/../views/frontoffice/modifier_profil.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderSimpleEditForm($user, $userRole, $success, $error);
        }
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfil(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=profil');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? 'patient';

        $data = [
            'nom'            => trim($_POST['nom']            ?? ''),
            'prenom'         => trim($_POST['prenom']         ?? ''),
            'email'          => trim($_POST['email']          ?? ''),
            'telephone'      => trim($_POST['telephone']      ?? ''),
            'adresse'        => trim($_POST['adresse']        ?? ''),
            'date_naissance' => $_POST['date_naissance']      ?? null,
        ];

        if (empty($data['nom']) || empty($data['prenom'])) {
            $_SESSION['error_profil'] = 'Le nom et le prénom sont obligatoires.';
            header('Location: index.php?page=profil');
            exit;
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_profil'] = 'Email invalide.';
            header('Location: index.php?page=profil');
            exit;
        }

        $existing = $this->userModel->findByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $userId) {
            $_SESSION['error_profil'] = 'Cet email est déjà utilisé.';
            header('Location: index.php?page=profil');
            exit;
        }

        $this->userModel->update($userId, $data);

        if ($userRole === 'patient') {
            $this->userModel->upsertPatient($userId, [
                'groupe_sanguin' => $_POST['groupe_sanguin'] ?? null,
            ]);
        } elseif ($userRole === 'medecin') {
            $this->medecinModel->update($userId, [
                'specialite'      => $_POST['specialite']      ?? '',
                'tarif'           => $_POST['tarif']           ?? 0,
                'experience'      => $_POST['experience']      ?? 0,
                'adresse_cabinet' => $_POST['adresse_cabinet'] ?? '',
                'bio'             => $_POST['bio']             ?? '',
            ]);
        }

        $_SESSION['user_name']  = $data['prenom'] . ' ' . $data['nom'];
        $_SESSION['user_email'] = $data['email'];

        $_SESSION['success_profil'] = 'Profil mis à jour avec succès.';
        header('Location: index.php?page=profil');
        exit;
    }

    // ─────────────────────────────────────────
    //  Changement de mot de passe
    // ─────────────────────────────────────────
    public function changePassword(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=profil');
            exit;
        }

        $userId          = (int)$_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password']     ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = $this->userModel->findById($userId);
        if (!$user) {
            $_SESSION['error_password_profil'] = 'Utilisateur introuvable.';
            header('Location: index.php?page=profil');
            exit;
        }

        if (!password_verify($currentPassword, $user['password'])) {
            $_SESSION['error_password_profil'] = 'Mot de passe actuel incorrect.';
            header('Location: index.php?page=profil');
            exit;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error_password_profil'] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            header('Location: index.php?page=profil');
            exit;
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $_SESSION['error_password_profil'] = 'Le nouveau mot de passe doit contenir au moins une majuscule.';
            header('Location: index.php?page=profil');
            exit;
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['error_password_profil'] = 'Le nouveau mot de passe doit contenir au moins un chiffre.';
            header('Location: index.php?page=profil');
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error_password_profil'] = 'Les mots de passe ne correspondent pas.';
            header('Location: index.php?page=profil');
            exit;
        }
        if ($newPassword === $currentPassword) {
            $_SESSION['error_password_profil'] = 'Le nouveau mot de passe doit être différent de l\'ancien.';
            header('Location: index.php?page=profil');
            exit;
        }

        $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $_SESSION['success_password_profil'] = 'Mot de passe modifié avec succès.';
        header('Location: index.php?page=profil');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  AVATAR
    // ═══════════════════════════════════════════════════════════

    public function updateAvatar(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['avatar'])) {
            header('Location: index.php?page=profil');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        if (!$user) {
            $_SESSION['error_profil'] = "Utilisateur non trouvé.";
            header('Location: index.php?page=profil');
            exit;
        }
        
        $result = $this->userModel->uploadAvatar($_FILES['avatar'], $userId);
        
        if ($result) {
            $_SESSION['success_profil'] = "Photo de profil mise à jour avec succès.";
        } else {
            $_SESSION['error_profil'] = "Erreur lors de l'upload. Vérifiez le format (JPG, PNG, GIF, WEBP) et la taille (max 2 Mo).";
        }
        
        header('Location: index.php?page=profil');
        exit;
    }

    public function deleteAvatar(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $result = $this->userModel->deleteAvatar($userId);
        
        if ($result) {
            $_SESSION['success_profil'] = "Photo de profil supprimée avec succès.";
        } else {
            $_SESSION['error_profil'] = "Erreur lors de la suppression.";
        }
        
        header('Location: index.php?page=profil');
        exit;
    }

    // ─────────────────────────────────────────
    //  CRUD utilisateurs (backoffice admin)
    // ─────────────────────────────────────────
    public function index(): void {
        $this->auth->requireRole('admin');
        $users    = $this->userModel->getAll();
        $viewPath = __DIR__ . '/../views/backoffice/users_list.html';
        file_exists($viewPath) ? require_once $viewPath : $this->renderTable($users);
    }

    public function create(): void {
        $this->auth->requireRole('admin');
        $old   = $_SESSION['old']                ?? null;
        $flash = $_SESSION['flash']['message']    ?? null;
        unset($_SESSION['old'], $_SESSION['flash']);

        $viewPath = __DIR__ . '/../views/backoffice/user_add.html';
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../views/backoffice/user_form.html';
        }
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function store(): void {
        $this->auth->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=users&action=create');
            exit;
        }

        $data   = $this->extractFormData();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            $_SESSION['old']   = $_POST;
            header('Location: index.php?page=users&action=create');
            exit;
        }

        if ($this->userModel->findByEmail($data['email'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
            $_SESSION['old']   = $_POST;
            header('Location: index.php?page=users&action=create');
            exit;
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId           = $this->userModel->create($data);
        $this->saveRoleExtras($userId, $data['role']);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur créé avec succès.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function edit(int $id): void {
        $this->auth->requireRole('admin');
        $user  = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); }

        $extra = $this->userModel->getExtras($id, $user['role']);
        $old   = $_SESSION['old']             ?? null;
        $flash = $_SESSION['flash']['message'] ?? null;
        unset($_SESSION['old'], $_SESSION['flash']);

        $viewPath = __DIR__ . '/../views/backoffice/user_edit.html';
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../views/backoffice/user_form.html';
        }
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function update(int $id): void {
        $this->auth->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=users&action=edit&id=$id");
            exit;
        }

        $user = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); }

        $data   = $this->extractFormData(false);
        $errors = $this->validate($data, false);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            $_SESSION['old']   = $_POST;
            header("Location: index.php?page=users&action=edit&id=$id");
            exit;
        }

        $existing = $this->userModel->findByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $id) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
            $_SESSION['old']   = $_POST;
            header("Location: index.php?page=users&action=edit&id=$id");
            exit;
        }

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $data);
        $this->saveRoleExtras($id, $data['role']);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur mis à jour.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function delete(int $id): void {
        $this->auth->requireRole('admin');

        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vous ne pouvez pas supprimer votre propre compte.'];
            header('Location: index.php?page=users');
            exit;
        }

        $this->userModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur supprimé.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function toggleStatus(int $id): void {
        $this->auth->requireRole('admin');
        $user = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); }

        $newStatus = ($user['statut'] === 'actif') ? 'inactif' : 'actif';
        $this->userModel->update($id, ['statut' => $newStatus]);
        header('Location: index.php?page=users');
        exit;
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function extractFormData(bool $withPassword = true): array {
        $data = [
            'nom'            => trim($_POST['nom']            ?? ''),
            'prenom'         => trim($_POST['prenom']         ?? ''),
            'email'          => trim($_POST['email']          ?? ''),
            'telephone'      => trim($_POST['telephone']      ?? ''),
            'adresse'        => trim($_POST['adresse']        ?? ''),
            'date_naissance' => $_POST['date_naissance']      ?? null,
            'role'           => $_POST['role']                ?? 'patient',
            'statut'         => $_POST['statut']              ?? 'actif',
        ];

        if ($withPassword && !empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        return $data;
    }

    private function validate(array $data, bool $requirePassword = true): array {
        $errors = [];

        if (empty($data['nom']))    $errors[] = 'Le nom est obligatoire.';
        if (empty($data['prenom'])) $errors[] = 'Le prénom est obligatoire.';

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }

        if (empty($data['telephone'])) {
            $errors[] = 'Le téléphone est obligatoire.';
        }

        if ($requirePassword) {
            $pwd = $data['password'] ?? '';
            if (strlen($pwd) < 8)             $errors[] = 'Mot de passe : au moins 8 caractères.';
            if (!preg_match('/[A-Z]/', $pwd)) $errors[] = 'Mot de passe : au moins une majuscule.';
            if (!preg_match('/[0-9]/', $pwd)) $errors[] = 'Mot de passe : au moins un chiffre.';
        }

        return $errors;
    }

    private function saveRoleExtras(int $userId, string $role): void {
        if ($role === 'patient') {
            $this->userModel->upsertPatient($userId, [
                'groupe_sanguin' => $_POST['groupe_sanguin'] ?? null,
            ]);
        }

        if ($role === 'medecin') {
            $this->userModel->upsertMedecin($userId, [
                'specialite'      => $_POST['specialite']      ?? '',
                'numero_ordre'    => $_POST['numero_ordre']    ?? '',
                'tarif'           => $_POST['tarif']           ?? 0,
                'experience'      => $_POST['experience']      ?? 0,
                'adresse_cabinet' => $_POST['adresse_cabinet'] ?? '',
            ]);
        }
    }

    private function notFound(): void {
        http_response_code(404);
        die('Utilisateur introuvable.');
    }

    private function renderTable(array $users): void {
        echo '<table border="1"><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th></tr>';
        foreach ($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['prenom']} {$u['nom']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>{$u['statut']}</td></tr>";
        }
        echo '</table>';
    }

    /**
     * Fallback simple pour l'édition du profil
     */
    private function renderSimpleEditForm($user, $userRole, $success, $error): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Modifier mon profil - MediConnect</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                :root { --primary: #2A7FAA; --secondary: #4CAF50; }
                body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; font-family: 'Segoe UI', sans-serif; }
                .profile-card { max-width: 550px; width: 100%; background: white; border-radius: 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden; }
                .card-header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 40px 28px; text-align: center; color: white; }
                .avatar-icon { width: 90px; height: 90px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
                .avatar-icon i { font-size: 45px; }
                .card-header h2 { font-size: 28px; margin: 0; }
                .card-header p { margin: 8px 0 0; opacity: 0.9; }
                .card-body { padding: 32px 28px; }
                .form-group { margin-bottom: 24px; }
                .form-label { font-weight: 600; color: #2d3748; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
                .input-icon { position: relative; }
                .input-icon i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #a0aec0; }
                .form-control { width: 100%; border-radius: 14px; padding: 14px 16px 14px 46px; border: 1.5px solid #e2e8f0; font-size: 15px; background: #f8fafc; transition: all 0.3s; }
                .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(42,127,170,0.1); background: white; }
                .password-section { background: #f8fafc; border-radius: 20px; padding: 20px; margin: 24px 0; border: 1px solid #e2e8f0; }
                .password-section-title { font-size: 16px; font-weight: 600; color: var(--primary); margin-bottom: 16px; }
                .btn-save { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 14px; padding: 14px 28px; border: none; font-weight: 600; width: 100%; transition: all 0.3s; }
                .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(76,175,80,0.3); }
                .btn-cancel { background: #f1f3f5; color: #6c757d; border-radius: 14px; padding: 14px 28px; text-decoration: none; display: inline-block; text-align: center; width: 100%; margin-top: 12px; font-weight: 600; transition: all 0.3s; }
                .btn-cancel:hover { background: #e9ecef; color: #495057; transform: translateY(-2px); }
                .alert-custom { border-radius: 14px; padding: 14px 18px; margin-bottom: 24px; border-left: 4px solid; }
                .alert-success-custom { background: #d4edda; color: #155724; border-left-color: #28a745; }
                .alert-error-custom { background: #f8d7da; color: #721c24; border-left-color: #dc3545; }
                .back-link { text-align: center; margin-top: 20px; }
                .back-link a { color: white; text-decoration: none; opacity: 0.9; }
                .back-link a:hover { opacity: 1; text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="profile-card">
                <div class="card-header">
                    <div class="avatar-icon"><i class="fas fa-user-edit"></i></div>
                    <h2>Modifier mon profil</h2>
                    <p>Mettez à jour vos informations personnelles</p>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert-custom alert-success-custom"><i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert-custom alert-error-custom"><i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="index.php?page=modifier_profil">
                        <div class="form-group">
                            <label class="form-label">Nom complet</label>
                            <div class="input-icon"><i class="fas fa-user"></i><input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required></div>
                        </div>
                        <div class="form-group">
                            <div class="input-icon"><i class="fas fa-user"></i><input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Adresse email</label>
                            <div class="input-icon"><i class="fas fa-envelope"></i><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <div class="input-icon"><i class="fas fa-phone"></i><input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Adresse</label>
                            <div class="input-icon"><i class="fas fa-map-marker-alt"></i><textarea name="adresse" class="form-control" rows="2" style="padding-top: 14px;"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea></div>
                        </div>
                        <div class="password-section">
                            <div class="password-section-title"><i class="fas fa-lock me-2"></i> Mot de passe <span style="font-size: 12px;">(optionnel)</span></div>
                            <div class="form-group" style="margin-bottom: 16px;"><div class="input-icon"><i class="fas fa-key"></i><input type="password" name="password" class="form-control" placeholder="Nouveau mot de passe"></div></div>
                            <div class="form-group" style="margin-bottom: 0;"><div class="input-icon"><i class="fas fa-check-circle"></i><input type="password" name="confirm_password" class="form-control" placeholder="Confirmer le mot de passe"></div></div>
                            <div class="password-hint" style="font-size: 12px; color: #718096; margin-top: 12px;"><i class="fas fa-info-circle"></i> Laisser vide pour ne pas changer. Minimum 6 caractères.</div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save me-2"></i> Enregistrer les modifications</button>
                        <a href="index.php?page=profil" class="btn-cancel"><i class="fas fa-times me-2"></i> Annuler</a>
                    </form>
                </div>
            </div>
            <div class="back-link"><a href="index.php?page=profil"><i class="fas fa-arrow-left me-2"></i> Retour à mon profil</a></div>
        </body>
        </html>
        <?php
    }
}// update
