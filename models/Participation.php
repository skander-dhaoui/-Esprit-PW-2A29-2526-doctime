<?php
require_once __DIR__ . '/../config/database.php';

class Participation {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array {
        $stmt = $this->pdo->query("
            SELECT p.*, e.titre AS evenement_titre
            FROM participations p
            JOIN events e ON p.event_id = e.id
            ORDER BY p.date_inscription DESC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("
            SELECT p.*, e.titre AS evenement_titre
            FROM participations p
            JOIN events e ON p.event_id = e.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findByEvenement(int $evenementId): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM participations
            WHERE event_id = :eid
            ORDER BY date_inscription DESC
        ");
        $stmt->execute([':eid' => $evenementId]);
        return $stmt->fetchAll();
    }

    /** Vérifie si un participant est déjà inscrit à cet événement */
    public function alreadyRegistered(string $email, int $evenementId, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM participations
            WHERE email = :email AND event_id = :eid AND id != :id
        ");
        $stmt->execute([':email' => $email, ':eid' => $evenementId, ':id' => $excludeId]);
        return $stmt->fetchColumn() > 0;
    }

    public function create(array $data): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO participations (nom, prenom, email, telephone, profession, event_id, statut)
            VALUES (:nom, :prenom, :email, :telephone, :profession, :evenement_id, :statut)
        ");
        return $stmt->execute([
            ':nom'          => trim($data['nom']),
            ':prenom'       => trim($data['prenom']),
            ':email'        => trim($data['email']),
            ':telephone'    => trim($data['telephone']),
            ':profession'   => trim($data['profession']),
            ':evenement_id' => (int)$data['evenement_id'],
            ':statut'       => $data['statut'] ?? 'en_attente',
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE participations
            SET nom          = :nom,
                prenom       = :prenom,
                email        = :email,
                telephone    = :telephone,
                profession   = :profession,
                event_id     = :evenement_id,
                statut       = :statut
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'           => $id,
            ':nom'          => trim($data['nom']),
            ':prenom'       => trim($data['prenom']),
            ':email'        => trim($data['email']),
            ':telephone'    => trim($data['telephone']),
            ':profession'   => trim($data['profession']),
            ':evenement_id' => (int)$data['evenement_id'],
            ':statut'       => $data['statut'],
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM participations WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Récupère toutes les inscriptions d'un email (frontoffice) */
    public function findByEmail(string $email): array {
        $stmt = $this->pdo->prepare("
            SELECT p.*, e.titre AS evenement_titre, e.date_debut, e.date_fin,
                   e.lieu, e.specialite, e.prix, e.statut AS evenement_statut
            FROM participations p
            JOIN events e ON p.event_id = e.id
            WHERE p.email = :email
            ORDER BY p.date_inscription DESC
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetchAll();
    }
}
