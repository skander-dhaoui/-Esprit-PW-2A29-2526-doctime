<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/AuthController.php';

use App\Models\User;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Admin;
use App\Repositories\ArticleRepository;
use App\Repositories\UserRepository;

class AdminController {

    private UserRepository $userRepo;
    private Medecin     $medecinModel;
    private Patient     $patientModel;
    private Admin       $adminModel;
    private AuthController $auth;

    public function __construct() {
        $this->userRepo     = new UserRepository();
        $this->medecinModel = new Medecin();
        $this->patientModel = new Patient();
        $this->adminModel   = new Admin();
        $this->auth         = new AuthController();
    }

    // ─────────────────────────────────────────
    //  Dashboard admin
    // ─────────────────────────────────────────
    public function dashboard(): void {
        $this->auth->requireRole('admin');

        $db = Database::getInstance()->getConnection();

        // Récupérer les statistiques
        $totalUsersStmt = $db->prepare("SELECT COUNT(*) as count FROM users");
        $totalUsersStmt->execute();
        $totalUsers = $totalUsersStmt->fetch(PDO::FETCH_ASSOC)['count'];

        $medecinStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'medecin'");
        $medecinStmt->execute();
        $totalMedecins = $medecinStmt->fetch(PDO::FETCH_ASSOC)['count'];

        $patientStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'patient'");
        $patientStmt->execute();
        $totalPatients = $patientStmt->fetch(PDO::FETCH_ASSOC)['count'];

        $validationStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE statut = 'en_attente'");
        $validationStmt->execute();
        $enValidation = $validationStmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stats = [
            'total_users'     => $totalUsers,
            'total_medecins'  => $totalMedecins,
            'total_patients'  => $totalPatients,
            'en_validation'   => $enValidation,
        ];

        // Récupérer les utilisateurs récents
        $recentStmt = $db->prepare("SELECT id, nom, prenom, email, role, statut, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        $recentStmt->execute();
        $recentUsers = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        $users = $recentUsers;

        $viewPath = __DIR__ . '/../views/backoffice/dashboard.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderFallback('Dashboard', $stats, $users);
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques avancées
    // ─────────────────────────────────────────
    public function stats(): void {
        $this->auth->requireRole('admin');

        $statsData = [
            'inscriptions_par_mois' => $this->getMonthlyRegistrations(),
            'repartition_roles'     => $this->getRepartitionByRole(),
            'top_specialites'       => $this->getTopSpecialities(),
            'rdv_par_mois'          => $this->getMonthlyAppointments(),
        ];

        $viewPath = __DIR__ . '/../views/backoffice/stats.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    // ─────────────────────────────────────────
    //  Gestion des utilisateurs
    // ─────────────────────────────────────────
    public function listUsers(): void {
        $this->auth->requireRole('admin');
        $filters = $this->getBackofficeListFilters(
            [
                'created_at' => 'u.created_at',
                'nom' => 'u.nom',
                'email' => 'u.email',
                'role' => 'u.role',
                'statut' => 'u.statut',
            ],
            'created_at',
            'desc'
        );
        $result = $this->getFilteredUsers($filters);
        $users = $result['items'];
        $pagination = $result['pagination'];
        $page_title = 'Gestion des utilisateurs';
        $current_page = 'users';
        require __DIR__ . '/../views/backoffice/users_list.php';
    }

    public function showCreateUser(): void {
        $this->auth->requireRole('admin');
        $errors = [];
        $page_title = 'Ajouter un utilisateur';
        $current_page = 'users';
        require __DIR__ . '/../views/backoffice/user_add.php';
    }

    public function createUser(): void {
        $this->auth->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=users&action=create');
            exit;
        }

        $data = $this->extractUserFormData();
        $errors = [];
        
        // Validation
        if (empty($data['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire.';
        }
        if (empty($data['prenom'])) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est obligatoire.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide.';
        } elseif ($this->findUserByEmail($data['email'])) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
        if (empty($_POST['password'])) {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($_POST['password']) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if (empty($data['role'])) {
            $errors['role'] = 'Le rôle est obligatoire.';
        }
        
        // Si erreurs, retourner à la vue
        if (!empty($errors)) {
            $page_title = 'Ajouter un utilisateur';
            $current_page = 'users';
            require __DIR__ . '/../views/backoffice/user_add.php';
            return;
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId = $this->createUserRecord($data);

        if ($data['role'] === 'patient') {
            $this->upsertPatientExtra($userId, ['groupe_sanguin' => $_POST['groupe_sanguin'] ?? null]);
        } elseif ($data['role'] === 'medecin') {
            $this->upsertMedecinExtra($userId, [
                'specialite'      => $_POST['specialite']      ?? '',
                'numero_ordre'    => $_POST['numero_ordre']    ?? '',
                'tarif'           => $_POST['tarif']           ?? 0,
                'experience'      => $_POST['experience']      ?? 0,
                'adresse_cabinet' => $_POST['adresse_cabinet'] ?? '',
            ]);
        }

        $this->logAction('Création utilisateur', "Utilisateur #$userId créé par admin");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur créé avec succès.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function editUser(int $id): void {
        $this->auth->requireRole('admin');
        $user   = $this->findUserById($id);
        if (!$user) { $this->notFound(); }
        $extras = $this->getUserExtras($id, $user['role']);
        $errors = [];

        $viewPath = __DIR__ . '/../views/backoffice/user_edit.php';
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../views/backoffice/user_form.php';
        }
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function updateUser(int $id): void {
        $this->auth->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=users&action=edit&id=$id");
            exit;
        }

        $user = $this->findUserById($id);
        if (!$user) { $this->notFound(); }

        $data = $this->extractUserFormData(false);
        $errors = [];
        
        // Validation
        if (empty($data['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire.';
        }
        if (empty($data['prenom'])) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est obligatoire.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide.';
        } else {
            $existing = $this->findUserByEmail($data['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
        }
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        
        // Si erreurs, retourner à la vue
        if (!empty($errors)) {
            $extras = $this->getUserExtras($id, $user['role']);
            $viewPath = __DIR__ . '/../views/backoffice/user_edit.php';
            if (!file_exists($viewPath)) {
                $viewPath = __DIR__ . '/../views/backoffice/user_form.php';
            }
            file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
            return;
        }

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $this->updateUserRecord($id, $data);

        if ($data['role'] === 'patient') {
            $this->upsertPatientExtra($id, ['groupe_sanguin' => $_POST['groupe_sanguin'] ?? null]);
        } elseif ($data['role'] === 'medecin') {
            $this->upsertMedecinExtra($id, [
                'specialite'      => $_POST['specialite']      ?? '',
                'numero_ordre'    => $_POST['numero_ordre']    ?? '',
                'tarif'           => $_POST['tarif']           ?? 0,
                'experience'      => $_POST['experience']      ?? 0,
                'adresse_cabinet' => $_POST['adresse_cabinet'] ?? '',
            ]);
        }

        $this->logAction('Modification utilisateur', "Utilisateur #$id modifié par admin");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur mis à jour.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function showUser(int $id): void {
        $this->auth->requireRole('admin');
        $user   = $this->findUserById($id);
        if (!$user) { $this->notFound(); }
        $extras = $this->getUserExtras($id, $user['role']);
        $viewPath = __DIR__ . '/../views/backoffice/user_show.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function deleteUser(int $id): void {
        $this->auth->requireRole('admin');

        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vous ne pouvez pas supprimer votre propre compte.'];
            header('Location: index.php?page=users');
            exit;
        }

        try {
            $db = Database::getInstance()->getConnection();
            // Supprimer TOUTES les données liées (contraintes FK vers users)
            $fkRefs = [
                ['replies', 'user_id'],
                ['reply', 'user_id'],
                ['reclamations', 'patient_id'],
                ['avis', 'patient_id'],
                ['avis', 'medecin_id'],
                ['commandes', 'user_id'],
                ['participations', 'user_id'],
                ['ordonnances', 'patient_id'],
                ['ordonnances', 'medecin_id'],
                ['rendez_vous', 'patient_id'],
                ['rendez_vous', 'medecin_id'],
                ['disponibilites', 'medecin_id'],
                ['articles', 'auteur_id'],
                ['patients', 'user_id'],
                ['medecins', 'user_id'],
            ];
            foreach ($fkRefs as [$table, $col]) {
                try { $db->prepare("DELETE FROM `$table` WHERE `$col` = ?")->execute([$id]); } catch (Exception $ignore) {}
            }
            // Nettoyer aussi patients.medecin_traitant_id (SET NULL)
            try { $db->prepare("UPDATE patients SET medecin_traitant_id = NULL WHERE medecin_traitant_id = ?")->execute([$id]); } catch (Exception $ignore) {}

            $this->deleteUserRecord($id);
            $this->logAction('Suppression utilisateur', "Utilisateur #$id supprimé");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur supprimé avec toutes ses données associées.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
        }
        
        header('Location: index.php?page=users');
        exit;
    }

    // ─────────────────────────────────────────
    //  Helper : extraire + assainir données utilisateur POST
    // ─────────────────────────────────────────
    private function extractUserFormData(bool $requirePassword = true): array {
        $data = [
            'nom'            => trim($_POST['nom']            ?? ''),
            'prenom'         => trim($_POST['prenom']         ?? ''),
            'email'          => trim($_POST['email']          ?? ''),
            'telephone'      => trim($_POST['telephone']      ?? ''),
            'adresse'        => trim($_POST['adresse']        ?? ''),
            'date_naissance' => !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null,
            'role'           => $_POST['role']   ?? 'patient',
            'statut'         => $_POST['statut'] ?? 'actif',
        ];

        if ($requirePassword && !empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        return $data;
    }

    public function toggleStatus(int $id): void {
        $this->auth->requireRole('admin');
        $user = $this->findUserById($id);
        if (!$user) { $this->notFound(); }

        $newStatus = ($user['statut'] === 'actif') ? 'inactif' : 'actif';
        $this->updateUserRecord($id, ['statut' => $newStatus]);
        $this->logAction('Changement statut', "Utilisateur #$id -> $newStatus");
        header('Location: index.php?page=users');
        exit;
    }

    // ─────────────────────────────────────────
    //  Gestion des patients (admin)
    // ─────────────────────────────────────────
    public function listPatients(): void {
        try {
            $filters = $this->getBackofficeListFilters(
                [
                    'created_at' => 'u.created_at',
                    'nom' => 'u.nom',
                    'email' => 'u.email',
                    'telephone' => 'u.telephone',
                    'groupe_sanguin' => 'p.groupe_sanguin',
                    'statut' => 'u.statut',
                ],
                'created_at',
                'desc'
            );
            $result = $this->getFilteredPatients($filters);
            $patients = $result['items'];
            $pagination = $result['pagination'];
        } catch (Exception $e) {
            error_log('Erreur listPatients: ' . $e->getMessage());
            $patients = [];
            $pagination = $this->buildPaginationData(0, 1, 10);
            $filters = $this->getBackofficeListFilters([], 'created_at', 'desc');
        }
        
        $page_title = 'Gestion des patients';
        $current_page = 'patients';
        require __DIR__ . '/../views/backoffice/patients_list.php';
    }

    public function showAddPatient(): void {
        $this->auth->requireRole('admin');
        $errors = [];
        $page_title = 'Ajouter un patient';
        $current_page = 'patients';
        require __DIR__ . '/../views/backoffice/patient_add.php';
    }

    public function addPatient(): void {
        $this->auth->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=patients');
            exit;
        }
        
        $errors = [];
        
        // Validation des données
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $password = $_POST['password'] ?? '';
        $groupe_sanguin = $_POST['groupe_sanguin'] ?? '';
        
        if (empty($nom)) {
            $errors['nom'] = 'Le nom est obligatoire.';
        }
        if (empty($prenom)) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        }
        if (empty($email)) {
            $errors['email'] = 'L\'email est obligatoire.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide.';
        } elseif ($this->findUserByEmail($email)) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        
        // Si erreurs, retourner à la vue
        if (!empty($errors)) {
            $page_title = 'Ajouter un patient';
            $current_page = 'patients';
            require __DIR__ . '/../views/backoffice/patient_add.php';
            return;
        }
        
        try {
            // Créer l'utilisateur
            $userId = $this->createUserRecord([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'adresse' => $adresse,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'patient',
                'statut' => 'actif',
            ]);
            
            // Ajouter les infos patient
            $this->upsertPatientExtra($userId, [
                'groupe_sanguin' => $groupe_sanguin ?? null,
            ]);
            
            $this->logAction('Ajout patient', "Patient #$userId ajouté");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Patient ajouté avec succès.'];
            header('Location: index.php?page=patients');
            exit;
        } catch (Exception $e) {
            error_log('Erreur addPatient: ' . $e->getMessage());
            $errors['enregistrement'] = 'Erreur lors de l\'ajout: ' . htmlspecialchars($e->getMessage());
            $viewPath = __DIR__ . '/../views/backoffice/patient_add.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo "Vue non trouvée: " . $viewPath;
            }
        }
    }

    public function showPatient(int $id): void {
        $this->auth->requireRole('admin');
        $user = $this->findUserById($id);
        if (!$user) { $this->notFound(); }
        $patient = $this->findPatientByUserId($id);
        $viewPath = __DIR__ . '/../views/backoffice/patient_show.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    }

public function editPatient(int $id): void {
    $this->auth->requireRole('admin');
    
    // Récupérer l'utilisateur
    $user = $this->findUserById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    // Récupérer les infos patient
    $patientInfo = $this->findPatientByUserId($id);
    
    // Fusionner les données
    $patient = [
        'id' => $user['id'],
        'nom' => $user['nom'] ?? '',
        'prenom' => $user['prenom'] ?? '',
        'email' => $user['email'] ?? '',
        'telephone' => $user['telephone'] ?? '',
        'adresse' => $user['adresse'] ?? '',
        'statut' => $user['statut'] ?? 'actif',
        'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
        'groupe_sanguin' => $patientInfo['groupe_sanguin'] ?? '',
    ];
    
    $errors = [];
    
    $viewPath = __DIR__ . '/../views/backoffice/patient_edit.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo "Vue non trouvée: " . $viewPath;
    }
}

public function updatePatient(int $id): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?page=patients&action=edit&id=$id");
        exit;
    }
    
    // Vérifier si l'utilisateur existe
    $user = $this->findUserById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    $errors = [];
    
    // Validation des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $password = $_POST['password'] ?? '';
    $statut = $_POST['statut'] ?? 'actif';
    $groupe_sanguin = $_POST['groupe_sanguin'] ?? '';
    
    if (empty($nom)) {
        $errors['nom'] = 'Le nom est obligatoire.';
    }
    if (empty($prenom)) {
        $errors['prenom'] = 'Le prénom est obligatoire.';
    }
    if (empty($email)) {
        $errors['email'] = 'L\'email est obligatoire.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'email n\'est pas valide.';
    } else {
        $existing = $this->findUserByEmail($email);
        if ($existing && (int)$existing['id'] !== $id) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
    }
    if (!empty($password) && strlen($password) < 6) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }
    
    // Si erreurs, retourner à la vue
    if (!empty($errors)) {
        // Récupérer les infos patient pour la vue
        $patientInfo = $this->findPatientByUserId($id);
        $patient = [
            'id' => $user['id'],
            'nom' => $user['nom'] ?? '',
            'prenom' => $user['prenom'] ?? '',
            'email' => $user['email'] ?? '',
            'telephone' => $user['telephone'] ?? '',
            'adresse' => $user['adresse'] ?? '',
            'statut' => $user['statut'] ?? 'actif',
            'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            'groupe_sanguin' => $patientInfo['groupe_sanguin'] ?? '',
        ];
        
        $viewPath = __DIR__ . '/../views/backoffice/patient_edit.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
        return;
    }
    
    try {
        // Mettre à jour l'utilisateur
        $userData = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'adresse' => $adresse,
            'statut' => $statut,
        ];
        
        // Mettre à jour le mot de passe si fourni
        if (!empty($password)) {
            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $this->updateUserRecord($id, $userData);
        
        // Mettre à jour les infos patient
        $this->updatePatientRecord($id, [
            'groupe_sanguin' => $groupe_sanguin ?? null,
        ]);
        
        $this->logAction('Modification patient', "Patient #$id modifié");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Patient mis à jour avec succès.'];
        header('Location: index.php?page=patients');
        exit;
    } catch (Exception $e) {
        error_log('Erreur updatePatient: ' . $e->getMessage());
        $errors['enregistrement'] = 'Erreur lors de la mise à jour: ' . htmlspecialchars($e->getMessage());
        
        // Récupérer les infos patient pour la vue
        $patientInfo = $this->findPatientByUserId($id);
        $patient = [
            'id' => $user['id'],
            'nom' => $user['nom'] ?? '',
            'prenom' => $user['prenom'] ?? '',
            'email' => $user['email'] ?? '',
            'telephone' => $user['telephone'] ?? '',
            'adresse' => $user['adresse'] ?? '',
            'statut' => $user['statut'] ?? 'actif',
            'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            'groupe_sanguin' => $patientInfo['groupe_sanguin'] ?? '',
        ];
        
        $viewPath = __DIR__ . '/../views/backoffice/patient_edit.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    }
}

    public function deletePatient(int $id): void {
        $this->auth->requireRole('admin');
        
        try {
            $db = Database::getInstance()->getConnection();
            $fkRefs = [
                ['replies', 'user_id'], ['reply', 'user_id'],
                ['reclamations', 'patient_id'], ['avis', 'patient_id'],
                ['commandes', 'user_id'], ['participations', 'user_id'],
                ['ordonnances', 'patient_id'], ['rendez_vous', 'patient_id'],
                ['articles', 'auteur_id'], ['patients', 'user_id'],
            ];
            foreach ($fkRefs as [$table, $col]) {
                try { $db->prepare("DELETE FROM `$table` WHERE `$col` = ?")->execute([$id]); } catch (Exception $ignore) {}
            }
            $this->deleteUserRecord($id);
            $this->logAction('Suppression patient', "Patient #$id supprimé");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Patient supprimé avec ses données associées.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
        }
        
        header('Location: index.php?page=patients');
        exit;
    }

    // ─────────────────────────────────────────
    //  Gestion des médecins
    // ─────────────────────────────────────────
    public function listMedecins(): void {
        try {
            $filters = $this->getBackofficeListFilters(
                [
                    'created_at' => 'u.created_at',
                    'nom' => 'u.nom',
                    'email' => 'u.email',
                    'telephone' => 'u.telephone',
                    'specialite' => 'm.specialite',
                    'consultation_prix' => 'm.consultation_prix',
                    'statut' => 'u.statut',
                ],
                'created_at',
                'desc'
            );
            $result = $this->getFilteredMedecins($filters);
            $medecins = $result['items'];
            $pagination = $result['pagination'];
        } catch (Exception $e) {
            error_log('Erreur listMedecins: ' . $e->getMessage());
            $medecins = [];
            $pagination = $this->buildPaginationData(0, 1, 10);
            $filters = $this->getBackofficeListFilters([], 'created_at', 'desc');
        }
        
        $page_title = 'Gestion des médecins';
        $current_page = 'medecins_admin';
        require __DIR__ . '/../views/backoffice/medecins_list.php';
    }

    public function showAddMedecin(): void {
        $this->auth->requireRole('admin');
        $errors = [];
        $page_title = 'Ajouter un médecin';
        $current_page = 'medecins_admin';
        require __DIR__ . '/../views/backoffice/medecin_add.php';
    }

    public function addMedecin(): void {
        $this->auth->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=medecins_admin');
            exit;
        }
        
        try {
            // Vérifier si l'email existe déjà
            if ($this->findUserByEmail($_POST['email'])) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
                header('Location: index.php?page=medecins_admin&action=add');
                exit;
            }
            
            // Créer l'utilisateur
            $userId = $this->createUserRecord([
                'nom' => trim($_POST['nom']),
                'prenom' => trim($_POST['prenom']),
                'email' => trim($_POST['email']),
                'telephone' => trim($_POST['telephone'] ?? ''),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'role' => 'medecin',
                'statut' => 'actif',
            ]);
            
            // Ajouter les infos médecin
            $this->upsertMedecinExtra($userId, [
                'specialite' => $_POST['specialite'] ?? '',
                'numero_ordre' => $_POST['numero_ordre'] ?? '',
                'tarif' => $_POST['consultation_prix'] ?? 0,
                'experience' => $_POST['annee_experience'] ?? 0,
                'adresse_cabinet' => $_POST['cabinet_adresse'] ?? '',
            ]);
            
            $this->logAction('Ajout médecin', "Médecin #$userId ajouté");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Médecin ajouté avec succès.'];
            header('Location: index.php?page=medecins_admin');
            exit;
        } catch (Exception $e) {
            error_log('Erreur addMedecin: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()];
            header('Location: index.php?page=medecins_admin&action=add');
            exit;
        }
    }

public function showMedecin(int $id): void {
    $this->auth->requireRole('admin');
    
    // Récupérer l'utilisateur
    $user = $this->findUserById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    // Récupérer les infos médecin
    $medecinInfo = $this->findMedecinByUserId($id);
    
    // Fusionner les données
    $medecin = [
        'id' => $user['id'],
        'nom' => $user['nom'] ?? '',
        'prenom' => $user['prenom'] ?? '',
        'email' => $user['email'] ?? '',
        'telephone' => $user['telephone'] ?? '',
        'statut' => $user['statut'] ?? 'actif',
        'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
        'specialite' => $medecinInfo['specialite'] ?? '',
        'numero_ordre' => $medecinInfo['numero_ordre'] ?? '',
        'annee_experience' => $medecinInfo['annee_experience'] ?? 0,
        'consultation_prix' => $medecinInfo['consultation_prix'] ?? '',
        'cabinet_adresse' => $medecinInfo['cabinet_adresse'] ?? '',
    ];
    
    $viewPath = __DIR__ . '/../views/backoffice/medecin_show.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo "Vue non trouvée: " . $viewPath;
    }
}

public function editMedecin(int $id): void {
    $this->auth->requireRole('admin');
    
    // Récupérer l'utilisateur
    $user = $this->findUserById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    // Récupérer les infos médecin
    $medecin = $this->findMedecinByUserId($id);
    
    // Fusionner les données
    if ($medecin && is_array($medecin)) {
        $medecinData = array_merge($user, $medecin);
    } else {
        $medecinData = $user;
        // Ajouter des valeurs par défaut pour les champs médecins
        $medecinData['specialite'] = '';
        $medecinData['numero_ordre'] = '';
        $medecinData['annee_experience'] = '';
        $medecinData['consultation_prix'] = '';
        $medecinData['cabinet_adresse'] = '';
    }
    
    // Passer la variable à la vue
    $medecin = $medecinData;
    $errors = [];
    
    $viewPath = __DIR__ . '/../views/backoffice/medecin_edit.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo "Vue non trouvée: " . $viewPath;
    }
}

    public function updateMedecin(int $id): void {
        $this->auth->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=medecins_admin&action=edit&id=$id");
            exit;
        }
        
        // Mettre à jour l'utilisateur
        $userData = [
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'email' => trim($_POST['email']),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'statut' => $_POST['statut'] ?? 'actif',
        ];
        
        $errors = [];
        
        // Validation
        if (empty($userData['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire.';
        }
        if (empty($userData['prenom'])) {
            $errors['prenom'] = 'Le prénom est obligatoire.';
        }
        if (empty($userData['email'])) {
            $errors['email'] = 'L\'email est obligatoire.';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide.';
        } else {
            $existing = $this->findUserByEmail($userData['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
        }
        if (empty($_POST['specialite'])) {
            $errors['specialite'] = 'La spécialité est obligatoire.';
        }
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        
        // Si erreurs, retourner à la vue
        if (!empty($errors)) {
            $user = $this->findUserById($id);
            $medecin = $this->findMedecinByUserId($id);
            
            if ($medecin && is_array($medecin)) {
                $medecinData = array_merge($user, $medecin);
            } else {
                $medecinData = $user;
                $medecinData['specialite'] = '';
                $medecinData['numero_ordre'] = '';
                $medecinData['annee_experience'] = '';
                $medecinData['consultation_prix'] = '';
                $medecinData['cabinet_adresse'] = '';
            }
            
            $medecin = $medecinData;
            
            $viewPath = __DIR__ . '/../views/backoffice/medecin_edit.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo "Vue non trouvée: " . $viewPath;
            }
            return;
        }
        
        if (!empty($_POST['password'])) {
            $userData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $this->updateUserRecord($id, $userData);
        
        // Mettre à jour les infos médecin
        $this->updateMedecinRecord($id, [
            'specialite' => $_POST['specialite'] ?? '',
            'numero_ordre' => $_POST['numero_ordre'] ?? '',
            'cabinet_adresse' => $_POST['cabinet_adresse'] ?? '',
        ]);
        
        $this->logAction('Modification médecin', "Médecin #$id modifié");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Médecin mis à jour.'];
        header('Location: index.php?page=medecins_admin');
        exit;
    }

    public function deleteMedecin(int $id): void {
        $this->auth->requireRole('admin');
        
        try {
            $db = Database::getInstance()->getConnection();
            $fkRefs = [
                ['replies', 'user_id'], ['reply', 'user_id'],
                ['avis', 'medecin_id'], ['commandes', 'user_id'],
                ['participations', 'user_id'], ['ordonnances', 'medecin_id'],
                ['rendez_vous', 'medecin_id'], ['disponibilites', 'medecin_id'],
                ['articles', 'auteur_id'], ['medecins', 'user_id'],
            ];
            foreach ($fkRefs as [$table, $col]) {
                try { $db->prepare("DELETE FROM `$table` WHERE `$col` = ?")->execute([$id]); } catch (Exception $ignore) {}
            }
            // Nettoyer patients.medecin_traitant_id
            try { $db->prepare("UPDATE patients SET medecin_traitant_id = NULL WHERE medecin_traitant_id = ?")->execute([$id]); } catch (Exception $ignore) {}
            $this->deleteUserRecord($id);
            $this->logAction('Suppression médecin', "Médecin #$id supprimé");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Médecin supprimé avec ses données associées.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
        }
        
        header('Location: index.php?page=medecins_admin');
        exit;
    }

    public function showValidateMedecin(int $medecinId): void {
        $this->auth->requireRole('admin');
        $medecin = $this->findMedecinByUserId($medecinId);
        $user    = $this->findUserById($medecinId);
        if (!$medecin || !$user) { $this->notFound(); }
        $viewPath = __DIR__ . '/../views/backoffice/medecin_validate.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function approveMedecin(int $medecinId): void {
        $this->auth->requireRole('admin');
        $this->updateUserRecord($medecinId, ['statut' => 'actif']);
        $this->validateMedecinRecord($medecinId, 'validé', $_POST['commentaire'] ?? '');
        $this->logAction('Validation médecin', "Médecin #$medecinId validé");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Médecin validé avec succès.'];
        header('Location: index.php?page=medecins_admin');
        exit;
    }

    public function validateMedecin(int $medecinId): void {
        $this->approveMedecin($medecinId);
    }

    public function rejectMedecin(int $medecinId): void {
        $this->auth->requireRole('admin');
        $this->updateUserRecord($medecinId, ['statut' => 'inactif']);
        $this->validateMedecinRecord($medecinId, 'refusé', $_POST['commentaire'] ?? '');
        $this->logAction('Refus médecin', "Médecin #$medecinId refusé");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Demande refusée.'];
        header('Location: index.php?page=medecins_admin');
        exit;
    }

    // ─────────────────────────────────────────
    //  Rendez-vous (admin)
    // ─────────────────────────────────────────
public function listRendezVous(): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Requête de base
        $sql = "
            SELECT rv.*,
                   u_patient.prenom AS patient_prenom, u_patient.nom AS patient_nom,
                   u_patient.email AS patient_email, u_patient.telephone AS patient_telephone,
                   u_medecin.prenom AS medecin_prenom, u_medecin.nom AS medecin_nom,
                   u_medecin.email AS medecin_email,
                   m.specialite
            FROM rendez_vous rv
            JOIN users u_patient ON rv.patient_id = u_patient.id
            JOIN users u_medecin ON rv.medecin_id = u_medecin.id
            LEFT JOIN medecins m ON rv.medecin_id = m.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Filtre par date
        if (!empty($_GET['date'])) {
            $sql .= " AND DATE(rv.date_rendezvous) = :date";
            $params[':date'] = $_GET['date'];
        }
        
        // Filtre par statut
        if (!empty($_GET['statut'])) {
            $sql .= " AND rv.statut = :statut";
            $params[':statut'] = $_GET['statut'];
        }
        
        // Recherche par patient ou médecin
        if (!empty($_GET['search'])) {
            $sql .= " AND (u_patient.nom LIKE :search OR u_patient.prenom LIKE :search 
                       OR u_medecin.nom LIKE :search OR u_medecin.prenom LIKE :search)";
            $params[':search'] = '%' . $_GET['search'] . '%';
        }
        
        $sql .= " ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous ASC";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculer les statistiques
        $stats = [
            'total' => count($rdvs),
            'en_attente' => count(array_filter($rdvs, fn($r) => $r['statut'] === 'en_attente')),
            'confirmes' => count(array_filter($rdvs, fn($r) => $r['statut'] === 'confirmé')),
            'termines' => count(array_filter($rdvs, fn($r) => $r['statut'] === 'terminé')),
        ];
        
    } catch (Exception $e) {
        error_log('Erreur listRendezVous: ' . $e->getMessage());
        $rdvs = [];
        $stats = ['total' => 0, 'en_attente' => 0, 'confirmes' => 0, 'termines' => 0];
    }
    
    // Inclure la vue
    $viewPath = __DIR__ . '/../views/backoffice/rendezvous/list.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        // Fallback si la vue n'existe pas
        echo "Vue non trouvée: " . $viewPath;
        echo "<pre>Liste des rendez-vous: " . print_r($rdvs, true) . "</pre>";
    }
}

public function viewRendezVous(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get RDV with all details
        $stmt = $db->prepare("
            SELECT rv.*,
                   u_patient.prenom AS patient_prenom, u_patient.nom AS patient_nom, 
                   u_patient.email AS patient_email, u_patient.telephone AS patient_telephone,
                   u_medecin.prenom AS medecin_prenom, u_medecin.nom AS medecin_nom,
                   u_medecin.email AS medecin_email,
                   m.specialite
            FROM rendez_vous rv
            JOIN users u_patient ON rv.patient_id = u_patient.id
            JOIN users u_medecin ON rv.medecin_id = u_medecin.id
            LEFT JOIN medecins m ON rv.medecin_id = m.user_id
            WHERE rv.id = ?
        ");
        $stmt->execute([$id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rdv) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=admin_rendezvous');
            exit;
        }
        
        // Get comments
        $commentsStmt = $db->prepare("
            SELECT ec.*, u.nom, u.prenom, 
                   CONCAT(u.prenom, ' ', u.nom) as user_name
            FROM event_comments ec 
            LEFT JOIN users u ON ec.user_id = u.id 
            WHERE ec.event_id = ? AND ec.status = 'approuvé'
            ORDER BY ec.created_at DESC
        ");
        $commentsStmt->execute([$id]);
        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
        $commentCount = count($comments);
        
        $viewPath = __DIR__ . '/../views/backoffice/rendezvous_detail.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(404);
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
        header('Location: index.php?page=admin_rendezvous');
        exit;
    }
}

public function addCommentRendezVous(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verify RDV exists
        $rdvStmt = $db->prepare("SELECT id FROM rendez_vous WHERE id = ?");
        $rdvStmt->execute([$id]);
        if (!$rdvStmt->fetch()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header("Location: index.php?page=admin_rendezvous&action=view&id={$id}");
            exit;
        }
        
        // Get comment
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($comment) || strlen($comment) < 3) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le commentaire doit faire au moins 3 caractères.'];
            header("Location: index.php?page=admin_rendezvous&action=view&id={$id}");
            exit;
        }
        
        // Insert comment
        $insertStmt = $db->prepare("
            INSERT INTO event_comments (event_id, user_id, comment, status) 
            VALUES (?, ?, ?, 'approuvé')
        ");
        $insertStmt->execute([
            $id,
            $_SESSION['user_id'] ?? 1,
            $comment
        ]);
        
        $this->logAction('Ajout commentaire RDV', "Commentaire ajouté sur le RDV #$id");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commentaire ajouté avec succès !'];
        
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
    }
    
    header("Location: index.php?page=admin_rendezvous&action=view&id={$id}");
    exit;
}


// ─────────────────────────────────────────
//  Gestion des rendez-vous (CRUD complet)
// ─────────────────────────────────────────

/**
 * Afficher le formulaire de création d'un rendez-vous
 */
public function showCreateRendezVous(): void {
    $this->auth->requireRole('admin');
    
    try {
        // Récupérer les patients et médecins
        $db = Database::getInstance()->getConnection();
        
        // Récupérer tous les patients
        $stmt = $db->query("SELECT id, nom, prenom, email FROM users WHERE role = 'patient' ORDER BY nom ASC");
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer tous les médecins
        $stmt = $db->query("
            SELECT u.id, u.nom, u.prenom, u.email, m.specialite 
            FROM users u 
            LEFT JOIN medecins m ON u.id = m.user_id 
            WHERE u.role = 'medecin' 
            ORDER BY u.nom ASC
        ");
        $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les erreurs et anciennes données
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        $flash = $_SESSION['flash'] ?? null;
        
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash']);
        
        $viewPath = __DIR__ . '/../views/backoffice/rendezvous/form.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            // Vue de fallback
            $this->renderRendezVousFormFallback($patients, $medecins, $errors, $old);
        }
    } catch (Exception $e) {
        error_log('Erreur showCreateRendezVous - ' . $e->getMessage());
        $this->setFlash('error', 'Erreur lors du chargement.');
        header('Location: index.php?page=admin_rendezvous');
        exit;
    }
}

/**
 * Créer un rendez-vous
 */
public function createRendezVous(): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=admin_rendezvous&action=create');
        exit;
    }
    
    $errors = [];
    $old = $_POST;
    
    // Validation
    if (empty($_POST['patient_id'])) {
        $errors['patient_id'] = 'Veuillez sélectionner un patient.';
    }
    
    if (empty($_POST['medecin_id'])) {
        $errors['medecin_id'] = 'Veuillez sélectionner un médecin.';
    }
    
    if (empty($_POST['date_rendezvous'])) {
        $errors['date_rendezvous'] = 'Veuillez sélectionner une date.';
    }
    
    if (empty($_POST['heure_rendezvous'])) {
        $errors['heure_rendezvous'] = 'Veuillez sélectionner une heure.';
    }
    
    // S'il y a des erreurs, retourner au formulaire
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header('Location: index.php?page=admin_rendezvous&action=create');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $patient_id = (int)$_POST['patient_id'];
        $medecin_id = (int)$_POST['medecin_id'];
        $date_rendezvous = $_POST['date_rendezvous'];
        $heure_rendezvous = $_POST['heure_rendezvous'];
        $motif = $_POST['motif'] ?? '';
        $statut = $_POST['statut'] ?? 'en_attente';
        
        $sql = "INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut, created_at) 
                VALUES (:patient_id, :medecin_id, :date_rendezvous, :heure_rendezvous, :motif, :statut, NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':patient_id' => $patient_id,
            ':medecin_id' => $medecin_id,
            ':date_rendezvous' => $date_rendezvous,
            ':heure_rendezvous' => $heure_rendezvous,
            ':motif' => $motif,
            ':statut' => $statut
        ]);
        
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous créé avec succès.'];
            header('Location: index.php?page=rendez_vous_admin');
            exit;
        } else {
            throw new Exception('Erreur lors de la création.');
        }
    } catch (Exception $e) {
        error_log('Erreur createRendezVous - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        $_SESSION['old'] = $old;
        header('Location: index.php?page=admin_rendezvous&action=create');
        exit;
    }
}

/**
 * Afficher le formulaire de modification d'un rendez-vous
 */
public function editRendezVous(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Récupérer le rendez-vous
        $stmt = $db->prepare("SELECT * FROM rendez_vous WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $rendezvous = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rendezvous) {
            throw new Exception('Rendez-vous non trouvé.');
        }
        
        // Récupérer les patients et médecins
        $stmt = $db->query("SELECT id, nom, prenom, email FROM users WHERE role = 'patient' ORDER BY nom ASC");
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $db->query("
            SELECT u.id, u.nom, u.prenom, u.email, m.specialite 
            FROM users u 
            LEFT JOIN medecins m ON u.id = m.user_id 
            WHERE u.role = 'medecin' 
            ORDER BY u.nom ASC
        ");
        $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? null;
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash']);
        
        if (!is_array($old)) {
            $old = $rendezvous;
        }
        
        $viewPath = __DIR__ . '/../views/backoffice/rendezvous/form.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée";
        }
    } catch (Exception $e) {
        error_log('Erreur editRendezVous - ' . $e->getMessage());
        $this->setFlash('error', $e->getMessage());
        header('Location: index.php?page=rendez_vous_admin');
        exit;
    }
}

/**
 * Mettre à jour un rendez-vous
 */
public function updateRendezVous(int $id): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?page=admin_rendezvous&action=edit&id=$id");
        exit;
    }
    
    $errors = [];
    $old = $_POST;
    
    if (empty($_POST['patient_id'])) {
        $errors['patient_id'] = 'Veuillez sélectionner un patient.';
    }
    
    if (empty($_POST['medecin_id'])) {
        $errors['medecin_id'] = 'Veuillez sélectionner un médecin.';
    }
    
    if (empty($_POST['date_rendezvous'])) {
        $errors['date_rendezvous'] = 'Veuillez sélectionner une date.';
    }
    
    if (empty($_POST['heure_rendezvous'])) {
        $errors['heure_rendezvous'] = 'Veuillez sélectionner une heure.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header("Location: index.php?page=admin_rendezvous&action=edit&id=$id");
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $patient_id = (int)$_POST['patient_id'];
        $medecin_id = (int)$_POST['medecin_id'];
        $date_rendezvous = $_POST['date_rendezvous'];
        $heure_rendezvous = $_POST['heure_rendezvous'];
        $motif = $_POST['motif'] ?? '';
        $statut = $_POST['statut'] ?? 'en_attente';
        
        $sql = "UPDATE rendez_vous SET 
                    patient_id = :patient_id, 
                    medecin_id = :medecin_id, 
                    date_rendezvous = :date_rendezvous, 
                    heure_rendezvous = :heure_rendezvous, 
                    motif = :motif, 
                    statut = :statut,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':patient_id' => $patient_id,
            ':medecin_id' => $medecin_id,
            ':date_rendezvous' => $date_rendezvous,
            ':heure_rendezvous' => $heure_rendezvous,
            ':motif' => $motif,
            ':statut' => $statut,
            ':id' => $id
        ]);
        
        if ($result) {
            $this->setFlash('success', 'Rendez-vous mis à jour avec succès.');
        } else {
            throw new Exception('Erreur lors de la mise à jour.');
        }
        
        header('Location: index.php?page=rendez_vous_admin');
        exit;
    } catch (Exception $e) {
        error_log('Erreur updateRendezVous - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        $_SESSION['old'] = $old;
        header("Location: index.php?page=admin_rendezvous&action=edit&id=$id");
        exit;
    }
}

/**
 * Afficher les détails d'un rendez-vous
 */
public function showRendezVous(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT rv.*, 
                       CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                       u_patient.email AS patient_email,
                       u_patient.telephone AS patient_telephone,
                       CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                       u_medecin.email AS medecin_email,
                       m.specialite,
                       m.cabinet_adresse
                FROM rendez_vous rv
                JOIN users u_patient ON rv.patient_id = u_patient.id
                JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                LEFT JOIN medecins m ON rv.medecin_id = m.user_id
                WHERE rv.id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $rendezvous = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rendezvous) {
            throw new Exception('Rendez-vous non trouvé.');
        }
        
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        
        $viewPath = __DIR__ . '/../views/backoffice/rendezvous/show.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    } catch (Exception $e) {
        error_log('Erreur showRendezVous - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        header('Location: index.php?page=rendez_vous_admin');
        exit;
    }
}







// ─────────────────────────────────────────
//  Gestion des disponibilités
// ─────────────────────────────────────────
// ─────────────────────────────────────────
//  Gestion des disponibilités (CRUD complet)
// ─────────────────────────────────────────

/**
 * Afficher le formulaire de création d'une disponibilité
 */
public function createDisponibilite(): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=disponibilites_admin&action=create');
        exit;
    }
    
    $errors = [];
    $old = $_POST;
    $old['actif'] = isset($_POST['actif']) ? 1 : 0;
    
    if (empty($_POST['medecin_id'])) {
        $errors['medecin_id'] = 'Veuillez sélectionner un médecin.';
    }
    
    if (empty($_POST['jour_semaine'])) {
        $errors['jour_semaine'] = 'Veuillez sélectionner un jour.';
    }
    
    if (empty($_POST['heure_debut'])) {
        $errors['heure_debut'] = 'Veuillez saisir une heure de début.';
    }
    
    if (empty($_POST['heure_fin'])) {
        $errors['heure_fin'] = 'Veuillez saisir une heure de fin.';
    }
    
    if (!empty($_POST['heure_debut']) && !empty($_POST['heure_fin']) && $_POST['heure_debut'] >= $_POST['heure_fin']) {
        $errors['heure_fin'] = 'L\'heure de fin doit être supérieure à l\'heure de début.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header('Location: index.php?page=disponibilites_admin&action=create');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, pause_debut, pause_fin, actif, created_at, updated_at) 
                VALUES (:medecin_id, :jour_semaine, :heure_debut, :heure_fin, :pause_debut, :pause_fin, :actif, NOW(), NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':medecin_id' => (int)$_POST['medecin_id'],
            ':jour_semaine' => $_POST['jour_semaine'],
            ':heure_debut' => $_POST['heure_debut'],
            ':heure_fin' => $_POST['heure_fin'],
            ':pause_debut' => !empty($_POST['pause_debut']) ? $_POST['pause_debut'] : null,
            ':pause_fin' => !empty($_POST['pause_fin']) ? $_POST['pause_fin'] : null,
            ':actif' => isset($_POST['actif']) ? 1 : 0
        ]);
        
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité créée avec succès.'];
            header('Location: index.php?page=disponibilites_admin');
            exit;
        } else {
            throw new Exception('Erreur lors de la création.');
        }
    } catch (Exception $e) {
        error_log('Erreur createDisponibilite - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        $_SESSION['old'] = $old;
        header('Location: index.php?page=disponibilites_admin&action=create');
        exit;
    }
}

// ─────────────────────────────────────────
//  Gestion des disponibilités (CRUD complet)
// ─────────────────────────────────────────

/**
 * Afficher le formulaire de création d'une disponibilité
 */

/**
 * Créer une disponibilité
 */

/**
 * Afficher le formulaire de modification d'une disponibilité
 */


/**
 * Mettre à jour une disponibilité
 */


/**
 * Supprimer une disponibilité
 */

/**
 * Créer une disponibilité
 */


/**
 * Afficher le formulaire de modification d'une disponibilité
 */
public function editDisponibilite(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM disponibilites WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $disponibilite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$disponibilite) {
            throw new Exception('Disponibilité non trouvée.');
        }
        
        $stmt = $db->query("
            SELECT u.id, u.nom, u.prenom, u.email, m.specialite 
            FROM users u 
            LEFT JOIN medecins m ON u.id = m.user_id 
            WHERE u.role = 'medecin' 
            ORDER BY u.nom ASC
        ");
        $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? null;
        $flash = $_SESSION['flash'] ?? null;
        
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash']);
        
        if (!is_array($old)) {
            $old = $disponibilite;
        }
        
        $viewPath = __DIR__ . '/../views/backoffice/disponibilite/form.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    } catch (Exception $e) {
        error_log('Erreur editDisponibilite - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        header('Location: index.php?page=disponibilites_admin');
        exit;
    }
}

/**
 * Mettre à jour une disponibilité
 */
public function updateDisponibilite(int $id): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?page=disponibilites_admin&action=edit&id=$id");
        exit;
    }
    
    $errors = [];
    $old = $_POST;
    $old['actif'] = isset($_POST['actif']) ? 1 : 0;
    
    if (empty($_POST['medecin_id'])) {
        $errors['medecin_id'] = 'Veuillez sélectionner un médecin.';
    }
    
    if (empty($_POST['jour_semaine'])) {
        $errors['jour_semaine'] = 'Veuillez sélectionner un jour.';
    }
    
    if (empty($_POST['heure_debut'])) {
        $errors['heure_debut'] = 'Veuillez saisir une heure de début.';
    }
    
    if (empty($_POST['heure_fin'])) {
        $errors['heure_fin'] = 'Veuillez saisir une heure de fin.';
    }
    
    if (!empty($_POST['heure_debut']) && !empty($_POST['heure_fin']) && $_POST['heure_debut'] >= $_POST['heure_fin']) {
        $errors['heure_fin'] = 'L\'heure de fin doit être supérieure à l\'heure de début.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header("Location: index.php?page=disponibilites_admin&action=edit&id=$id");
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "UPDATE disponibilites SET 
                    medecin_id = :medecin_id,
                    jour_semaine = :jour_semaine,
                    heure_debut = :heure_debut,
                    heure_fin = :heure_fin,
                    pause_debut = :pause_debut,
                    pause_fin = :pause_fin,
                    actif = :actif,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':medecin_id' => (int)$_POST['medecin_id'],
            ':jour_semaine' => $_POST['jour_semaine'],
            ':heure_debut' => $_POST['heure_debut'],
            ':heure_fin' => $_POST['heure_fin'],
            ':pause_debut' => !empty($_POST['pause_debut']) ? $_POST['pause_debut'] : null,
            ':pause_fin' => !empty($_POST['pause_fin']) ? $_POST['pause_fin'] : null,
            ':actif' => isset($_POST['actif']) ? 1 : 0,
            ':id' => $id
        ]);
        
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité mise à jour avec succès.'];
        } else {
            throw new Exception('Erreur lors de la mise à jour.');
        }
        
        header('Location: index.php?page=disponibilites_admin');
        exit;
    } catch (Exception $e) {
        error_log('Erreur updateDisponibilite - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        $_SESSION['old'] = $old;
        header("Location: index.php?page=disponibilites_admin&action=edit&id=$id");
        exit;
    }
}

/**
 * Supprimer une disponibilité
 */
public function deleteDisponibilite(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("DELETE FROM disponibilites WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité supprimée avec succès.'];
        } else {
            throw new Exception('Erreur lors de la suppression.');
        }
        
        header('Location: index.php?page=disponibilites_admin');
        exit;
    } catch (Exception $e) {
        error_log('Erreur deleteDisponibilite - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        header('Location: index.php?page=disponibilites_admin');
        exit;
    }
}
/**
 * Liste des disponibilités
 */
public function listDisponibilites(): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT d.*, 
                       CONCAT(u.prenom, ' ', u.nom) AS medecin_nom,
                       u.email AS medecin_email,
                       m.specialite
                FROM disponibilites d
                JOIN users u ON d.medecin_id = u.id
                LEFT JOIN medecins m ON d.medecin_id = m.user_id
                ORDER BY FIELD(d.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), d.heure_debut ASC";
        
        $stmt = $db->query($sql);
        $disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $db->query("SELECT u.id, u.nom, u.prenom, u.email, m.specialite FROM users u LEFT JOIN medecins m ON u.id = m.user_id WHERE u.role = 'medecin' ORDER BY u.nom ASC");
        $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total' => count($disponibilites),
            'actives' => count(array_filter($disponibilites, fn($d) => $d['actif'] == 1)),
            'inactives' => count(array_filter($disponibilites, fn($d) => $d['actif'] == 0)),
        ];
        
        $viewPath = __DIR__ . '/../views/backoffice/disponibilite/list.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    } catch (Exception $e) {
        error_log('Erreur listDisponibilites: ' . $e->getMessage());
        $disponibilites = [];
        $medecins = [];
        $stats = ['total' => 0, 'actives' => 0, 'inactives' => 0];
    }
}

public function showCreateDisponibilite(): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("
            SELECT u.id, u.nom, u.prenom, u.email, m.specialite 
            FROM users u 
            LEFT JOIN medecins m ON u.id = m.user_id 
            WHERE u.role = 'medecin' 
            ORDER BY u.nom ASC
        ");
        $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        $flash = $_SESSION['flash'] ?? null;
        
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash']);
        
        $viewPath = __DIR__ . '/../views/backoffice/disponibilite/form.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    } catch (Exception $e) {
        error_log('Erreur showCreateDisponibilite - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors du chargement.'];
        header('Location: index.php?page=disponibilites_admin');
        exit;
    }
}



/**
 * Supprimer un rendez-vous
 */
public function deleteRendezVous(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("DELETE FROM rendez_vous WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            $this->setFlash('success', 'Rendez-vous supprimé avec succès.');
        } else {
            throw new Exception('Erreur lors de la suppression.');
        }
        
        header('Location: index.php?page=rendez_vous_admin');
        exit;
    } catch (Exception $e) {
        error_log('Erreur deleteRendezVous - ' . $e->getMessage());
        $this->setFlash('error', $e->getMessage());
        header('Location: index.php?page=rendez_vous_admin');
        exit;
    }
}

/**
 * Méthode utilitaire pour les messages flash
 */
private function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Enregistrer une action dans les logs
 */
private function logAction(string $action, string $description): void {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO logs (user_id, action, description, ip_address, created_at)
             VALUES (:uid, :action, :desc, :ip, NOW())"
        );
        $stmt->execute([
            ':uid'    => (int)($_SESSION['user_id'] ?? 0),
            ':action' => $action,
            ':desc'   => $description,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    } catch (Exception $e) {
        error_log('Erreur logAction: ' . $e->getMessage());
    }
}

    // ─────────────────────────────────────────
    //  Articles
    // ─────────────────────────────────────────
    /**
     * Afficher une page du backoffice avec le layout
     */
    private function renderAdminPage(string $pageTitle, string $contentFile, string $currentPage = '', array $data = []): void {
        $this->auth->requireRole('admin');
        
        // Extraire les données pour les rendre accessibles à la vue
        extract($data, EXTR_OVERWRITE);
        
        require __DIR__ . '/../views/backoffice/layout.php';
    }

    public function listArticles(): void {
        require_once __DIR__ . '/../models/Article.php';
        
        try {
            // Récupérer tous les articles avec les infos de l'auteur
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT a.id, a.titre, a.contenu, a.status, a.vues, a.created_at, 
                       a.auteur_id, u.nom, u.prenom
                FROM articles a
                LEFT JOIN users u ON a.auteur_id = u.id
                ORDER BY a.created_at DESC
            ");
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur listArticles: ' . $e->getMessage());
            $articles = [];
        }
        
        $page_title = 'Gestion des Articles';
        $current_page = 'articles_admin';
        require __DIR__ . '/../views/backoffice/articles_list.php';
    }

    public function advancedArticles(): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        $articleModel = new ArticleRepository();

        $filtres = [
            'keyword'    => trim($_GET['keyword'] ?? ''),
            'categorie'  => $_GET['categorie'] ?? '',
            'status'     => $_GET['status'] ?? '',
            'auteur_id'  => $_GET['auteur_id'] ?? '',
            'date_min'   => $_GET['date_min'] ?? '',
            'date_max'   => $_GET['date_max'] ?? '',
            'tag'        => trim($_GET['tag'] ?? ''),
            'vues_min'   => $_GET['vues_min'] ?? '',
            'tri'        => $_GET['tri'] ?? 'created_at',
            'ordre'      => $_GET['ordre'] ?? 'DESC',
        ];
        $hasSearch = !empty($filtres['keyword']) || !empty($filtres['categorie'])
                  || !empty($filtres['status']) || !empty($filtres['auteur_id'])
                  || !empty($filtres['date_min']) || !empty($filtres['date_max'])
                  || !empty($filtres['tag']) || !empty($filtres['vues_min']);

        $searchResults = $hasSearch ? $articleModel->advancedSearch($filtres) : [];

        // Stats
        $categories       = $articleModel->getCategories();
        $auteurs           = $articleModel->getAuteurs();
        $statusDistrib     = $articleModel->getStatusDistribution();
        $categoryDistrib   = $articleModel->getCategoryDistribution();
        $topByViews        = $articleModel->getTopByViews(5);
        $topByComments     = $articleModel->getTopByComments(5);
        $monthlyTrend      = $articleModel->getMonthlyTrend(6);
        $totalArticles     = $articleModel->countAll();
        $totalViews        = $articleModel->getTotalViews();
        $totalLikes        = $articleModel->getTotalLikes();
        $thisMonth         = $articleModel->countThisMonth();

        $page_title   = 'Articles — Vue avancée';
        $current_page = 'articles_admin';
        require __DIR__ . '/../views/backoffice/articles_advanced.php';
    }

    public function advancedRendezVous(): void {
        $this->auth->requireRole('admin');
        $db = Database::getInstance()->getConnection();

        $filtres = [
            'keyword'      => trim($_GET['keyword'] ?? ''),
            'medecin_id'   => $_GET['medecin_id'] ?? '',
            'patient_id'   => $_GET['patient_id'] ?? '',
            'statut'       => $_GET['statut'] ?? '',
            'date_min'     => $_GET['date_min'] ?? '',
            'date_max'     => $_GET['date_max'] ?? '',
            'tri'          => $_GET['tri'] ?? 'date_rendezvous',
            'ordre'        => $_GET['ordre'] ?? 'DESC',
        ];

        $hasSearch = !empty($filtres['keyword']) || !empty($filtres['medecin_id']) 
                  || !empty($filtres['patient_id']) || !empty($filtres['statut'])
                  || !empty($filtres['date_min']) || !empty($filtres['date_max']);

        $searchResults = [];
        if ($hasSearch) {
            $query = "SELECT rv.*, u_p.nom as patient_nom, u_p.prenom as patient_prenom, 
                      u_m.nom as medecin_nom, u_m.prenom as medecin_prenom, m.specialite
                      FROM rendez_vous rv
                      JOIN users u_p ON rv.patient_id = u_p.id
                      JOIN users u_m ON rv.medecin_id = u_m.id
                      LEFT JOIN medecins m ON rv.medecin_id = m.user_id
                      WHERE 1=1";
            
            $params = [];
            if (!empty($filtres['keyword'])) {
                $query .= " AND (u_p.nom LIKE ? OR u_p.prenom LIKE ? OR u_m.nom LIKE ? OR u_m.prenom LIKE ? OR rv.motif LIKE ?)";
                $keyword = '%' . $filtres['keyword'] . '%';
                $params = array_merge($params, [$keyword, $keyword, $keyword, $keyword, $keyword]);
            }
            if (!empty($filtres['medecin_id'])) {
                $query .= " AND rv.medecin_id = ?";
                $params[] = $filtres['medecin_id'];
            }
            if (!empty($filtres['patient_id'])) {
                $query .= " AND rv.patient_id = ?";
                $params[] = $filtres['patient_id'];
            }
            if (!empty($filtres['statut'])) {
                $query .= " AND rv.statut = ?";
                $params[] = $filtres['statut'];
            }
            if (!empty($filtres['date_min'])) {
                $query .= " AND DATE(rv.date_rendezvous) >= ?";
                $params[] = $filtres['date_min'];
            }
            if (!empty($filtres['date_max'])) {
                $query .= " AND DATE(rv.date_rendezvous) <= ?";
                $params[] = $filtres['date_max'];
            }

            $query .= " ORDER BY rv." . $filtres['tri'] . " " . $filtres['ordre'];
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Stats
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM rendez_vous");
        $stmt->execute();
        $totalRDV = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->prepare("SELECT statut, COUNT(*) as count FROM rendez_vous GROUP BY statut");
        $stmt->execute();
        $statutDistrib = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT rv.medecin_id, u.nom, u.prenom, COUNT(*) as count FROM rendez_vous rv JOIN users u ON rv.medecin_id = u.id GROUP BY rv.medecin_id ORDER BY count DESC LIMIT 5");
        $stmt->execute();
        $topMedecins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT DATE(date_rendezvous) as date, COUNT(*) as count FROM rendez_vous GROUP BY DATE(date_rendezvous) ORDER BY date DESC LIMIT 6");
        $stmt->execute();
        $recentRDV = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT COUNT(*) as confirmees FROM rendez_vous WHERE statut = 'confirmé'");
        $stmt->execute();
        $confirmedRDV = (int)$stmt->fetch(PDO::FETCH_ASSOC)['confirmees'];

        $stmt = $db->prepare("SELECT COUNT(*) as terminees FROM rendez_vous WHERE statut = 'terminé'");
        $stmt->execute();
        $finishedRDV = (int)$stmt->fetch(PDO::FETCH_ASSOC)['terminees'];

        // Get distinct medecins et patients
        $stmt = $db->prepare("SELECT DISTINCT u.id, u.nom, u.prenom FROM rendez_vous rv JOIN users u ON rv.medecin_id = u.id ORDER BY u.nom");
        $stmt->execute();
        $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT DISTINCT u.id, u.nom, u.prenom FROM rendez_vous rv JOIN users u ON rv.patient_id = u.id ORDER BY u.nom");
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $page_title   = 'Rendez-vous — Vue avancée';
        $current_page = 'rendez_vous_admin';
        require __DIR__ . '/../views/backoffice/rendezvous/advanced.php';
    }

    public function viewArticle(int $id): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        try {
            $articleModel = new ArticleRepository();
            $db = Database::getInstance()->getConnection();
            
            // Get article with author info
            $stmt = $db->prepare("
                SELECT a.*, u.nom, u.prenom 
                FROM articles a 
                LEFT JOIN users u ON a.auteur_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$article) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Article introuvable.'];
                header('Location: index.php?page=articles_admin');
                exit;
            }
            
            // Increment view count
            $updateStmt = $db->prepare("UPDATE articles SET vues = vues + 1 WHERE id = ?");
            $updateStmt->execute([$id]);
            
            // Get approved comments
            $commentsStmt = $db->prepare("
                SELECT r.id, r.replay, r.status, r.created_at, u.nom, u.prenom, 
                       CONCAT(u.prenom, ' ', u.nom) as user_name
                FROM replies r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.article_id = ? AND r.status = 'approuvé'
                ORDER BY r.created_at DESC
            ");
            $commentsStmt->execute([$id]);
            $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
            $commentCount = count($comments);
            
            $viewPath = __DIR__ . '/../views/backoffice/article_detail.php';
            file_exists($viewPath) ? require_once $viewPath : http_response_code(404);
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
            header('Location: index.php?page=articles_admin');
            exit;
        }
    }

    public function addComment(int $id): void {
        $this->auth->requireRole('admin');
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Verify article exists
            $articleStmt = $db->prepare("SELECT id FROM articles WHERE id = ?");
            $articleStmt->execute([$id]);
            if (!$articleStmt->fetch()) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Article introuvable.'];
                header("Location: index.php?page=articles_admin&action=view&id={$id}");
                exit;
            }
            
            // Get comment text
            $comment = trim($_POST['comment'] ?? '');
            
            if (empty($comment) || strlen($comment) < 3) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le commentaire doit faire au moins 3 caractères.'];
                header("Location: index.php?page=articles_admin&action=view&id={$id}");
                exit;
            }
            
            // Insert comment (en_attente by default for moderation)
            $insertStmt = $db->prepare("
                INSERT INTO replies (article_id, user_id, replay, status) 
                VALUES (?, ?, ?, 'approuvé')
            ");
            $insertStmt->execute([
                $id,
                $_SESSION['user_id'] ?? 1,
                $comment
            ]);
            
            $this->logAction('Ajout commentaire', "Commentaire ajouté sur l'article #$id");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commentaire ajouté avec succès !'];
            
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
        }
        
        header("Location: index.php?page=articles_admin&action=view&id={$id}");
        exit;
    }

    public function showCreateArticle(): void {
        $this->auth->requireRole('admin');
        $isEdit  = false;
        $title   = 'Créer un article';
        $article = null;
        $viewPath = __DIR__ . '/../views/backoffice/article_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function createArticle(): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        $titre     = trim($_POST['titre']     ?? '');
        $contenu   = trim($_POST['contenu']   ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $status    = $_POST['status']         ?? 'brouillon';
        $tags      = trim($_POST['tags']      ?? '');

        if (empty($titre) || empty($contenu)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le titre et le contenu sont obligatoires.'];
        } else {
            try {
                $articleModel = new ArticleRepository();
                $newId = $articleModel->create([
                    'titre'     => $titre,
                    'contenu'   => $contenu,
                    'categorie' => $categorie ?: null,
                    'status'    => $status,
                    'tags'      => $tags ?: null,
                    'auteur_id' => $_SESSION['user_id'] ?? 1,
                ]);
                $this->logAction('Création article', "Article #$newId créé - {$titre}");
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article créé avec succès.'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?page=articles_admin');
        exit;
    }

    public function editArticle(int $id): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';

        $articleModel = new ArticleRepository();
        $article = $articleModel->getById($id);
        if (!$article) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Article introuvable.'];
            header('Location: index.php?page=articles_admin');
            exit;
        }
        $isEdit = true;
        $title  = 'Modifier l\'article';
        $viewPath = __DIR__ . '/../views/backoffice/article_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function updateArticle(int $id): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        $titre     = trim($_POST['titre']     ?? '');
        $contenu   = trim($_POST['contenu']   ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $status    = $_POST['status']         ?? 'brouillon';
        $tags      = trim($_POST['tags']      ?? '');

        if (empty($titre) || empty($contenu)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le titre et le contenu sont obligatoires.'];
        } else {
            try {
                $articleModel = new ArticleRepository();
                // updateFull() persists categorie, status, tags — unlike basic update()
                $result = $articleModel->updateFull(
                    $id,
                    $titre,
                    $contenu,
                    $_SESSION['user_id'] ?? null,
                    null,               // image: non géré ici
                    $categorie ?: null,
                    $tags      ?: null,
                    $status
                );
                if ($result) {
                    $this->logAction('Modification article', "Article #$id modifié - {$titre}");
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article mis à jour avec succès.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Aucune modification enregistrée.'];
                }
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?page=articles_admin');
        exit;
    }

    public function deleteArticle(int $id): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        try {
            $articleModel = new ArticleRepository();
            $result = $articleModel->delete($id);
            if($result) {
                $this->logAction('Suppression article', "Article #$id supprimé");
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article supprimé avec succès.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible de supprimer cet article.'];
            }
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
        }
        header('Location: index.php?page=articles_admin');
        exit;
    }

    private function db(): PDO {
        return Database::getInstance()->getConnection();
    }

    private function getAllUsers(int $offset = 0, int $limit = 100): array {
        $stmt = $this->db()->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBackofficeListFilters(array $allowedSorts, string $defaultSort, string $defaultDirection = 'desc'): array {
        $q = trim((string) ($_GET['q'] ?? ''));
        $sort = (string) ($_GET['sort'] ?? $defaultSort);
        $direction = strtolower((string) ($_GET['direction'] ?? $defaultDirection));
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $perPage = 10;

        if (!isset($allowedSorts[$sort])) {
            $sort = $defaultSort;
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return [
            'q' => $q,
            'sort' => $sort,
            'direction' => $direction,
            'sort_sql' => $allowedSorts[$sort] ?? $defaultSort,
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    private function getFilteredUsers(array $filters): array {
        $sql = "SELECT u.* FROM users u";
        $conditions = [];
        $params = [];

        if ($filters['q'] !== '') {
            $searchValue = '%' . $filters['q'] . '%';
            $conditions[] = "(
                u.nom LIKE :q_nom
                OR u.prenom LIKE :q_prenom
                OR u.email LIKE :q_email
                OR u.telephone LIKE :q_telephone
                OR u.role LIKE :q_role
                OR u.statut LIKE :q_statut
            )";
            $params[':q_nom'] = $searchValue;
            $params[':q_prenom'] = $searchValue;
            $params[':q_email'] = $searchValue;
            $params[':q_telephone'] = $searchValue;
            $params[':q_role'] = $searchValue;
            $params[':q_statut'] = $searchValue;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $countSql = "SELECT COUNT(*) FROM users u" . (!empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '');
        $countStmt = $this->db()->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int) $countStmt->fetchColumn();

        $pagination = $this->buildPaginationData($totalItems, $filters['page'], $filters['per_page']);
        $offset = ($pagination['current_page'] - 1) * $filters['per_page'];

        $sql .= " ORDER BY {$filters['sort_sql']} " . strtoupper($filters['direction']) . " LIMIT :limit OFFSET :offset";

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $filters['per_page'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => $pagination,
        ];
    }

    private function getFilteredPatients(array $filters): array {
        $sql = "
            SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.adresse, u.statut, u.created_at,
                   p.groupe_sanguin
            FROM users u
            LEFT JOIN patients p ON u.id = p.user_id
            WHERE u.role = 'patient'
        ";
        $params = [];

        if ($filters['q'] !== '') {
            $searchValue = '%' . $filters['q'] . '%';
            $sql .= " AND (
                u.nom LIKE :q_nom
                OR u.prenom LIKE :q_prenom
                OR u.email LIKE :q_email
                OR u.telephone LIKE :q_telephone
                OR p.groupe_sanguin LIKE :q_groupe
                OR u.statut LIKE :q_statut
            )";
            $params[':q_nom'] = $searchValue;
            $params[':q_prenom'] = $searchValue;
            $params[':q_email'] = $searchValue;
            $params[':q_telephone'] = $searchValue;
            $params[':q_groupe'] = $searchValue;
            $params[':q_statut'] = $searchValue;
        }

        $countSql = "
            SELECT COUNT(*)
            FROM users u
            LEFT JOIN patients p ON u.id = p.user_id
            WHERE u.role = 'patient'
        ";
        if ($filters['q'] !== '') {
            $countSql .= " AND (
                u.nom LIKE :q_nom
                OR u.prenom LIKE :q_prenom
                OR u.email LIKE :q_email
                OR u.telephone LIKE :q_telephone
                OR p.groupe_sanguin LIKE :q_groupe
                OR u.statut LIKE :q_statut
            )";
        }
        $countStmt = $this->db()->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int) $countStmt->fetchColumn();

        $pagination = $this->buildPaginationData($totalItems, $filters['page'], $filters['per_page']);
        $offset = ($pagination['current_page'] - 1) * $filters['per_page'];

        $sql .= " ORDER BY {$filters['sort_sql']} " . strtoupper($filters['direction']) . " LIMIT :limit OFFSET :offset";

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $filters['per_page'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => $pagination,
        ];
    }

    private function getFilteredMedecins(array $filters): array {
        $sql = "
            SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.statut, u.created_at,
                   m.specialite, m.numero_ordre, m.annee_experience, m.consultation_prix, m.cabinet_adresse,
                   m.statut_validation
            FROM users u
            LEFT JOIN medecins m ON u.id = m.user_id
            WHERE u.role = 'medecin'
        ";
        $params = [];

        if ($filters['q'] !== '') {
            $searchValue = '%' . $filters['q'] . '%';
            $sql .= " AND (
                u.nom LIKE :q_nom
                OR u.prenom LIKE :q_prenom
                OR u.email LIKE :q_email
                OR u.telephone LIKE :q_telephone
                OR m.specialite LIKE :q_specialite
                OR u.statut LIKE :q_statut
            )";
            $params[':q_nom'] = $searchValue;
            $params[':q_prenom'] = $searchValue;
            $params[':q_email'] = $searchValue;
            $params[':q_telephone'] = $searchValue;
            $params[':q_specialite'] = $searchValue;
            $params[':q_statut'] = $searchValue;
        }

        $countSql = "
            SELECT COUNT(*)
            FROM users u
            LEFT JOIN medecins m ON u.id = m.user_id
            WHERE u.role = 'medecin'
        ";
        if ($filters['q'] !== '') {
            $countSql .= " AND (
                u.nom LIKE :q_nom
                OR u.prenom LIKE :q_prenom
                OR u.email LIKE :q_email
                OR u.telephone LIKE :q_telephone
                OR m.specialite LIKE :q_specialite
                OR u.statut LIKE :q_statut
            )";
        }
        $countStmt = $this->db()->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int) $countStmt->fetchColumn();

        $pagination = $this->buildPaginationData($totalItems, $filters['page'], $filters['per_page']);
        $offset = ($pagination['current_page'] - 1) * $filters['per_page'];

        $sql .= " ORDER BY {$filters['sort_sql']} " . strtoupper($filters['direction']) . " LIMIT :limit OFFSET :offset";

        $stmt = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $filters['per_page'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => $pagination,
        ];
    }

    private function buildPaginationData(int $totalItems, int $currentPage, int $perPage): array {
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $currentPage), $totalPages);
        $startItem = $totalItems > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $endItem = min($totalItems, $currentPage * $perPage);

        return [
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => max(1, $currentPage - 1),
            'next_page' => min($totalPages, $currentPage + 1),
            'start_item' => $startItem,
            'end_item' => $endItem,
        ];
    }

    private function findUserById(int $id): ?array {
        $stmt = $this->db()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function findUserByEmail(string $email): ?array {
        $stmt = $this->db()->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function createUserRecord(array $data): int {
        $stmt = $this->db()->prepare(
            "INSERT INTO users
                (nom, prenom, email, telephone, password, role, statut, adresse, date_naissance, created_at)
             VALUES
                (:nom, :prenom, :email, :telephone, :password, :role, :statut, :adresse, :date_naissance, NOW())"
        );
        $stmt->execute([
            ':nom' => $data['nom'] ?? '',
            ':prenom' => $data['prenom'] ?? '',
            ':email' => $data['email'] ?? '',
            ':telephone' => $data['telephone'] ?? '',
            ':password' => $data['password'] ?? '',
            ':role' => $data['role'] ?? 'patient',
            ':statut' => $data['statut'] ?? 'actif',
            ':adresse' => $data['adresse'] ?? null,
            ':date_naissance' => $data['date_naissance'] ?? null,
        ]);
        return (int) $this->db()->lastInsertId();
    }

    private function updateUserRecord(int $id, array $data): bool {
        $allowed = ['nom','prenom','email','telephone','password','role','statut','adresse','date_naissance','avatar','face_photo','face_encoding','face_descriptor','derniere_connexion'];
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        if (empty($fields)) {
            return false;
        }
        $stmt = $this->db()->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id");
        return $stmt->execute($params);
    }

    private function deleteUserRecord(int $id): bool {
        $stmt = $this->db()->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    private function getUserExtras(int $userId, string $role): array {
        if ($role === 'patient') {
            $stmt = $this->db()->prepare("SELECT * FROM patients WHERE user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        if ($role === 'medecin') {
            return $this->findMedecinByUserId($userId) ?? [];
        }
        return [];
    }

    private function upsertPatientExtra(int $userId, array $data): void {
        $stmt = $this->db()->prepare(
            "INSERT INTO patients (user_id, groupe_sanguin)
             VALUES (:user_id, :groupe_sanguin)
             ON DUPLICATE KEY UPDATE groupe_sanguin = VALUES(groupe_sanguin)"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
        ]);
    }

    private function findPatientByUserId(int $userId): ?array {
        $stmt = $this->db()->prepare("SELECT * FROM patients WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function updatePatientRecord(int $userId, array $data): bool {
        $allowed = [
            'groupe_sanguin',
            'allergies',
            'medicaments_actuels',
            'antecedents_medicaux',
            'medecin_traitant_id',
            'mutuelle',
            'numero_mutuelle',
            'numero_securite_sociale',
            'urgence_contact_nom',
            'urgence_contact_telephone',
        ];

        $fields = [];
        $params = [':user_id' => $userId];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $exists = $this->findPatientByUserId($userId);
        if ($exists) {
            $stmt = $this->db()->prepare("UPDATE patients SET " . implode(', ', $fields) . " WHERE user_id = :user_id");
            return $stmt->execute($params);
        }

        $stmt = $this->db()->prepare(
            "INSERT INTO patients (user_id, groupe_sanguin)
             VALUES (:user_id, :groupe_sanguin)"
        );
        return $stmt->execute([
            ':user_id' => $userId,
            ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
        ]);
    }

    private function upsertMedecinExtra(int $userId, array $data): void {
        $stmt = $this->db()->prepare(
            "INSERT INTO medecins
                (user_id, specialite, numero_ordre, annee_experience, consultation_prix, cabinet_adresse)
             VALUES
                (:user_id, :specialite, :numero_ordre, :annee_experience, :consultation_prix, :cabinet_adresse)
             ON DUPLICATE KEY UPDATE
                specialite = VALUES(specialite),
                numero_ordre = VALUES(numero_ordre),
                annee_experience = VALUES(annee_experience),
                consultation_prix = VALUES(consultation_prix),
                cabinet_adresse = VALUES(cabinet_adresse)"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':specialite' => $data['specialite'] ?? '',
            ':numero_ordre' => $data['numero_ordre'] ?? '',
            ':annee_experience' => $data['experience'] ?? ($data['annee_experience'] ?? null),
            ':consultation_prix' => $data['tarif'] ?? ($data['consultation_prix'] ?? null),
            ':cabinet_adresse' => $data['adresse_cabinet'] ?? ($data['cabinet_adresse'] ?? ''),
        ]);
    }

    private function getMonthlyRegistrations(): array {
        $stmt = $this->db()->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS total
             FROM users
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mois
             ORDER BY mois ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRepartitionByRole(): array {
        $stmt = $this->db()->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findMedecinByUserId(int $userId): ?array {
        $stmt = $this->db()->prepare(
            "SELECT m.*, u.nom, u.prenom, u.email, u.telephone, u.adresse, u.date_naissance, u.statut, u.created_at
             FROM medecins m
             JOIN users u ON m.user_id = u.id
             WHERE m.user_id = :uid
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function updateMedecinRecord(int $userId, array $data): bool {
        $map = [
            'specialite' => 'specialite',
            'numero_ordre' => 'numero_ordre',
            'annee_experience' => 'annee_experience',
            'experience' => 'annee_experience',
            'consultation_prix' => 'consultation_prix',
            'tarif' => 'consultation_prix',
            'cabinet_adresse' => 'cabinet_adresse',
            'adresse_cabinet' => 'cabinet_adresse',
            'description' => 'description',
            'statut_validation' => 'statut_validation',
            'commentaire_validation' => 'commentaire_validation',
        ];
        $fields = [];
        $params = [':user_id' => $userId];
        foreach ($data as $key => $value) {
            if (!isset($map[$key])) {
                continue;
            }
            $column = $map[$key];
            $placeholder = ':p_' . $column;
            if (!isset($params[$placeholder])) {
                $fields[] = "$column = $placeholder";
            }
            $params[$placeholder] = $value;
        }
        if (empty($fields)) {
            return false;
        }
        $stmt = $this->db()->prepare("UPDATE medecins SET " . implode(', ', $fields) . " WHERE user_id = :user_id");
        return $stmt->execute($params);
    }

    private function validateMedecinRecord(int $userId, string $statutValidation, string $commentaire = ''): bool {
        $stmt = $this->db()->prepare(
            "UPDATE medecins
             SET statut_validation = :statut, commentaire_validation = :commentaire
             WHERE user_id = :user_id"
        );
        return $stmt->execute([
            ':statut' => $statutValidation,
            ':commentaire' => $commentaire,
            ':user_id' => $userId,
        ]);
    }

    private function getTopSpecialities(): array {
        $stmt = $this->db()->query(
            "SELECT specialite, COUNT(*) AS total
             FROM medecins
             GROUP BY specialite
             ORDER BY total DESC
             LIMIT 5"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMonthlyAppointments(): array {
        $stmt = $this->db()->query(
            "SELECT DATE_FORMAT(date_rendezvous, '%Y-%m') AS mois, COUNT(*) AS total
             FROM rendez_vous
             WHERE date_rendezvous >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mois
             ORDER BY mois ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
