<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/AuthController.php';

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

        // Fusionner les extras dans $user pour la vue
        if (!empty($extras)) {
            $user = array_merge($user, $extras);
        }

        // Statistiques selon le rôle
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

        // Vider les messages flash avant affichage
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

        // Vérification de base
        if (empty($data['nom']) || empty($data['prenom'])) {
            $_SESSION['error_profil'] = 'Le nom et le prénom sont obligatoires.';
            header('Location: index.php?page=profil');
            exit;
        }
        
        unset($_SESSION['error_profil']);
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_profil'] = 'Email invalide.';
            header('Location: index.php?page=profil');
            exit;
        }

        // Vérifier unicité email
        $existing = $this->userModel->findByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $userId) {
            $_SESSION['error_profil'] = 'Cet email est déjà utilisé.';
            header('Location: index.php?page=profil');
            exit;
        }

        $this->userModel->update($userId, $data);

        // Extras selon le rôle
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

        // Mettre à jour la session
        $_SESSION['user_name']  = $data['prenom'] . ' ' . $data['nom'];
        $_SESSION['user_email'] = $data['email'];

        unset($_SESSION['error_profil']);
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

        unset($_SESSION['error_password_profil']);
        $_SESSION['success_password_profil'] = 'Mot de passe modifié avec succès.';
        header('Location: index.php?page=profil');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ NOUVELLES MÉTHODES POUR L'AVATAR
    // ═══════════════════════════════════════════════════════════

    /**
     * Mettre à jour l'avatar
     */
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
        
        // Vérifier que l'utilisateur existe
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

    /**
     * Supprimer l'avatar
     */
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
}