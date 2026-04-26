<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/AuthController.php';

class AdminController {

    private User        $userModel;
    private Medecin     $medecinModel;
    private Patient     $patientModel;
    private Admin       $adminModel;
    private AuthController $auth;

    public function __construct() {
        $this->userModel    = new User();
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

        $stats = [
            'total_users'     => $this->userModel->count(),
            'total_medecins'  => $this->userModel->countByRole('medecin'),
            'total_patients'  => $this->userModel->countByRole('patient'),
            'en_validation'   => $this->userModel->countByStatus('en_attente'),
        ];

        $recentUsers = $this->userModel->getRecent(5);
        $users       = $recentUsers;

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
            'inscriptions_par_mois' => $this->userModel->getMonthlyRegistrations(),
            'repartition_roles'     => $this->userModel->getRepartitionByRole(),
            'top_specialites'       => $this->medecinModel->getTopSpecialities(),
            'rdv_par_mois'          => $this->medecinModel->getMonthlyAppointments(),
        ];

        $viewPath = __DIR__ . '/../views/backoffice/stats.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    // ─────────────────────────────────────────
    //  Gestion des utilisateurs
    // ─────────────────────────────────────────
    public function listUsers(): void {
        $this->auth->requireRole('admin');
        $users    = $this->userModel->getAll();
        $viewPath = __DIR__ . '/../views/backoffice/users_list.php';
        file_exists($viewPath) ? require_once $viewPath : $this->renderUsersTable($users);
    }

    public function showCreateUser(): void {
        $this->auth->requireRole('admin');
        $errors = [];
        $viewPath = __DIR__ . '/../views/backoffice/user_add.php';
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../views/backoffice/user_form.php';
        }
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
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
        } elseif ($this->userModel->findByEmail($data['email'])) {
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
            $viewPath = __DIR__ . '/../views/backoffice/user_add.php';
            if (!file_exists($viewPath)) {
                $viewPath = __DIR__ . '/../views/backoffice/user_form.php';
            }
            file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
            return;
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId = $this->userModel->create($data);

        if ($data['role'] === 'patient') {
            $this->userModel->upsertPatient($userId, ['groupe_sanguin' => $_POST['groupe_sanguin'] ?? null]);
        } elseif ($data['role'] === 'medecin') {
            $this->userModel->upsertMedecin($userId, [
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
        $user   = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); }
        $extras = $this->userModel->getExtras($id, $user['role']);
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

        $user = $this->userModel->findById($id);
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
            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
        }
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        
        // Si erreurs, retourner à la vue
        if (!empty($errors)) {
            $extras = $this->userModel->getExtras($id, $user['role']);
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

        $this->userModel->update($id, $data);

        if ($data['role'] === 'patient') {
            $this->userModel->upsertPatient($id, ['groupe_sanguin' => $_POST['groupe_sanguin'] ?? null]);
        } elseif ($data['role'] === 'medecin') {
            $this->userModel->upsertMedecin($id, [
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
        $user   = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); }
        $extras = $this->userModel->getExtras($id, $user['role']);
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

        $this->userModel->delete($id);
        $this->logAction('Suppression utilisateur', "Utilisateur #$id supprimé");
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
        $this->logAction('Changement statut', "Utilisateur #$id -> $newStatus");
        header('Location: index.php?page=users');
        exit;
    }

    // ─────────────────────────────────────────
    //  Gestion des patients (admin)
    // ─────────────────────────────────────────
    public function listPatients(): void {
        $this->auth->requireRole('admin');
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.adresse, u.statut, u.created_at,
                       p.groupe_sanguin
                FROM users u
                LEFT JOIN patients p ON u.id = p.user_id
                WHERE u.role = 'patient'
                ORDER BY u.created_at DESC
            ");
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur listPatients: ' . $e->getMessage());
            $patients = [];
        }
        
        $viewPath = __DIR__ . '/../views/backoffice/patients_list.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderPatientsTable($patients);
        }
    }

    public function showAddPatient(): void {
        $this->auth->requireRole('admin');
        $errors = [];
        $viewPath = __DIR__ . '/../views/backoffice/patient_add.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
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
        } elseif ($this->userModel->findByEmail($email)) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        
        // Si erreurs, retourner à la vue
        if (!empty($errors)) {
            $viewPath = __DIR__ . '/../views/backoffice/patient_add.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo "Vue non trouvée: " . $viewPath;
            }
            return;
        }
        
        try {
            // Créer l'utilisateur
            $userId = $this->userModel->create([
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
            $this->userModel->upsertPatient($userId, [
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
        $user = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); }
        $patient = $this->patientModel->findByUserId($id);
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
    $user = $this->userModel->findById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    // Récupérer les infos patient
    $patientInfo = $this->patientModel->findByUserId($id);
    
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
    $user = $this->userModel->findById($id);
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
        $existing = $this->userModel->findByEmail($email);
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
        $patientInfo = $this->patientModel->findByUserId($id);
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
        
        $this->userModel->update($id, $userData);
        
        // Mettre à jour les infos patient
        $this->patientModel->update($id, [
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
        $patientInfo = $this->patientModel->findByUserId($id);
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
        $this->userModel->delete($id);
        $this->logAction('Suppression patient', "Patient #$id supprimé");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Patient supprimé.'];
        header('Location: index.php?page=patients');
        exit;
    }

    // ─────────────────────────────────────────
    //  Gestion des médecins
    // ─────────────────────────────────────────
    public function listMedecins(): void {
        $this->auth->requireRole('admin');
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.statut, u.created_at,
                       m.specialite, m.numero_ordre, m.annee_experience, m.consultation_prix, m.cabinet_adresse
                FROM users u
                LEFT JOIN medecins m ON u.id = m.user_id
                WHERE u.role = 'medecin'
                ORDER BY u.created_at DESC
            ");
            $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur listMedecins: ' . $e->getMessage());
            $medecins = [];
        }
        
        $viewPath = __DIR__ . '/../views/backoffice/medecins_list.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderMedecinsTable($medecins);
        }
    }

    public function showAddMedecin(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/medecin_add.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Vue non trouvée: " . $viewPath;
        }
    }

    public function addMedecin(): void {
        $this->auth->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=medecins_admin');
            exit;
        }
        
        try {
            // Vérifier si l'email existe déjà
            if ($this->userModel->findByEmail($_POST['email'])) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
                header('Location: index.php?page=medecins_admin&action=add');
                exit;
            }
            
            // Créer l'utilisateur
            $userId = $this->userModel->create([
                'nom' => trim($_POST['nom']),
                'prenom' => trim($_POST['prenom']),
                'email' => trim($_POST['email']),
                'telephone' => trim($_POST['telephone'] ?? ''),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'role' => 'medecin',
                'statut' => 'actif',
            ]);
            
            // Ajouter les infos médecin
            $this->userModel->upsertMedecin($userId, [
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
    $user = $this->userModel->findById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    // Récupérer les infos médecin
    $medecinInfo = $this->medecinModel->findByUserId($id);
    
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
    $user = $this->userModel->findById($id);
    if (!$user) { 
        $this->notFound(); 
    }
    
    // Récupérer les infos médecin
    $medecin = $this->medecinModel->findByUserId($id);
    
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
            $existing = $this->userModel->findByEmail($userData['email']);
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
            $user = $this->userModel->findById($id);
            $medecin = $this->medecinModel->findByUserId($id);
            
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
        
        $this->userModel->update($id, $userData);
        
        // Mettre à jour les infos médecin
        $this->medecinModel->update($id, [
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
        $this->userModel->delete($id);
        $this->logAction('Suppression médecin', "Médecin #$id supprimé");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Médecin supprimé.'];
        header('Location: index.php?page=medecins_admin');
        exit;
    }

    public function showValidateMedecin(int $medecinId): void {
        $this->auth->requireRole('admin');
        $medecin = $this->medecinModel->findByUserId($medecinId);
        $user    = $this->userModel->findById($medecinId);
        if (!$medecin || !$user) { $this->notFound(); }
        $viewPath = __DIR__ . '/../views/backoffice/medecin_validate.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function approveMedecin(int $medecinId): void {
        $this->auth->requireRole('admin');
        $this->userModel->update($medecinId, ['statut' => 'actif']);
        $this->medecinModel->validate($medecinId, 'validé', $_POST['commentaire'] ?? '');
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
        $this->userModel->update($medecinId, ['statut' => 'inactif']);
        $this->medecinModel->validate($medecinId, 'refusé', $_POST['commentaire'] ?? '');
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




    // ─────────────────────────────────────────
    //  Articles
    // ─────────────────────────────────────────
    public function listArticles(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/articles_list.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function showCreateArticle(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/article_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function createArticle(): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        
        if(empty($titre) || empty($contenu)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Titre et contenu obligatoires.'];
        } else {
            try {
                $articleModel = new Article();
                $newId = $articleModel->create([
                    'titre' => $titre,
                    'contenu' => $contenu,
                    'auteur_id' => $_SESSION['user_id'] ?? 1
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article créé avec succès.'];
            } catch(Exception $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?page=articles_admin');
        exit;
    }

    public function editArticle(int $id): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/article_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function updateArticle(int $id): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        
        if(empty($titre) || empty($contenu)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Titre et contenu obligatoires.'];
        } else {
            try {
                $articleModel = new Article();
                $result = $articleModel->update($id, $titre, $contenu, $_SESSION['user_id']);
                if($result) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article mis à jour avec succès.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur lors de la mise à jour.'];
                }
            } catch(Exception $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur: ' . $e->getMessage()];
            }
        }
        header('Location: index.php?page=articles_admin');
        exit;
    }

    public function deleteArticle(int $id): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../models/Article.php';
        
        try {
            $articleModel = new Article();
            $result = $articleModel->delete($id);
            if($result) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article supprimé avec succès.'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur lors de la suppression.'];
            }
        } catch(Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur: ' . $e->getMessage()];
        }
        header('Location: index.php?page=articles_admin');
        exit;
    }

    // ─────────────────────────────────────────
    //  Événements
    // ─────────────────────────────────────────
    public function listEvents(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/events_list.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function showCreateEvent(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/event_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function createEvent(): void {
        $this->auth->requireRole('admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement créé.'];
        header('Location: index.php?page=evenements_admin');
        exit;
    }

    public function editEvent(int $id): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/event_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function updateEvent(int $id): void {
        $this->auth->requireRole('admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement mis à jour.'];
        header('Location: index.php?page=evenements_admin');
        exit;
    }

    public function deleteEvent(int $id): void {
        $this->auth->requireRole('admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement supprimé.'];
        header('Location: index.php?page=evenements_admin');
        exit;
    }

    // ─────────────────────────────────────────
    //  Produits
    // ─────────────────────────────────────────
    public function listProduits(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/produits_list.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function showCreateProduit(): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/produit_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function createProduit(): void {
        $this->auth->requireRole('admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit créé.'];
        header('Location: index.php?page=produits_admin');
        exit;
    }

    public function editProduit(int $id): void {
        $this->auth->requireRole('admin');
        $viewPath = __DIR__ . '/../views/backoffice/produit_form.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function updateProduit(int $id): void {
        $this->auth->requireRole('admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit mis à jour.'];
        header('Location: index.php?page=produits_admin');
        exit;
    }

    public function deleteProduit(int $id): void {
        $this->auth->requireRole('admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit supprimé.'];
        header('Location: index.php?page=produits_admin');
        exit;
    }

    // ─────────────────────────────────────────
    //  Logs
    // ─────────────────────────────────────────
    public function logs(): void {
        $this->auth->requireRole('admin');
        $logs     = $this->getLogs();
        $viewPath = __DIR__ . '/../views/backoffice/logs.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function exportLogs(): void {
        $this->auth->requireRole('admin');
        $logs = $this->getLogs();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="logs_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Utilisateur', 'Rôle', 'Action', 'Description', 'IP', 'Date']);

        foreach ($logs as $log) {
            fputcsv($out, [
                $log['id'],
                ($log['prenom'] ?? '') . ' ' . ($log['nom'] ?? 'Système'),
                $log['role']        ?? '-',
                $log['action'],
                $log['description'],
                $log['ip_address'],
                $log['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    // ─────────────────────────────────────────
    //  API JSON
    // ─────────────────────────────────────────
    public function apiStats(): void {
        $this->auth->requireRole('admin');

        header('Content-Type: application/json');
        echo json_encode([
            'inscriptions' => $this->userModel->getMonthlyRegistrations(),
            'repartition'  => $this->userModel->getRepartitionByRole(),
            'specialites'  => $this->medecinModel->getTopSpecialities(),
            'rdv'          => $this->medecinModel->getMonthlyAppointments(),
        ]);
        exit;
    }

    // ─────────────────────────────────────────
    //  Paramètres
    // ─────────────────────────────────────────
    public function settings(): void {
        $this->auth->requireRole('admin');
        $settings = $this->adminModel->getAllSettings();
        $viewPath = __DIR__ . '/../views/backoffice/settings.php';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function updateSettings(): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=settings');
            exit;
        }

        $this->adminModel->setSetting('site_name',     trim($_POST['site_name']     ?? 'DocTime'));
        $this->adminModel->setSetting('contact_email', trim($_POST['contact_email'] ?? ''));
        $this->adminModel->setSetting('maintenance',   isset($_POST['maintenance']) ? '1' : '0');

        $this->logAction('Paramètres', 'Paramètres mis à jour par admin');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Paramètres enregistrés.'];
        header('Location: index.php?page=settings');
        exit;
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function getLogs(): array {
        try {
            return $this->adminModel->getLogs(200);
        } catch (Exception $e) {
            return [];
        }
    }

    private function logAction(string $action, string $description): void {
        try {
            $this->adminModel->addLog(
                (int)($_SESSION['user_id'] ?? 0),
                $action,
                $description
            );
        } catch (Exception $e) {}
    }

    private function extractUserFormData(bool $withPassword = true): array {
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

    private function renderMedecinsTable(array $medecins): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Gestion des médecins - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-4">
                <h2>Gestion des médecins</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr><th>ID</th><th>Nom</th><th>Email</th><th>Spécialité</th><th>Statut</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medecins as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= htmlspecialchars($m['specialite'] ?? '-') ?></td>
                            <td><?= $m['statut'] ?></td>
                            <td>
                                <a href="index.php?page=medecins_admin&action=edit&id=<?= $m['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <a href="index.php?page=medecins_admin&action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">Supprimer</a>
                             </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </body>
        </html>
        <?php
    }

    private function renderPatientsTable(array $patients): void {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Gestion des patients - Valorys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-4">
                <h2>Gestion des patients</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr><th>ID</th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Statut</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= htmlspecialchars($p['telephone'] ?? '-') ?></td>
                            <td><?= $p['statut'] ?></td>
                            <td>
                                <a href="index.php?page=patients&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <a href="index.php?page=patients&action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">Supprimer</a>
                             </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </body>
        </html>
        <?php
    }

    private function notFound(): void {
        http_response_code(404);
        die('Ressource introuvable.');
    }

    private function renderFallback(string $title, array $stats, array $users): void {
        echo "<h2>$title</h2>";
        echo "<p>Total users: {$stats['total_users']} | Médecins: {$stats['total_medecins']} | Patients: {$stats['total_patients']}</p>";
    }

    private function renderUsersTable(array $users): void {
        echo '<table border="1"><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th></tr>';
        foreach ($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['prenom']} {$u['nom']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>{$u['statut']}</td></tr>";
        }
        echo '</table>';
    }
}// update
