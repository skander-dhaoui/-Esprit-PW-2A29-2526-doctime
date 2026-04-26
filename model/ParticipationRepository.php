<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Participation.php';

/**
 * ParticipationRepository — Gère les opérations de base de données pour Participation
 * Contient les méthodes CRUD et les requêtes spécialisées
 */
class ParticipationRepository {
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
     * Récupère toutes les participations sous forme d'objets Participation
     */
    public function findAll(): array {
        $stmt = $this->getPdo()->query("
            SELECT p.* FROM participation p
            ORDER BY p.date_inscription DESC
        ");
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Récupère une participation par son ID
     */
    public function findById(int $id): ?Participation {
        $stmt = $this->getPdo()->prepare("
            SELECT p.* FROM participation p WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data ? Participation::fromArray($data) : null;
    }

    /**
     * Récupère les participations liées à un événement
     */
    public function findByEvenement(int $evenementId): array {
        $stmt = $this->getPdo()->prepare("
            SELECT p.* FROM participation p
            WHERE p.evenement_id = :eid
            ORDER BY p.date_inscription DESC
        ");
        $stmt->execute([':eid' => $evenementId]);
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Récupère les participations d'un email donné
     */
    public function findByEmail(string $email): array {
        $stmt = $this->getPdo()->prepare("
            SELECT p.* FROM participation p
            WHERE p.email = :email
            ORDER BY p.date_inscription DESC
        ");
        $stmt->execute([':email' => $email]);
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Récupère les participations filtrées par statut pour un événement
     */
    public function findByStatut(int $evenementId, string $statut): array {
        $stmt = $this->getPdo()->prepare("
            SELECT p.* FROM participation p
            WHERE p.evenement_id = :eid AND p.statut = :statut
            ORDER BY p.date_inscription DESC
        ");
        $stmt->execute([':eid' => $evenementId, ':statut' => $statut]);
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Vérifie si un email est déjà inscrit à un événement (hors $excludeId)
     */
    public function isAlreadyRegistered(string $email, int $evenementId, int $excludeId = 0): bool {
        $stmt = $this->getPdo()->prepare("
            SELECT COUNT(*) FROM participation
            WHERE email = :email AND evenement_id = :eid AND id != :id
        ");
        $stmt->execute([
            ':email' => $email,
            ':eid'   => $evenementId,
            ':id'    => $excludeId,
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Crée une nouvelle participation et met à jour son ID
     */
    public function create(Participation $participation): bool {
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT INTO participation
                    (nom, prenom, email, telephone, profession, evenement_id, statut)
                VALUES
                    (:nom, :prenom, :email, :telephone, :profession, :evenement_id, :statut)
            ");
            $result = $stmt->execute([
                ':nom'          => $participation->getNom(),
                ':prenom'       => $participation->getPrenom(),
                ':email'        => $participation->getEmail(),
                ':telephone'    => $participation->getTelephone(),
                ':profession'   => $participation->getProfession(),
                ':evenement_id' => $participation->getEvenementId(),
                ':statut'       => $participation->getStatut(),
            ]);

            if ($result) {
                $participation->setId((int)$this->getPdo()->lastInsertId());
            }

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Met à jour une participation existante
     */
    public function update(Participation $participation): bool {
        try {
            $stmt = $this->getPdo()->prepare("
                UPDATE participation
                SET nom          = :nom,
                    prenom       = :prenom,
                    email        = :email,
                    telephone    = :telephone,
                    profession   = :profession,
                    evenement_id = :evenement_id,
                    statut       = :statut
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id'           => $participation->getId(),
                ':nom'          => $participation->getNom(),
                ':prenom'       => $participation->getPrenom(),
                ':email'        => $participation->getEmail(),
                ':telephone'    => $participation->getTelephone(),
                ':profession'   => $participation->getProfession(),
                ':evenement_id' => $participation->getEvenementId(),
                ':statut'       => $participation->getStatut(),
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Supprime une participation par son ID
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->getPdo()->prepare("DELETE FROM participation WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES PUBLIQUES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Compte les participations confirmées pour un événement
     */
    public function countConfirmed(int $evenementId): int {
        $stmt = $this->getPdo()->prepare(
            "SELECT COUNT(*) FROM participation WHERE evenement_id = :eid AND statut = 'confirme'"
        );
        $stmt->execute([':eid' => $evenementId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Compte les participations en attente pour un événement
     */
    public function countPending(int $evenementId): int {
        $stmt = $this->getPdo()->prepare(
            "SELECT COUNT(*) FROM participation WHERE evenement_id = :eid AND statut = 'en_attente'"
        );
        $stmt->execute([':eid' => $evenementId]);
        return (int)$stmt->fetchColumn();
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — HYDRATATION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Convertit un tableau de lignes BD en tableau d'objets Participation
     */
    private function hydrateAll(array $rows): array {
        $result = [];
        foreach ($rows as $row) {
            $result[] = Participation::fromArray($row);
        }
        return $result;
    }
}
