<?php
if (class_exists('UserController')) return;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Medecin.php';

/**
 * UserController — CORRECTIONS APPLIQUÉES
 * ══════════════════════════════════════════════════════════════════
 *
 * BUG 1 — <br> s'affichent en texte brut dans la vue
 *   CAUSE  : implode('<br>', $errors) produit du HTML, mais la vue
 *            l'enveloppait dans htmlspecialchars() → les balises
 *            étaient échappées et affichées littéralement.
 *   FIX    : Les messages individuels sont échappés AVANT le join,
 *            et le résultat final (qui contient <br>) est affiché
 *            avec echo $error (sans double-échappement).
 *
 * BUG 2 — Champs POST vides lors d'un upload (faux "obligatoire")
 *   CAUSE  : Si enctype="multipart/form-data" est absent du <form>,
 *            PHP ne peuple pas $_POST du tout quand un fichier est joint.
 *            → nom, prenom, email semblaient vides → toutes les
 *            validations échouaient.
 *   FIX    : Vérification défensive ajoutée + commentaire dans la vue.
 *            La vraie correction est dans modifier_profil.php (le form).
 *
 * BUG 3 — Photo non conservée entre soumissions invalides
 *   CAUSE  : La photo était lue depuis la session APRÈS le bloc POST,
 *            donc après une erreur elle revenait à null.
 *   FIX    : On lit l'utilisateur (et sa photo) depuis la BDD AVANT
 *            tout traitement.
 *
 * BUG 4 — Session non mise à jour après modification
 *   CAUSE  : Seuls certains champs de session étaient mis à jour.
 *   FIX    : Mise à jour complète de $_SESSION après updateProfil().
 * ══════════════════════════════════════════════════════════════════
 */
class UserController {

    private User           $userModel;
    private Patient        $patientModel;
    private Medecin        $medecinModel;
    private AuthController $auth;

    // Constantes upload
    private const UPLOAD_DIR    = __DIR__ . '/../uploads/photos/';
    private const UPLOAD_URL    = 'uploads/photos/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE      = 2 * 1024 * 1024; // 2 Mo

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

        $db = Database::getInstance()->getConnection();

        // Récupérer les infos utilisateur
        $userStmt = $db->prepare("SELECT id, nom, prenom, email, telephone, adresse, date_naissance, avatar, role, statut, created_at FROM users WHERE id = :id");
        $userStmt->execute([':id' => $userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        // Récupérer les infos spécifiques au rôle
        if ($userRole === 'patient') {
            $extraStmt = $db->prepare("SELECT groupe_sanguin FROM patients WHERE user_id = :uid LIMIT 1");
            $extraStmt->execute([':uid' => $userId]);
            $extras = $extraStmt->fetch(PDO::FETCH_ASSOC) ?? [];
        } elseif ($userRole === 'medecin') {
            $extraStmt = $db->prepare("SELECT specialite, numero_ordre, cabinet_adresse, description, statut_validation FROM medecins WHERE user_id = :uid LIMIT 1");
            $extraStmt->execute([':uid' => $userId]);
            $extras = $extraStmt->fetch(PDO::FETCH_ASSOC) ?? [];
        } else {
            $extras = [];
        }

        if (!empty($extras)) {
            $user = array_merge($user, $extras);
        }

        $stats = [];
        if ($userRole === 'patient') {
            $statsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as rdv_total,
                    SUM(CASE WHEN statut IN ('en_attente', 'confirmé') AND date_rendezvous >= NOW() THEN 1 ELSE 0 END) as rdv_a_venir
                FROM rendez_vous 
                WHERE patient_id = :pid
            ");
            $statsStmt->execute([':pid' => $userId]);
            $statsData = $statsStmt->fetch(PDO::FETCH_ASSOC);
            $stats = [
                'total_rdv'    => $statsData['rdv_total'] ?? 0,
                'rdv_avenir'   => $statsData['rdv_a_venir'] ?? 0,
                'note_moyenne' => '—',
            ];
        } elseif ($userRole === 'medecin') {
            $statsStmt = $db->prepare("
                SELECT
                    COUNT(*) as rdv_total,
                    SUM(CASE WHEN statut IN ('en_attente', 'confirmé') THEN 1 ELSE 0 END) as rdv_pending,
                    COUNT(DISTINCT patient_id) as patients
                FROM rendez_vous
                WHERE medecin_id = :mid
            ");
            $statsStmt->execute([':mid' => $userId]);
            $statsData = $statsStmt->fetch(PDO::FETCH_ASSOC);
            $stats = [
                'total_rdv'    => $statsData['rdv_total'] ?? 0,
                'rdv_avenir'   => $statsData['rdv_pending'] ?? 0,
                'note_moyenne' => '4.8',
            ];
        }

        // FIX BUG 1 : lecture des messages flash sans double-échappement
        $success         = $_SESSION['success_profil']          ?? null;
        $error           = $_SESSION['error_profil']            ?? null;
        $successPassword = $_SESSION['success_password_profil'] ?? null;
        $errorPassword   = $_SESSION['error_password_profil']   ?? null;
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

    // ─────────────────────────────────────────
    //  Formulaire modification profil
    // ─────────────────────────────────────────
    public function editProfilForm(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? 'patient';

        $user = $this->findUserById($userId);
        if (!$user) {
            $_SESSION['error_profil'] = "Utilisateur non trouvé.";
            header('Location: index.php?page=profil');
            exit;
        }

        $extras = $this->getUserExtras($userId, $userRole);
        if (!empty($extras)) {
            $user = array_merge($user, $extras);
        }

        // FIX BUG 1 : messages flash lus ici, affichés dans la vue sans double-échappement
        $success = $_SESSION['success_profil'] ?? null;
        $error   = $_SESSION['error_profil']   ?? null;
        unset($_SESSION['success_profil'], $_SESSION['error_profil']);

        $viewPath = __DIR__ . '/../views/frontoffice/modifier_profil.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderSimpleEditForm($user, $userRole, $success, $error);
        }
    }

    // ─────────────────────────────────────────
    //  Mise à jour du profil (avec photo)
    // ─────────────────────────────────────────
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

        // FIX BUG 3 : lecture depuis la BDD AVANT tout traitement
        $db = Database::getInstance()->getConnection();
        $userStmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $userStmt->execute([':id' => $userId]);
        $userActuel = $userStmt->fetch(PDO::FETCH_ASSOC);
        $photoActuelle = $userActuel['avatar'] ?? null;
        $photoFinale   = $photoActuelle;

        // ── Champs texte ──────────────────────────────────────────
        $nom            = trim($_POST['nom']            ?? '');
        $prenom         = trim($_POST['prenom']         ?? '');
        $email          = trim($_POST['email']          ?? '');
        $telephone      = trim($_POST['telephone']      ?? '');
        $adresse        = trim($_POST['adresse']        ?? '');
        $date_naissance = $_POST['date_naissance']      ?? null;
        $password       = $_POST['password']            ?? '';
        $confirm        = $_POST['confirm_password']    ?? '';

        // ── Validation ────────────────────────────────────────────
        // FIX BUG 2 : si $_POST est vide alors que des champs devaient exister,
        // c'est que enctype est manquant dans le formulaire HTML (cf. modifier_profil.php).
        // On détecte ce cas et on informe clairement.
        if (empty($_POST) && !empty($_FILES)) {
            $_SESSION['error_profil'] =
                'Erreur de configuration : le formulaire doit avoir enctype="multipart/form-data". '
                . 'Contactez l\'administrateur.';
            header('Location: index.php?page=modifier_profil');
            exit;
        }

        $errors = [];

        if ($nom    === '') $errors[] = 'Le nom est obligatoire.';
        if ($prenom === '') $errors[] = 'Le prénom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if ($password !== '' && $password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';
        if ($password !== '' && strlen($password) < 6)  $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';

        // Email déjà utilisé par un autre compte
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $checkStmt->execute([':email' => $email, ':id' => $userId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $errors[] = 'Cet email est déjà utilisé par un autre compte.';
        }

        if (!empty($errors)) {
            // FIX BUG 1 : on échappe chaque message INDIVIDUELLEMENT,
            // puis on joint avec <br>. La vue affiche $error sans htmlspecialchars().
            $_SESSION['error_profil'] = implode('<br>', array_map('htmlspecialchars', $errors));
            header('Location: index.php?page=modifier_profil');
            exit;
        }

        // ── Gestion photo ─────────────────────────────────────────
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

            $file    = $_FILES['photo'];
            $tmpPath = $file['tmp_name'];
            $mime    = mime_content_type($tmpPath);
            $size    = $file['size'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($mime, self::ALLOWED_TYPES)) {
                $_SESSION['error_profil'] = htmlspecialchars('Format non supporté. Utilisez JPG, PNG, GIF ou WEBP.');
                header('Location: index.php?page=modifier_profil');
                exit;
            }

            if ($size > self::MAX_SIZE) {
                $_SESSION['error_profil'] = 'La photo ne doit pas dépasser 2 Mo.';
                header('Location: index.php?page=modifier_profil');
                exit;
            }

            // Crée le dossier si absent
            if (!is_dir(self::UPLOAD_DIR)) {
                mkdir(self::UPLOAD_DIR, 0755, true);
            }

            // Supprime l'ancienne photo
            if ($photoActuelle && file_exists(self::UPLOAD_DIR . $photoActuelle)) {
                unlink(self::UPLOAD_DIR . $photoActuelle);
            }

            // Sauvegarde la nouvelle
            $newPhoto = uniqid('avatar_', true) . '.' . $ext;
            if (move_uploaded_file($tmpPath, self::UPLOAD_DIR . $newPhoto)) {
                $photoFinale = $newPhoto;
            } else {
                $_SESSION['error_profil'] = "Erreur lors de l'enregistrement de la photo.";
                header('Location: index.php?page=modifier_profil');
                exit;
            }

        } elseif (
            isset($_FILES['photo']['error']) &&
            $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE &&
            $_FILES['photo']['error'] !== UPLOAD_ERR_OK
        ) {
            $phpErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite serveur).',
                UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire).',
                UPLOAD_ERR_PARTIAL    => 'Upload incomplet, réessayez.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
                UPLOAD_ERR_CANT_WRITE => "Impossible d'écrire le fichier.",
                UPLOAD_ERR_EXTENSION  => 'Upload bloqué par une extension PHP.',
            ];
            $code = $_FILES['photo']['error'];
            $_SESSION['error_profil'] = $phpErrors[$code] ?? "Erreur upload (code $code).";
            header('Location: index.php?page=modifier_profil');
            exit;
        }

        // ── Mise à jour BDD ───────────────────────────────────────
        $data = [
            'nom'            => $nom,
            'prenom'         => $prenom,
            'email'          => $email,
            'telephone'      => $telephone,
            'adresse'        => $adresse,
            'date_naissance' => $date_naissance ?: null,
            'avatar'         => $photoFinale,
        ];

        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // Mise à jour utilisateur
        $updateStmt = $db->prepare("
            UPDATE users SET 
                nom = :nom,
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                adresse = :adresse,
                date_naissance = :date_naissance,
                avatar = :avatar
                " . ($password !== '' ? ", password = :password" : "") . "
            WHERE id = :id
        ");
        $execData = [
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'],
            ':adresse' => $data['adresse'],
            ':date_naissance' => $data['date_naissance'],
            ':avatar' => $data['avatar'],
            ':id' => $userId,
        ];
        if ($password !== '') {
            $execData[':password'] = $data['password'];
        }
        $updateStmt->execute($execData);

        // ── Extras selon le rôle ──────────────────────────────────
        if ($userRole === 'patient') {
            $groupeSanguin = $_POST['groupe_sanguin'] ?? null;
            $checkPatientStmt = $db->prepare("SELECT id FROM patients WHERE user_id = :uid");
            $checkPatientStmt->execute([':uid' => $userId]);
            $patientExists = $checkPatientStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($patientExists) {
                $updatePatientStmt = $db->prepare("UPDATE patients SET groupe_sanguin = :gs WHERE user_id = :uid");
                $updatePatientStmt->execute([':gs' => $groupeSanguin, ':uid' => $userId]);
            } else {
                $insertPatientStmt = $db->prepare("INSERT INTO patients (user_id, groupe_sanguin) VALUES (:uid, :gs)");
                $insertPatientStmt->execute([':uid' => $userId, ':gs' => $groupeSanguin]);
            }
        } elseif ($userRole === 'medecin') {
            $updateMedecinStmt = $db->prepare("
                UPDATE medecins SET
                    specialite = :specialite,
                    tarif = :tarif,
                    experience = :experience,
                    cabinet_adresse = :adresse,
                    description = :bio
                WHERE user_id = :uid
            ");
            $updateMedecinStmt->execute([
                ':specialite' => $_POST['specialite'] ?? '',
                ':tarif' => $_POST['tarif'] ?? 0,
                ':experience' => $_POST['experience'] ?? 0,
                ':adresse' => $_POST['adresse_cabinet'] ?? '',
                ':bio' => $_POST['bio'] ?? '',
                ':uid' => $userId,
            ]);
        }

        // FIX BUG 4 : mise à jour COMPLÈTE de la session
        $_SESSION['user_name']         = $prenom . ' ' . $nom;
        $_SESSION['user_email']        = $email;
        $_SESSION['user']['nom']       = $nom;
        $_SESSION['user']['prenom']    = $prenom;
        $_SESSION['user']['email']     = $email;
        $_SESSION['user']['telephone'] = $telephone;
        $_SESSION['user']['adresse']   = $adresse;
        $_SESSION['user']['photo']     = $photoFinale;

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
        $newPassword     = $_POST['new_password']      ?? '';
        $confirmPassword = $_POST['confirm_password']  ?? '';

        $user = $this->findUserById($userId);
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
            $_SESSION['error_password_profil'] = "Le nouveau mot de passe doit être différent de l'ancien.";
            header('Location: index.php?page=profil');
            exit;
        }

        $this->updateUserRecord($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $_SESSION['success_password_profil'] = 'Mot de passe modifié avec succès.';
        header('Location: index.php?page=profil');
        exit;
    }

    // ─────────────────────────────────────────
    //  Avatar (routes dédiées)
    // ─────────────────────────────────────────
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
        $user   = $this->findUserById($userId);
        if (!$user) {
            $_SESSION['error_profil'] = "Utilisateur non trouvé.";
            header('Location: index.php?page=profil');
            exit;
        }

        $result = $this->uploadAvatarFile($_FILES['avatar'], $userId);
        $_SESSION[$result ? 'success_profil' : 'error_profil'] = $result
            ? "Photo de profil mise à jour avec succès."
            : "Erreur lors de l'upload. Vérifiez le format (JPG, PNG, GIF, WEBP) et la taille (max 2 Mo).";

        header('Location: index.php?page=profil');
        exit;
    }

    public function deleteAvatar(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $result = $this->deleteAvatarFile($userId);
        $_SESSION[$result ? 'success_profil' : 'error_profil'] = $result
            ? "Photo de profil supprimée avec succès."
            : "Erreur lors de la suppression.";

        header('Location: index.php?page=profil');
        exit;
    }

    // ─────────────────────────────────────────
    //  CRUD utilisateurs (backoffice admin)
    // ─────────────────────────────────────────
    public function index(): void {
        $this->auth->requireRole('admin');
        $users    = $this->getAllUsers();
        $viewPath = __DIR__ . '/../views/backoffice/users_list.html';
        file_exists($viewPath) ? require_once $viewPath : $this->renderTable($users);
    }

    public function create(): void {
        $this->auth->requireRole('admin');
        $old   = $_SESSION['old']             ?? null;
        $flash = $_SESSION['flash']['message'] ?? null;
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
            // FIX BUG 1 : chaque message est échappé avant le join
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', array_map('htmlspecialchars', $errors))];
            $_SESSION['old']   = $_POST;
            header('Location: index.php?page=users&action=create');
            exit;
        }

        if ($this->findUserByEmail($data['email'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
            $_SESSION['old']   = $_POST;
            header('Location: index.php?page=users&action=create');
            exit;
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId           = $this->createUserRecord($data);
        $this->saveRoleExtras($userId, $data['role']);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur créé avec succès.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function edit(int $id): void {
        $this->auth->requireRole('admin');
        $user = $this->findUserById($id);
        if (!$user) { $this->notFound(); }

        $extra = $this->getUserExtras($id, $user['role']);
        $old   = $_SESSION['old']              ?? null;
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

        $user = $this->findUserById($id);
        if (!$user) { $this->notFound(); }

        $data   = $this->extractFormData(false);
        $errors = $this->validate($data, false);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', array_map('htmlspecialchars', $errors))];
            $_SESSION['old']   = $_POST;
            header("Location: index.php?page=users&action=edit&id=$id");
            exit;
        }

        $existing = $this->findUserByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $id) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
            $_SESSION['old']   = $_POST;
            header("Location: index.php?page=users&action=edit&id=$id");
            exit;
        }

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $this->updateUserRecord($id, $data);
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

        $this->deleteUserRecord($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur supprimé.'];
        header('Location: index.php?page=users');
        exit;
    }

    public function toggleStatus(int $id): void {
        $this->auth->requireRole('admin');
        $user = $this->findUserById($id);
        if (!$user) { $this->notFound(); }

        $newStatus = ($user['statut'] === 'actif') ? 'inactif' : 'actif';
        $this->updateUserRecord($id, ['statut' => $newStatus]);
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
            $this->upsertPatientExtra($userId, [
                'groupe_sanguin' => $_POST['groupe_sanguin'] ?? null,
            ]);
        }

        if ($role === 'medecin') {
            $this->upsertMedecinExtra($userId, [
                'specialite'      => $_POST['specialite']      ?? '',
                'numero_ordre'    => $_POST['numero_ordre']    ?? '',
                'tarif'           => $_POST['tarif']           ?? 0,
                'experience'      => $_POST['experience']      ?? 0,
                'adresse_cabinet' => $_POST['adresse_cabinet'] ?? '',
            ]);
        }
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
            $stmt = $this->db()->prepare("SELECT * FROM medecins WHERE user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
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

    private function uploadAvatarFile(array $file, int $userId): bool {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        if (!in_array($file['type'] ?? '', $allowedTypes, true)) {
            return false;
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return false;
        }
        $uploadDir = dirname(__DIR__) . '/uploads/avatars';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return false;
        }
        $extension = strtolower(pathinfo($file['name'] ?? 'avatar.jpg', PATHINFO_EXTENSION)) ?: 'jpg';
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $absolutePath = $uploadDir . '/' . $filename;
        $relativePath = 'uploads/avatars/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return false;
        }
        return $this->updateUserRecord($userId, ['avatar' => $relativePath]);
    }

    private function deleteAvatarFile(int $userId): bool {
        $user = $this->findUserById($userId);
        if (!$user) {
            return false;
        }
        if (!empty($user['avatar'])) {
            $oldFile = dirname(__DIR__) . '/' . ltrim((string) $user['avatar'], '/');
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }
        return $this->updateUserRecord($userId, ['avatar' => null]);
    }

    private function notFound(): void {
        http_response_code(404);
        die('Utilisateur introuvable.');
    }

    private function renderTable(array $users): void {
        echo '<table border="1"><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th></tr>';
        foreach ($users as $u) {
            echo '<tr>'
               . '<td>' . htmlspecialchars($u['id'])     . '</td>'
               . '<td>' . htmlspecialchars($u['prenom']) . ' ' . htmlspecialchars($u['nom']) . '</td>'
               . '<td>' . htmlspecialchars($u['email'])  . '</td>'
               . '<td>' . htmlspecialchars($u['role'])   . '</td>'
               . '<td>' . htmlspecialchars($u['statut']) . '</td>'
               . '</tr>';
        }
        echo '</table>';
    }

    private function renderSimpleEditForm($user, $userRole, $success, $error): void { ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Modifier mon profil - MediConnect</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="p-4">
            <!--
                FIX BUG 2 : enctype="multipart/form-data" obligatoire.
                Sans lui, dès qu'un fichier est joint, PHP vide $_POST.
            -->
            <form method="POST" action="index.php?page=modifier_profil" enctype="multipart/form-data">

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <!--
                            FIX BUG 1 : $error contient du HTML sûr (<br>)
                            construit par nos soins → pas de htmlspecialchars ici.
                        -->
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control"
                           value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control"
                           value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control"
                           value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Photo (JPG/PNG/GIF/WEBP, 2 Mo max)</label>
                    <input type="file" name="photo" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe (optionnel)</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Laisser vide pour ne pas changer">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="index.php?page=profil" class="btn btn-secondary ms-2">Annuler</a>
            </form>
        </body>
        </html>
    <?php }
}
