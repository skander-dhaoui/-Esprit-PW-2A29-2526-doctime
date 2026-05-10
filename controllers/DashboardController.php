<?php
require_once __DIR__ . '/../config/database.php';

class DashboardController {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function index(): void {
        // Statistiques globales
        $totalEvenements    = $this->count('events');
        $totalSponsors      = $this->count('sponsors');
        $totalParticipations = $this->count('participations');
        $totalMontant       = $this->pdo->query("SELECT COALESCE(SUM(montant),0) FROM sponsors")->fetchColumn();

        // Données graphique 1 : montant par sponsor (camembert)
        $sponsorsData = $this->pdo->query("
            SELECT nom, montant FROM sponsors ORDER BY montant DESC
        ")->fetchAll();

        // Données graphique 2 : répartition participations par statut (camembert)
        $participStatut = $this->pdo->query("
            SELECT statut, COUNT(*) as total
            FROM participations
            GROUP BY statut
        ")->fetchAll();

        // Données graphique 3 : participations par événement (barres)
        $participEvenement = $this->pdo->query("
            SELECT e.titre, COUNT(p.id) as total
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id
            GROUP BY e.id, e.titre
            ORDER BY total DESC
            LIMIT 8
        ")->fetchAll();

        // Données graphique 4 : montant par niveau de sponsor (barres)
        $montantNiveau = $this->pdo->query("
            SELECT niveau, SUM(montant) as total
            FROM sponsors
            GROUP BY niveau
            ORDER BY total DESC
        ")->fetchAll();

        require __DIR__ . '/../views/backoffice/dashboard.php';
    }

    public function stats(): void {
        $this->index();
    }

    private function count(string $table): int {
        // ========== SÉCURITÉ: Whitelist des tables autorisées ==========
        // PDO ne peut pas paramétrer les noms de tables, donc on utilise une whitelist
        $allowed_tables = [
            'users', 'events', 'sponsors', 'participations', 'articles',
            'patients', 'medecins', 'rendez_vous', 'ordonnances', 'disponibilites',
            'replies', 'categories'
        ];
        
        // Valider que la table est dans la whitelist
        if (!in_array($table, $allowed_tables)) {
            throw new Exception("Table invalide: " . htmlspecialchars($table));
        }
        
        // Requête sécurisée (le nom de table est validé)
        return (int)$this->pdo->query("SELECT COUNT(*) FROM `" . $table . "`")->fetchColumn();
    }
}
