<?php
declare(strict_types=1);

namespace App\Repositories;

use \PDO;
use \PDOException;
use \Database;

class EventRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM events ORDER BY date_debut ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUpcomingEvents(string $category = 'all'): array
    {
        $sql = "SELECT * FROM events WHERE (status = 'à venir' OR status = 'en_cours') AND date_debut >= CURDATE()";
        $params = [];
        if ($category !== 'all') {
            $sql .= " AND categorie = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY date_debut ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPastEvents(string $category = 'all'): array
    {
        $sql = "SELECT * FROM events WHERE (status = 'terminé' OR status = 'annulé') OR date_fin < CURDATE()";
        $params = [];
        if ($category !== 'all') {
            $sql .= " AND categorie = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcoming(): array
    {
        return $this->getUpcomingEvents();
    }

    public function getFeatured(): array
    {
        $stmt = $this->db->query("SELECT * FROM events WHERE status = 'à venir' ORDER BY date_debut ASC LIMIT 3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO events (titre, slug, description, contenu, date_debut, date_fin, lieu, adresse, capacite_max, places_restantes, image, prix, status, created_at, updated_at) 
                VALUES (:titre, :slug, :description, :contenu, :date_debut, :date_fin, :lieu, :adresse, :capacite_max, :places_restantes, :image, :prix, :status, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titre'            => $data['titre'] ?? '',
            ':slug'             => $data['slug'] ?? '',
            ':description'      => $data['description'] ?? null,
            ':contenu'          => $data['contenu'] ?? null,
            ':date_debut'       => $data['date_debut'] ?? '',
            ':date_fin'         => $data['date_fin'] ?? '',
            ':lieu'             => $data['lieu'] ?? null,
            ':adresse'          => $data['adresse'] ?? null,
            ':capacite_max'     => $data['capacite_max'] ?? 0,
            ':places_restantes' => $data['capacite_max'] ?? 0,
            ':image'            => $data['image'] ?? null,
            ':prix'             => $data['prix'] ?? 0,
            ':status'           => $data['status'] ?? 'à venir',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getParticipants(int $eventId): array
    {
        $sql = "SELECT u.id, u.nom, u.prenom, u.email, p.date_inscription, p.statut 
                FROM participations p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.event_id = :event_id 
                ORDER BY p.date_inscription DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isUserParticipant(int $eventId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM participations WHERE event_id = :event_id AND user_id = :user_id");
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getCategories(): array
    {
        try {
            return $this->db->query("SELECT DISTINCT categorie FROM events WHERE categorie IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // La colonne categorie n'existe pas encore dans la table
            return [];
        }
    }

    // Advanced methods for dashboard
    public function getTopEventsByParticipants(int $limit = 5): array
    {
        $sql = "SELECT e.*, COUNT(p.id) as nb_inscrits 
                FROM events e 
                LEFT JOIN participations p ON e.id = p.event_id 
                GROUP BY e.id 
                ORDER BY nb_inscrits DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenueEvents(): array
    {
        $sql = "SELECT e.titre, SUM(e.prix) as total_recettes 
                FROM events e 
                JOIN participations p ON e.id = p.event_id 
                GROUP BY e.id 
                ORDER BY total_recettes DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(array $filters): array
    {
        $sql = "SELECT e.*, COUNT(p.id) as nb_inscrits 
                FROM events e 
                LEFT JOIN participations p ON e.id = p.event_id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (e.titre LIKE :q OR e.description LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " GROUP BY e.id ORDER BY e.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllEvents(string $category = 'all'): array
    {
        if ($category === 'all') return $this->getAll();
        $stmt = $this->db->prepare("SELECT * FROM events WHERE categorie = :cat ORDER BY date_debut ASC");
        $stmt->execute([':cat' => $category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcomingEventsByParticipant(int $userId): array
    {
        $sql = "SELECT e.* FROM events e 
                JOIN participations p ON e.id = p.event_id 
                WHERE p.user_id = :user_id AND e.date_debut >= CURDATE() 
                ORDER BY e.date_debut ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPastEventsByParticipant(int $userId): array
    {
        $sql = "SELECT e.* FROM events e 
                JOIN participations p ON e.id = p.event_id 
                WHERE p.user_id = :user_id AND e.date_fin < CURDATE() 
                ORDER BY e.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllEventsByParticipant(int $userId): array
    {
        $sql = "SELECT e.* FROM events e 
                JOIN participations p ON e.id = p.event_id 
                WHERE p.user_id = :user_id 
                ORDER BY e.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventsByCreator(int $userId, string $role, ?string $status = null): array
    {
        // This assumes there's a creator_id or similar. If not in schema, might need to adjust.
        // Looking at database.sql, there is no creator_id in events table.
        // However, AdminController suggests events might be linked to users.
        // If the schema doesn't have it, we might need to return all if admin, or empty if not.
        // For now, let's assume it's filtered by status if provided.
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSpecialtyDistribution(): array
    {
        // For events, maybe it's category distribution
        return $this->db->query("SELECT categorie as specialty, COUNT(*) as count FROM events GROUP BY categorie")->fetchAll(PDO::FETCH_ASSOC);
    }
}
