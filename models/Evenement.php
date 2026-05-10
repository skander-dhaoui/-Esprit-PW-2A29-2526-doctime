<?php
require_once __DIR__ . '/../config/database.php';

class Evenement {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array {
        $stmt = $this->pdo->query("
            SELECT e.*, s.nom AS sponsor_nom
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            ORDER BY e.date_debut DESC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("
            SELECT e.*, s.nom AS sponsor_nom
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findUpcoming(): array {
        $stmt = $this->pdo->prepare("
            SELECT e.*, s.nom AS sponsor_nom
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.date_debut >= CURDATE()
              AND e.statut = 'planifie'
            ORDER BY e.date_debut ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO events
                (titre, description, specialite, lieu, date_debut, date_fin, capacite, prix, statut, sponsor_id)
            VALUES
                (:titre, :description, :specialite, :lieu, :date_debut, :date_fin, :capacite, :prix, :statut, :sponsor_id)
        ");
        return $stmt->execute([
            ':titre'       => trim($data['titre']),
            ':description' => trim($data['description']),
            ':specialite'  => trim($data['specialite']),
            ':lieu'        => trim($data['lieu']),
            ':date_debut'  => $data['date_debut'],
            ':date_fin'    => $data['date_fin'],
            ':capacite'    => (int)$data['capacite'],
            ':prix'        => (float)$data['prix'],
            ':statut'      => $data['statut'],
            ':sponsor_id'  => !empty($data['sponsor_id']) ? (int)$data['sponsor_id'] : null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE events
            SET titre       = :titre,
                description = :description,
                specialite  = :specialite,
                lieu        = :lieu,
                date_debut  = :date_debut,
                date_fin    = :date_fin,
                capacite    = :capacite,
                prix        = :prix,
                statut      = :statut,
                sponsor_id  = :sponsor_id
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'          => $id,
            ':titre'       => trim($data['titre']),
            ':description' => trim($data['description']),
            ':specialite'  => trim($data['specialite']),
            ':lieu'        => trim($data['lieu']),
            ':date_debut'  => $data['date_debut'],
            ':date_fin'    => $data['date_fin'],
            ':capacite'    => (int)$data['capacite'],
            ':prix'        => (float)$data['prix'],
            ':statut'      => $data['statut'],
            ':sponsor_id'  => !empty($data['sponsor_id']) ? (int)$data['sponsor_id'] : null,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM evenement WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countParticipations(int $id): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM participation WHERE evenement_id = :id AND statut != 'annule'"
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public function getPlacesRestantes(int $id): int {
        $evenement = $this->findById($id);
        if (!$evenement) return 0;
        return max(0, $evenement['capacite'] - $this->countParticipations($id));
    }
}
