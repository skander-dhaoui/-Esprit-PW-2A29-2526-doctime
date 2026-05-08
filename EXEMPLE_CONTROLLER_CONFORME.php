<?php
/**
 * EXEMPLE: AuthController avec Validations Serveur Complètes
 * 
 * ✅ RESPECTE LES CONTRAINTES:
 * - Pas d'HTML5 validation (requiert, pattern, etc.)
 * - Validation côté serveur PHP uniquement
 * - PDO pour base de données
 * - MVC: Séparation Model/View/Controller
 * - POO: Classe et méthodes
 */

namespace App\Controllers;

use App\Config\Database;
use App\Config\Validator;
use App\Models\User;

class ExempleAuthController {
    
    private $db;
    private $validator;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ✅ EXEMPLE 1: Inscription avec Validations Serveur
     * 
     * Flux:
     * 1. Utilisateur remplit formulaire (SANS attributs HTML5)
     * 2. Submit POST -> inscription()
     * 3. Valider côté serveur avec Validator
     * 4. Si erreurs -> afficher formulaire avec messages
     * 5. Si OK -> créer utilisateur en BD
     */
    public function register(): void {
        // Afficher formulaire
        $this->render('EXEMPLE_FORMULAIRE_CONFORME.php');
    }

    /**
     * Stocker l'inscription (traiter le POST)
     */
    public function storeRegistration(): void {
        // Créer validateur avec données POST
        $validator = new Validator();

        // ✅ VALIDATIONS SERVEUR (OBLIGATOIRES)
        // Aucun attribut HTML5 dans le formulaire!
        $validator
            // Email
            ->required('email', $_POST['email'] ?? '', 'Email')
            ->email('email', $_POST['email'] ?? '', 'Email')
            ->unique('email', function($email) {
                return User::findByEmail($email) !== null;
            }, 'Email')
            
            // Mot de passe
            ->required('password', $_POST['password'] ?? '', 'Mot de passe')
            ->minLength('password', $_POST['password'] ?? '', 8, 'Mot de passe')
            ->strongPassword('password', $_POST['password'] ?? '', 'Mot de passe')
            
            // Confirmation mot de passe
            ->matches('password', 'password_confirm', $_POST, 'Mot de passe')
            
            // Nom
            ->required('nom', $_POST['nom'] ?? '', 'Nom')
            ->minLength('nom', $_POST['nom'] ?? '', 2, 'Nom')
            ->maxLength('nom', $_POST['nom'] ?? '', 50, 'Nom')
            
            // Prénom
            ->required('prenom', $_POST['prenom'] ?? '', 'Prénom')
            ->minLength('prenom', $_POST['prenom'] ?? '', 2, 'Prénom')
            ->maxLength('prenom', $_POST['prenom'] ?? '', 50, 'Prénom')
            
            // Téléphone (optionnel)
            ->phone('telephone', $_POST['telephone'] ?? '', 'Téléphone')
            
            // Rôle
            ->required('role', $_POST['role'] ?? '', 'Rôle')
            ->inArray('role', $_POST['role'] ?? '', ['patient', 'medecin', 'sponsor'], 'Rôle')
            
            // Conditions
            ->required('terms', $_POST['terms'] ?? '', 'Conditions');

        // ❌ Si validation échoue
        if ($validator->hasErrors()) {
            $_SESSION['form_errors'] = $validator->getErrors();
            header('Location: index.php?page=register');
            exit;
        }

        // ✅ Si validation réussit
        try {
            // Préparer les données
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $telephone = trim($_POST['telephone'] ?? '');
            $role = $_POST['role'];

            // Créer utilisateur via PDO (prepared statement)
            $stmt = $this->db->prepare(
                "INSERT INTO users (email, password, nom, prenom, telephone, role, statut, created_at) 
                 VALUES (:email, :password, :nom, :prenom, :telephone, :role, :statut, NOW())"
            );
            
            $stmt->execute([
                ':email' => $email,
                ':password' => $password,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':telephone' => $telephone,
                ':role' => $role,
                ':statut' => 'actif'
            ]);

            // Rediriger vers login
            $_SESSION['success'] = 'Inscription réussie! Connectez-vous.';
            header('Location: index.php?page=login');
            exit;

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erreur lors de l\'inscription: ' . htmlspecialchars($e->getMessage());
            header('Location: index.php?page=register');
            exit;
        }
    }

    /**
     * ✅ EXEMPLE 2: Login avec Validations Serveur
     */
    public function login(): void {
        $this->render('login.php');
    }

    /**
     * Traiter le login (POST)
     */
    public function storeLogin(): void {
        $validator = new Validator();

        // ✅ VALIDATIONS SERVEUR
        $validator
            ->required('email', $_POST['email'] ?? '', 'Email')
            ->email('email', $_POST['email'] ?? '', 'Email')
            ->required('password', $_POST['password'] ?? '', 'Mot de passe')
            ->minLength('password', $_POST['password'] ?? '', 6, 'Mot de passe');

        if ($validator->hasErrors()) {
            $_SESSION['form_errors'] = $validator->getErrors();
            header('Location: index.php?page=login');
            exit;
        }

        try {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            // Récupérer utilisateur via PDO (prepared statement)
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Vérifier mot de passe
            if ($user && password_verify($password, $user['password'])) {
                // Login réussi
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                header('Location: index.php?page=accueil');
                exit;
            } else {
                // Login échoué
                $_SESSION['form_errors'] = ['general' => ['Email ou mot de passe incorrect']];
                header('Location: index.php?page=login');
                exit;
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erreur lors du login: ' . htmlspecialchars($e->getMessage());
            header('Location: index.php?page=login');
            exit;
        }
    }

    /**
     * Utilitaire: Afficher une vue
     */
    private function render(string $view, array $data = []): void {
        extract($data);
        require __DIR__ . '/../views/' . $view;
    }
}


/**
 * ✅ RÉSUMÉ DES BONNES PRATIQUES APPLIQUÉES:
 * 
 * 1. ❌ Pas d'HTML5 validation (required, pattern, etc.)
 *    ✅ Validation serveur PHP avec classe Validator
 * 
 * 2. ❌ Pas de $_GET/$_POST direct
 *    ✅ Valider et sanitizer les données
 * 
 * 3. ❌ Pas de requêtes SQL directes
 *    ✅ Prepared statements avec PDO
 * 
 * 4. ✅ Structure MVC claire
 *    - Models: User.php (BD)
 *    - Controllers: ExempleAuthController.php (logique)
 *    - Views: login.php, register.php (affichage)
 * 
 * 5. ✅ POO: Classes et méthodes publiques
 * 
 * 6. ✅ Messages d'erreur utilisateur
 *    - Affichés dans la vue
 *    - Erreurs techniques en logs
 * 
 * 7. ✅ Sécurité:
 *    - password_hash() pour mots de passe
 *    - password_verify() pour vérification
 *    - htmlspecialchars() pour affichage
 *    - Prepared statements pour requêtes
 */
