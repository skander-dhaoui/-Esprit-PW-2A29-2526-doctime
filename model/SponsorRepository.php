<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Sponsor.php';

/**
 * SponsorRepository — Gère les opérations de base de données pour Sponsor
 * Contient les méthodes CRUD et les requêtes spécialisées
 */
class SponsorRepository {
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
     * Récupère tous les sponsors sous forme d'objets Sponsor
     */
    public function findAll(): array {
        $stmt = $this->getPdo()->query("SELECT * FROM sponsor ORDER BY nom ASC");
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Récupère un sponsor par son ID
     */
    public function findById(int $id): ?Sponsor {
        $stmt = $this->getPdo()->prepare("SELECT * FROM sponsor WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        return $data ? Sponsor::fromArray($data) : null;
    }

    /**
     * Récupère les sponsors d'un niveau donné
     */
    public function findByNiveau(string $niveau): array {
        $stmt = $this->getPdo()->prepare("
            SELECT * FROM sponsor
            WHERE niveau = :niveau
            ORDER BY nom ASC
        ");
        $stmt->execute([':niveau' => $niveau]);
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Récupère les sponsors premium (or et platine)
     */
    public function findPremium(): array {
        $stmt = $this->getPdo()->query("
            SELECT * FROM sponsor
            WHERE niveau IN ('or', 'platine')
            ORDER BY niveau DESC, nom ASC
        ");
        $rows = $stmt->fetchAll();
        return $this->hydrateAll($rows);
    }

    /**
     * Vérifie si un email est déjà utilisé (hors $excludeId)
     */
    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->getPdo()->prepare(
            "SELECT COUNT(*) FROM sponsor WHERE email = :email AND id != :id"
        );
        $stmt->execute([':email' => $email, ':id' => $excludeId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Crée un nouveau sponsor et met à jour son ID
     */
    public function create(Sponsor $sponsor): bool {
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT INTO sponsor (nom, email, telephone, site_web, niveau, montant)
                VALUES (:nom, :email, :telephone, :site_web, :niveau, :montant)
            ");
            $result = $stmt->execute([
                ':nom'       => $sponsor->getNom(),
                ':email'     => $sponsor->getEmail(),
                ':telephone' => $sponsor->getTelephone(),
                ':site_web'  => $sponsor->getSiteWeb(),
                ':niveau'    => $sponsor->getNiveau(),
                ':montant'   => $sponsor->getMontant(),
            ]);

            if ($result) {
                $sponsor->setId((int)$this->getPdo()->lastInsertId());
            }

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Met à jour un sponsor existant
     */
    public function update(Sponsor $sponsor): bool {
        try {
            $stmt = $this->getPdo()->prepare("
                UPDATE sponsor
                SET nom       = :nom,
                    email     = :email,
                    telephone = :telephone,
                    site_web  = :site_web,
                    niveau    = :niveau,
                    montant   = :montant
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id'        => $sponsor->getId(),
                ':nom'       => $sponsor->getNom(),
                ':email'     => $sponsor->getEmail(),
                ':telephone' => $sponsor->getTelephone(),
                ':site_web'  => $sponsor->getSiteWeb(),
                ':niveau'    => $sponsor->getNiveau(),
                ':montant'   => $sponsor->getMontant(),
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Supprime un sponsor par son ID
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->getPdo()->prepare("DELETE FROM sponsor WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES PUBLIQUES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Compte les événements associés à un sponsor
     */
    public function countEvenements(int $id): int {
        $stmt = $this->getPdo()->prepare(
            "SELECT COUNT(*) FROM evenement_sponsor WHERE sponsor_id = :id"
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retourne la somme totale des montants de sponsoring
     */
    public function getTotalMontant(): float {
        $stmt = $this->getPdo()->query("SELECT SUM(montant) AS total FROM sponsor");
        $result = $stmt->fetch();
        return (float)($result['total'] ?? 0);
    }

    /**
     * Retourne la moyenne des montants de sponsoring
     */
    public function getAverageMontant(): float {
        $stmt = $this->getPdo()->query("SELECT AVG(montant) AS average FROM sponsor");
        $result = $stmt->fetch();
        return (float)($result['average'] ?? 0);
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — HYDRATATION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Convertit un tableau de lignes BD en tableau d'objets Sponsor
     */
    private function hydrateAll(array $rows): array {
        $result = [];
        foreach ($rows as $row) {
            $result[] = Sponsor::fromArray($row);
        }
        return $result;
    }
}
