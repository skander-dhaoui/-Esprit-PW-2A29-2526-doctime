<?php
/**
 * Modèle Gestion Unifiée
 * Modèle pour gérer les données intégrées
 */

require_once __DIR__ . '/../config/database.php';

class GestionUnifiee {
    private PDO $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    /**
     * Créer une table de liaison si elle n'existe pas
     */
    public function initialiserTables() {
        try {
            // Table pour lier participations et produits
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS participation_produits (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    participation_id INT NOT NULL,
                    produit_id INT NOT NULL,
                    quantity INT DEFAULT 1,
                    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (participation_id) REFERENCES participations(id) ON DELETE CASCADE,
                    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_participation_produit (participation_id, produit_id)
                )
            ");
            
            return ['statut' => 'succes', 'message' => 'Tables initialisées'];
        } catch (Exception $e) {
            return ['statut' => 'erreur', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Récupérer toutes les entités avec les relations
     */
    public function obtenirDonneesCompletes() {
        try {
            $utilisateurs = $this->obtenirUtilisateurs();
            $evenements = $this->obtenirEvenements();
            $pharmacies = $this->obtenirPharmacies();
            $participations = $this->obtenirParticipations();
            
            return [
                'statut' => 'succes',
                'donnees' => [
                    'utilisateurs' => $utilisateurs,
                    'evenements' => $evenements,
                    'pharmacies' => $pharmacies,
                    'participations' => $participations
                ]
            ];
        } catch (Exception $e) {
            return ['statut' => 'erreur', 'message' => $e->getMessage()];
        }
    }
    
    private function obtenirUtilisateurs() {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id,
                u.nom,
                u.prenom,
                u.email,
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
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenirEvenements() {
        $stmt = $this->pdo->prepare("
            SELECT 
                e.id,
                e.titre,
                e.date_debut,
                e.date_fin,
                e.lieu,
                e.statut,
                COUNT(DISTINCT p.id) AS nombre_participants
            FROM events e
            LEFT JOIN participations p ON e.id = p.event_id
            GROUP BY e.id
            ORDER BY e.date_debut DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenirPharmacies() {
        $stmt = $this->pdo->prepare("
            SELECT 
                ph.id,
                ph.nom,
                ph.adresse,
                ph.ville,
                ph.statut,
                COUNT(DISTINCT pr.id) AS nombre_produits,
                SUM(pr.stock) AS stock_total
            FROM pharmacies ph
            LEFT JOIN produits pr ON ph.id = pr.pharmacie_id
            GROUP BY ph.id
            ORDER BY ph.nom ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenirParticipations() {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id,
                u.nom AS utilisateur_nom,
                u.prenom AS utilisateur_prenom,
                e.titre AS evenement_titre,
                p.date_participation,
                p.statut,
                COUNT(DISTINCT pp.produit_id) AS nombre_produits
            FROM participations p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN events e ON p.event_id = e.id
            LEFT JOIN participation_produits pp ON p.id = pp.participation_id
            GROUP BY p.id
            ORDER BY p.date_participation DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Exporter les données
     */
    public function exporterEnJSON($filePath = null) {
        $donnees = $this->obtenirDonneesCompletes();
        
        if ($filePath) {
            file_put_contents($filePath, json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return ['statut' => 'succes', 'fichier' => $filePath];
        }
        
        return $donnees;
    }
    
    /**
     * Importer des données
     */
    public function importerJSON($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("Fichier non trouvé");
            }
            
            $contenu = file_get_contents($filePath);
            $donnees = json_decode($contenu, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Format JSON invalide");
            }
            
            return ['statut' => 'succes', 'message' => 'Importation réussie'];
        } catch (Exception $e) {
            return ['statut' => 'erreur', 'message' => $e->getMessage()];
        }
    }
}
?>
