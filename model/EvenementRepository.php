<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Evenement.php';

/**
 * EvenementRepository — Gère les opérations de base de données pour Evenement
 * Contient les méthodes CRUD et les requêtes spécialisées
 */
class EvenementRepository {
    private PDO $pdo;

    // ═══════════════════════════════════════════════════════════════════
    // CONSTRUCTEUR / DESTRUCTEUR
    // ═══════════════════════════════════════════════════════════════════

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function __destruct() {
        // Nettoyage des ressources si nécessaire
    }

    // ═══════════════════════════════════════════════════════════════════
    // GETTER INTERNE
    // ═══════════════════════════════════════════════════════════════════

    private function getPdo(): PDO {
        return $this->pdo;
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES CRUD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Récupère tous les événements sous forme d'objets Evenement
     */
    public function findAll(): array {
        $stmt = $this->getPdo()->query("
            SELECT e.*,
                   GROUP_CONCAT(s.nom SEPARATOR ', ') AS sponsors_nom
            FROM evenement e
            LEFT JOIN evenement_sponsor es ON e.id = es.evenement_id
            LEFT JOIN sponsor s ON es.sponsor_id = s.id
            GROUP BY e.id
            ORDER BY e.date_debut DESC
        ");
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Récupère un événement par son ID
     */
    public function findById(int $id): ?Evenement {
        $stmt = $this->getPdo()->prepare("
            SELECT e.* FROM evenement e WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        $data['sponsors'] = $this->getSponsorsForEvenement($id);
        return Evenement::fromArray($data);
    }

    /**
     * Récupère les événements à venir (planifiés, date_fin future)
     */
    public function findUpcoming(): array {
        $stmt = $this->getPdo()->prepare("
            SELECT e.* FROM evenement e
            WHERE e.date_fin >= CURDATE()
            AND e.statut = 'planifie'
            ORDER BY e.date_debut ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Crée un nouvel événement et met à jour son ID
     */
    public function create(Evenement $evenement): bool {
        $this->getPdo()->beginTransaction();
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT INTO evenement
                    (titre, description, specialite, lieu, date_debut, date_fin, capacite, prix, statut)
                VALUES
                    (:titre, :description, :specialite, :lieu, :date_debut, :date_fin, :capacite, :prix, :statut)
            ");
            $stmt->execute([
                ':titre'       => $evenement->getTitre(),
                ':description' => $evenement->getDescription(),
                ':specialite'  => $evenement->getSpecialite(),
                ':lieu'        => $evenement->getLieu(),
                ':date_debut'  => $evenement->getDateDebut(),
                ':date_fin'    => $evenement->getDateFin(),
                ':capacite'    => $evenement->getCapacite(),
                ':prix'        => $evenement->getPrix(),
                ':statut'      => $evenement->getStatut(),
            ]);

            $newId = (int)$this->getPdo()->lastInsertId();
            $evenement->setId($newId);

            $sponsors = $evenement->getSponsors();
            if (!empty($sponsors)) {
                $this->setSponsorsForEvenement($newId, $sponsors);
            }

            $this->getPdo()->commit();
            return true;
        } catch (Exception $e) {
            $this->getPdo()->rollBack();
            return false;
        }
    }

    /**
     * Met à jour un événement existant
     */
    public function update(Evenement $evenement): bool {
        $this->getPdo()->beginTransaction();
        try {
            $stmt = $this->getPdo()->prepare("
                UPDATE evenement
                SET titre       = :titre,
                    description = :description,
                    specialite  = :specialite,
                    lieu        = :lieu,
                    date_debut  = :date_debut,
                    date_fin    = :date_fin,
                    capacite    = :capacite,
                    prix        = :prix,
                    statut      = :statut
                WHERE id = :id
            ");
            $stmt->execute([
                ':id'          => $evenement->getId(),
                ':titre'       => $evenement->getTitre(),
                ':description' => $evenement->getDescription(),
                ':specialite'  => $evenement->getSpecialite(),
                ':lieu'        => $evenement->getLieu(),
                ':date_debut'  => $evenement->getDateDebut(),
                ':date_fin'    => $evenement->getDateFin(),
                ':capacite'    => $evenement->getCapacite(),
                ':prix'        => $evenement->getPrix(),
                ':statut'      => $evenement->getStatut(),
            ]);

            $this->setSponsorsForEvenement($evenement->getId(), $evenement->getSponsors());

            $this->getPdo()->commit();
            return true;
        } catch (Exception $e) {
            $this->getPdo()->rollBack();
            return false;
        }
    }

    /**
     * Supprime un événement par son ID
     */
    public function delete(int $id): bool {
        $stmt = $this->getPdo()->prepare("DELETE FROM evenement WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES PUBLIQUES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Compte les participations actives (hors annulées) pour un événement
     */
    public function countParticipations(int $id): int {
        $stmt = $this->getPdo()->prepare(
            "SELECT COUNT(*) FROM participation WHERE evenement_id = :id AND statut != 'annule'"
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retourne le nombre de places restantes pour un événement
     */
    public function getPlacesRestantes(int $id): int {
        $evenement = $this->findById($id);
        if (!$evenement) {
            return 0;
        }
        return max(0, $evenement->getCapacite() - $this->countParticipations($id));
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — HYDRATATION ET RELATIONS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Convertit un tableau de lignes BD en tableau d'objets Evenement
     */
    private function hydrateAll(array $rows): array {
        $result = [];
        foreach ($rows as $row) {
            $row['sponsors'] = $this->getSponsorsForEvenement($row['id']);
            $result[] = Evenement::fromArray($row);
        }
        return $result;
    }

    /**
     * Récupère les sponsors liés à un événement
     */
    private function getSponsorsForEvenement(int $evenementId): array {
        $stmt = $this->getPdo()->prepare("
            SELECT s.id, s.nom, s.niveau
            FROM sponsor s
            INNER JOIN evenement_sponsor es ON s.id = es.sponsor_id
            WHERE es.evenement_id = :evenement_id
            ORDER BY s.nom
        ");
        $stmt->execute([':evenement_id' => $evenementId]);
        return $stmt->fetchAll();
    }

    /**
     * Remplace les sponsors d'un événement (supprime puis réinsère)
     */
    private function setSponsorsForEvenement(int $evenementId, array $sponsors): void {
        $stmtDelete = $this->getPdo()->prepare(
            "DELETE FROM evenement_sponsor WHERE evenement_id = :evenement_id"
        );
        $stmtDelete->execute([':evenement_id' => $evenementId]);

        if (empty($sponsors)) {
            return;
        }

        $stmtInsert = $this->getPdo()->prepare(
            "INSERT INTO evenement_sponsor (evenement_id, sponsor_id) VALUES (:evenement_id, :sponsor_id)"
        );
        foreach ($sponsors as $sponsor) {
            $sponsorId = is_array($sponsor) ? ($sponsor['id'] ?? null) : $sponsor;
            if ($sponsorId) {
                $stmtInsert->execute([
                    ':evenement_id' => $evenementId,
                    ':sponsor_id'   => (int)$sponsorId,
                ]);
            }
        }
    }
}
