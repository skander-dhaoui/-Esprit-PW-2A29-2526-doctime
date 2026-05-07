<?php
declare(strict_types=1);

namespace App\Repositories;

use \PDO;
use \Database;

class ParticipationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO participations (event_id, user_id, statut, date_inscription) 
                VALUES (:event_id, :user_id, :statut, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':event_id' => (int)$data['event_id'],
            ':user_id'  => (int)$data['user_id'],
            ':statut'   => $data['statut'] ?? 'inscrit',
        ]);
    }

    public function checkUserEvent(int $userId, int $eventId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM participations WHERE user_id = :user_id AND event_id = :event_id");
        $stmt->execute([':user_id' => $userId, ':event_id' => $eventId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getByEvent(int $eventId): array
    {
        $sql = "SELECT p.*, u.nom, u.prenom, u.email 
                FROM participations p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.event_id = :event_id 
                ORDER BY p.date_inscription DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUser(int $userId): array
    {
        $sql = "SELECT p.*, e.titre as event_titre, e.date_debut, e.date_fin, e.lieu 
                FROM participations p 
                JOIN events e ON p.event_id = e.id 
                WHERE p.user_id = :user_id 
                ORDER BY e.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE participations SET statut = :statut WHERE id = :id");
        return $stmt->execute([':statut' => $status, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM participations WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
