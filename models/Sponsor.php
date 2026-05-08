<?php
require_once __DIR__ . '/../config/database.php';

class Sponsor {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** Récupère tous les sponsors */
    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM sponsors ORDER BY nom ASC");
        return $stmt->fetchAll();
    }

    /** Récupère un sponsor par son ID */
    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM sponsors WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Vérifie si un email existe déjà (hors l'ID courant pour l'édition) */
    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM sponsors WHERE email = :email AND id != :id"
        );
        $stmt->execute([':email' => $email, ':id' => $excludeId]);
        return $stmt->fetchColumn() > 0;
    }

    /** Crée un nouveau sponsor */
    public function create(array $data): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO sponsors (nom, email, telephone, site_web, niveau, montant)
            VALUES (:nom, :email, :telephone, :site_web, :niveau, :montant)
        ");
        return $stmt->execute([
            ':nom'       => trim($data['nom']),
            ':email'     => trim($data['email']),
            ':telephone' => trim($data['telephone']),
            ':site_web'  => !empty($data['site_web']) ? trim($data['site_web']) : null,
            ':niveau'    => $data['niveau'],
            ':montant'   => $data['montant'],
        ]);
    }

    /** Met à jour un sponsor existant */
    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE sponsors
            SET nom = :nom,
                email = :email,
                telephone = :telephone,
                site_web = :site_web,
                niveau = :niveau,
                montant = :montant
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'        => $id,
            ':nom'       => trim($data['nom']),
            ':email'     => trim($data['email']),
            ':telephone' => trim($data['telephone']),
            ':site_web'  => !empty($data['site_web']) ? trim($data['site_web']) : null,
            ':niveau'    => $data['niveau'],
            ':montant'   => $data['montant'],
        ]);
    }

    /** Supprime un sponsor */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sponsors WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Compte les événements liés à ce sponsor */
    public function countEvenements(int $id): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM evenement WHERE sponsor_id = :id"
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }
}
