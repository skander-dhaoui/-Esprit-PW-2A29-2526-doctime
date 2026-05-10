<?php
/**
 * Routes Gestion Unifiée
 * Intégration user+event+pharmacie
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/GestionUnifieeController.php';

class GestionUnifieeRoutes {
    private $controller;
    
    public function __construct() {
        $this->controller = new GestionUnifieeController();
    }
    
    /**
     * Afficher le tableau de bord unifié
     */
    public function afficherTableauBord() {
        include __DIR__ . '/../views/backoffice/gestion-unifiee.php';
    }
    
    /**
     * GET /gestion-unifiee/dashboard
     */
    public function getDashboard() {
        return $this->controller->tableauBordUnifie();
    }
    
    /**
     * GET /gestion-unifiee/utilisateurs
     */
    public function getUtilisateurs() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("
            SELECT 
                u.id,
                u.nom,
                u.prenom,
                u.email,
                u.telephone,
                u.role,
                u.statut,
                COUNT(DISTINCT p.id) AS nombre_participations,
                COUNT(DISTINCT e.id) AS nombre_evenements
            FROM users u
            LEFT JOIN participations p ON u.id = p.user_id
            LEFT JOIN events e ON p.event_id = e.id
            GROUP BY u.id
            ORDER BY u.nom ASC
        ");
        return [
            'statut' => 'succes',
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }
    
    /**
     * GET /gestion-unifiee/evenements
     */
    public function getEvenements($filtres = []) {
        return $this->controller->listerEvenementsAvecParticipations($filtres);
    }
    
    /**
     * POST /gestion-unifiee/participations
     */
    public function creerParticipation($userId, $eventId, $produits = []) {
        return $this->controller->creerParticipationAvecPharmacie($userId, $eventId, $produits);
    }
    
    /**
     * GET /gestion-unifiee/participations/:id
     */
    public function getParticipations($userId) {
        return $this->controller->obtenirParticipationsUtilisateur($userId);
    }
    
    /**
     * GET /gestion-unifiee/rapport
     */
    public function getRapport($dateDebut = null, $dateFin = null) {
        $dateDebut = $dateDebut ?? date('Y-01-01');
        $dateFin = $dateFin ?? date('Y-m-d');
        
        return $this->controller->genererRapportIntegre($dateDebut, $dateFin);
    }
}

// Singleton
class Router {
    private static $instance = null;
    private $routes = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function register($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }
    
    public function dispatch($method, $path) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return call_user_func($route['callback']);
            }
        }
        return null;
    }
}

// Enregistrer les routes
$router = Router::getInstance();
$routes = new GestionUnifieeRoutes();

$router->register('GET', '/gestion-unifiee', [$routes, 'afficherTableauBord']);
$router->register('GET', '/gestion-unifiee/dashboard', [$routes, 'getDashboard']);
$router->register('GET', '/gestion-unifiee/utilisateurs', [$routes, 'getUtilisateurs']);
$router->register('GET', '/gestion-unifiee/evenements', [$routes, 'getEvenements']);
$router->register('POST', '/gestion-unifiee/participations', [$routes, 'creerParticipation']);
$router->register('GET', '/gestion-unifiee/participations', [$routes, 'getParticipations']);
$router->register('GET', '/gestion-unifiee/rapport', [$routes, 'getRapport']);
?>
