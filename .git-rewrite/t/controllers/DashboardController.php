<?php
require_once __DIR__ . '/../config/database.php';

class DashboardController {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function index(): void {
        // Statistiques globales
        $totalEvenements    = $this->count('evenement');
        $totalSponsors      = $this->count('sponsor');
        $totalParticipations = $this->count('participation');
        $totalMontant       = $this->pdo->query("SELECT COALESCE(SUM(montant),0) FROM sponsor")->fetchColumn();

        // Données graphique 1 : montant par sponsor (camembert)
        $sponsorsData = $this->pdo->query("
            SELECT nom, montant FROM sponsor ORDER BY montant DESC
        ")->fetchAll();

        // Données graphique 2 : répartition participations par statut (camembert)
        $participStatut = $this->pdo->query("
            SELECT statut, COUNT(*) as total
            FROM participation
            GROUP BY statut
        ")->fetchAll();

        // Données graphique 3 : participations par événement (barres)
        $participEvenement = $this->pdo->query("
            SELECT e.titre, COUNT(p.id) as total
            FROM evenement e
            LEFT JOIN participation p ON p.evenement_id = e.id
            GROUP BY e.id, e.titre
            ORDER BY total DESC
            LIMIT 8
        ")->fetchAll();

        // Données graphique 4 : montant par niveau de sponsor (barres)
        $montantNiveau = $this->pdo->query("
            SELECT niveau, SUM(montant) as total
            FROM sponsor
            GROUP BY niveau
            ORDER BY total DESC
        ")->fetchAll();

        require __DIR__ . '/../view/backoffice/dashboard.php';
    }

    public function stats(): void {
        $this->index();
    }

    private function count(string $table): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    }
}
