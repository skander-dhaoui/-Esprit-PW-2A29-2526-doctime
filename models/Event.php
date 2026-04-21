<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - CREATE
    // ═══════════════════════════════════════════════════════════
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO events (titre, slug, description, contenu, date_debut, date_fin, lieu, adresse, capacite_max, places_restantes, image, prix, status, created_at)
            VALUES (:titre, :slug, :description, :contenu, :date_debut, :date_fin, :lieu, :adresse, :capacite_max, :places_restantes, :image, :prix, :status, NOW())
        ");
        
        // Générer le slug automatiquement
        $slug = $this->generateSlug($data['titre']);
        
        // places_restantes = capacite_max par défaut
        $placesRestantes = $data['capacite_max'] ?? 0;
        
        $stmt->execute([
            ':titre' => $data['titre'],
            ':slug' => $slug,
            ':description' => $data['description'] ?? null,
            ':contenu' => $data['contenu'] ?? null,
            ':date_debut' => $data['date_debut'],
            ':date_fin' => $data['date_fin'],
            ':lieu' => $data['lieu'] ?? null,
            ':adresse' => $data['adresse'] ?? null,
            ':capacite_max' => $data['capacite_max'] ?? 0,
            ':places_restantes' => $placesRestantes,
            ':image' => $data['image'] ?? null,
            ':prix' => $data['prix'] ?? 0,
            ':status' => $data['status'] ?? 'à venir'
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - READ
    // ═══════════════════════════════════════════════════════════
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            ORDER BY e.date_debut DESC
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getBySlug(string $slug): array|false {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            WHERE e.slug = :slug
        ");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    public function getUpcoming(): array {
        $stmt = $this->db->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            WHERE e.date_debut >= NOW() AND e.status = 'à venir'
            ORDER BY e.date_debut ASC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }

    public function getPast(): array {
        $stmt = $this->db->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            WHERE e.date_debut < NOW() OR e.status = 'terminé'
            ORDER BY e.date_debut DESC
        ");
        return $stmt->fetchAll();
    }

    public function getFeatured(): array {
        $stmt = $this->db->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            WHERE e.status = 'à venir' AND e.date_debut >= NOW()
            ORDER BY e.date_debut ASC
            LIMIT 3
        ");
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - UPDATE
    // ═══════════════════════════════════════════════════════════
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        $allowed = ['titre', 'description', 'contenu', 'date_debut', 'date_fin', 
                    'lieu', 'adresse', 'capacite_max', 'image', 'prix', 'status'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        // Si le titre change, mettre à jour le slug
        if (isset($data['titre'])) {
            $params[':slug'] = $this->generateSlug($data['titre']);
            $fields[] = "slug = :slug";
        }
        
        // Si la capacité change, mettre à jour places_restantes
        if (isset($data['capacite_max'])) {
            $currentEvent = $this->getById($id);
            $currentParticipants = $this->countParticipants($id);
            $newPlacesRestantes = $data['capacite_max'] - $currentParticipants;
            $params[':places_restantes'] = max(0, $newPlacesRestantes);
            $fields[] = "places_restantes = :places_restantes";
        }
        
        if (empty($fields)) return false;
        
        $stmt = $this->db->prepare("
            UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id
        ");
        return $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("
            UPDATE events SET status = :status WHERE id = :id
        ");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - DELETE
    // ═══════════════════════════════════════════════════════════
    public function delete(int $id): bool {
        try {
            // Supprimer d'abord les participations
            $stmt1 = $this->db->prepare("DELETE FROM participations WHERE event_id = :id");
            $stmt1->execute([':id' => $id]);
            
            // Supprimer les sponsors
            $stmt2 = $this->db->prepare("DELETE FROM event_sponsors WHERE event_id = :id");
            $stmt2->execute([':id' => $id]);
            
            // Puis supprimer l'événement
            $stmt3 = $this->db->prepare("DELETE FROM events WHERE id = :id");
            return $stmt3->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  PARTICIPATIONS
    // ═══════════════════════════════════════════════════════════
    public function addParticipant(int $eventId, int $userId): bool {
        // Vérifier si déjà inscrit
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM participations WHERE event_id = :event_id AND user_id = :user_id
        ");
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        
        // Vérifier les places disponibles
        $event = $this->getById($eventId);
        $nbParticipants = $this->countParticipants($eventId);
        
        if ($event['capacite_max'] > 0 && $nbParticipants >= $event['capacite_max']) {
            return false;
        }
        
        // Démarrer une transaction
        $this->db->beginTransaction();
        
        try {
            // Ajouter la participation
            $stmt = $this->db->prepare("
                INSERT INTO participations (event_id, user_id, statut, date_inscription)
                VALUES (:event_id, :user_id, 'inscrit', NOW())
            ");
            $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
            
            // Décrémenter les places restantes
            $stmt2 = $this->db->prepare("
                UPDATE events SET places_restantes = places_restantes - 1 WHERE id = :id AND places_restantes > 0
            ");
            $stmt2->execute([':id' => $eventId]);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function removeParticipant(int $eventId, int $userId): bool {
        // Démarrer une transaction
        $this->db->beginTransaction();
        
        try {
            // Supprimer la participation
            $stmt = $this->db->prepare("
                DELETE FROM participations WHERE event_id = :event_id AND user_id = :user_id
            ");
            $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
            
            // Incrémenter les places restantes
            $stmt2 = $this->db->prepare("
                UPDATE events SET places_restantes = places_restantes + 1 WHERE id = :id
            ");
            $stmt2->execute([':id' => $eventId]);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function isParticipant(int $eventId, int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM participations WHERE event_id = :event_id AND user_id = :user_id
        ");
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    public function countParticipants(int $eventId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM participations WHERE event_id = :event_id
        ");
        $stmt->execute([':event_id' => $eventId]);
        return (int)$stmt->fetchColumn();
    }

    public function getParticipants(int $eventId): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.nom, u.prenom, u.email, u.telephone, p.date_inscription, p.statut
            FROM participations p
            JOIN users u ON p.user_id = u.id
            WHERE p.event_id = :event_id
            ORDER BY p.date_inscription DESC
        ");
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════
    //  STATISTIQUES
    // ═══════════════════════════════════════════════════════════
    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    }

    public function countUpcoming(): int {
        return (int)$this->db->query("
            SELECT COUNT(*) FROM events WHERE date_debut >= NOW() AND status = 'à venir'
        ")->fetchColumn();
    }

    public function countPast(): int {
        return (int)$this->db->query("
            SELECT COUNT(*) FROM events WHERE date_debut < NOW() OR status = 'terminé'
        ")->fetchColumn();
    }

    public function countByStatus(string $status): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM events WHERE status = :status");
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    // ═══════════════════════════════════════════════════════════
    //  SPONSORS
    // ═══════════════════════════════════════════════════════════
    public function addSponsor(int $eventId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO event_sponsors (event_id, sponsor_name, sponsor_logo, sponsor_website, amount, contribution_type, status)
            VALUES (:event_id, :sponsor_name, :sponsor_logo, :sponsor_website, :amount, :contribution_type, :status)
        ");
        
        $stmt->execute([
            ':event_id' => $eventId,
            ':sponsor_name' => $data['sponsor_name'],
            ':sponsor_logo' => $data['sponsor_logo'] ?? null,
            ':sponsor_website' => $data['sponsor_website'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':contribution_type' => $data['contribution_type'] ?? 'financier',
            ':status' => 'actif'
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function getSponsorsByEvent(int $eventId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM event_sponsors 
            WHERE event_id = :event_id AND status = 'actif'
            ORDER BY amount DESC
        ");
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function updateSponsor(int $sponsorId, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE event_sponsors 
            SET sponsor_name = :sponsor_name,
                sponsor_logo = :sponsor_logo,
                sponsor_website = :sponsor_website,
                amount = :amount,
                contribution_type = :contribution_type
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id' => $sponsorId,
            ':sponsor_name' => $data['sponsor_name'],
            ':sponsor_logo' => $data['sponsor_logo'] ?? null,
            ':sponsor_website' => $data['sponsor_website'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':contribution_type' => $data['contribution_type'] ?? 'financier'
        ]);
    }

    public function deleteSponsor(int $sponsorId): bool {
        $stmt = $this->db->prepare("DELETE FROM event_sponsors WHERE id = :id");
        return $stmt->execute([':id' => $sponsorId]);
    }

    public function getTotalSponsorAmount(int $eventId): float {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total FROM event_sponsors 
            WHERE event_id = :event_id AND status = 'actif' AND contribution_type = 'financier'
        ");
        $stmt->execute([':event_id' => $eventId]);
        $result = $stmt->fetch();
        return (float)($result['total'] ?? 0);
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════
    private function generateSlug(string $text): string {
        // Convertir en minuscules
        $text = strtolower($text);
        // Remplacer les caractères spéciaux
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        // Remplacer les multiples tirets par un seul
        $text = preg_replace('/-+/', '-', $text);
        // Supprimer les tirets au début et à la fin
        return trim($text, '-');
    }
}
?>// update
