<?php
/**
 * API Gestion Unifiée
 * Endpoints pour user+event+pharmacie
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/GestionUnifieeController.php';
require_once __DIR__ . '/../models/GestionUnifiee.php';

header('Content-Type: application/json; charset=utf-8');

$controller = new GestionUnifieeController();
$model = new GestionUnifiee();

$action = $_GET['action'] ?? '';

// Router les requêtes
switch ($action) {
    // Dashboard
    case 'dashboard':
        echo json_encode($controller->tableauBordUnifie());
        break;
    
    // Utilisateurs
    case 'utilisateurs':
        $model->initialiserTables();
        echo json_encode($model->obtenirDonneesCompletes());
        break;
    
    // Événements
    case 'evenements':
        echo json_encode($controller->listerEvenementsAvecParticipations($_GET));
        break;
    
    // Participations
    case 'participations':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($controller->creerParticipationAvecPharmacie(
                $data['user_id'] ?? 0,
                $data['event_id'] ?? 0,
                $data['produits'] ?? []
            ));
        } else {
            $userId = $_GET['user_id'] ?? 0;
            echo json_encode($controller->obtenirParticipationsUtilisateur($userId));
        }
        break;
    
    // Produits
    case 'produits':
        $participationId = $_GET['participation_id'] ?? 0;
        $method = $_GET['method'] ?? 'lister';
        $produitId = $_GET['produit_id'] ?? null;
        
        echo json_encode($controller->gererProduitsParticipation(
            $participationId,
            $method,
            $produitId
        ));
        break;
    
    // Rapport
    case 'rapport':
        $dateDebut = $_GET['debut'] ?? date('Y-01-01');
        $dateFin = $_GET['fin'] ?? date('Y-m-d');
        
        echo json_encode($controller->genererRapportIntegre($dateDebut, $dateFin));
        break;
    
    // Export/Import
    case 'export':
        $format = $_GET['format'] ?? 'json';
        $filePath = __DIR__ . '/../exports/gestion_unifiee_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        echo json_encode($model->exporterEnJSON($filePath));
        break;
    
    case 'import':
        if ($_FILES['file'] ?? false) {
            $tmpFile = $_FILES['file']['tmp_name'];
            echo json_encode($model->importerJSON($tmpFile));
        }
        break;
    
    default:
        echo json_encode([
            'statut' => 'erreur',
            'message' => 'Action non reconnue'
        ]);
}
?>
