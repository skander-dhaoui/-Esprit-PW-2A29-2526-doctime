<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - CREATE
    // ═══════════════════════════════════════════════════════════
    public function create(array $data): int {
        $slug = $this->generateSlug($data['titre']);

        $this->db->execute("
            INSERT INTO events (titre, slug, description, date_debut, date_fin, lieu, adresse, capacite_max, image, prix, status, sponsor_id, created_at)
            VALUES (:titre, :slug, :description, :date_debut, :date_fin, :lieu, :adresse, :capacite_max, :image, :prix, :status, :sponsor_id, NOW())
        ", [
            ':titre'        => $data['titre'],
            ':slug'         => $slug,
            ':description'  => $data['description'] ?? null,
            ':date_debut'   => $data['date_debut'],
            ':date_fin'     => $data['date_fin'],
            ':lieu'         => $data['lieu'] ?? null,
            ':adresse'      => $data['adresse'] ?? null,
            ':capacite_max' => $data['capacite_max'] ?? 0,
            ':image'        => $data['image'] ?? null,
            ':prix'         => $data['prix'] ?? 0,
            ':status'       => $data['status'] ?? 'à venir',
            ':sponsor_id'   => $data['sponsor_id'] ?? null,
        ]);

        return $this->db->lastInsertId();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - READ
    // ═══════════════════════════════════════════════════════════
    public function getAll(): array {
        return $this->db->query("
            SELECT e.*, 
                   s.nom AS sponsor_nom,
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            ORDER BY e.date_debut DESC
        ");
    }

    public function getById(int $id): ?array {
        return $this->db->queryOne("
            SELECT e.*, 
                   s.nom AS sponsor_nom,
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.id = :id
        ", [':id' => $id]);
    }

    public function getBySlug(string $slug): ?array {
        return $this->db->queryOne("
            SELECT e.*, 
                   s.nom AS sponsor_nom,
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.slug = :slug
        ", [':slug' => $slug]);
    }

    public function getUpcoming(): array {
        return $this->db->query("
            SELECT e.*, 
                   s.nom AS sponsor_nom,
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.date_debut >= NOW() AND e.status = 'à venir'
            ORDER BY e.date_debut ASC
            LIMIT 10
        ");
    }

    public function getPast(): array {
        return $this->db->query("
            SELECT e.*, 
                   s.nom AS sponsor_nom,
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.date_debut < NOW() OR e.status = 'terminé'
            ORDER BY e.date_debut DESC
        ");
    }

    public function getFeatured(): array {
        return $this->db->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
            FROM events e
            WHERE e.status = 'à venir' AND e.date_debut >= NOW()
            ORDER BY e.date_debut ASC
            LIMIT 3
        ");
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - UPDATE
    // ═══════════════════════════════════════════════════════════
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['titre', 'description', 'contenu', 'date_debut', 'date_fin',
                    'lieu', 'adresse', 'capacite_max', 'image', 'prix', 'status', 'sponsor_id'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[]        = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (isset($data['titre'])) {
            $params[':slug'] = $this->generateSlug($data['titre']);
            $fields[]        = "slug = :slug";
        }

        if (isset($data['capacite_max'])) {
            $currentParticipants        = $this->countParticipants($id);
            $params[':places_restantes'] = max(0, $data['capacite_max'] - $currentParticipants);
            $fields[]                   = "places_restantes = :places_restantes";
        }

        if (empty($fields)) return false;

        return $this->db->execute(
            "UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id",
            $params
        );
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->db->execute(
            "UPDATE events SET status = :status WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - DELETE
    // ═══════════════════════════════════════════════════════════
    public function delete(int $id): bool {
        $this->db->execute("DELETE FROM participations  WHERE event_id = :id", [':id' => $id]);
        $this->db->execute("DELETE FROM event_sponsors  WHERE event_id = :id", [':id' => $id]);
        return $this->db->execute("DELETE FROM events WHERE id = :id",         [':id' => $id]);
    }

    // ═══════════════════════════════════════════════════════════
    //  PARTICIPATIONS
    // ═══════════════════════════════════════════════════════════
    public function addParticipant(int $eventId, int $userId): bool {
        // Déjà inscrit ?
        $already = $this->db->queryScalar(
            "SELECT COUNT(*) FROM participations WHERE event_id = :e AND user_id = :u",
            [':e' => $eventId, ':u' => $userId]
        );
        if ($already > 0) return false;

        // Places disponibles ?
        $event = $this->getById($eventId);
        if ($event['capacite_max'] > 0 && $this->countParticipants($eventId) >= $event['capacite_max']) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO participations (event_id, user_id, statut, date_inscription) VALUES (:e, :u, 'inscrit', NOW())",
                [':e' => $eventId, ':u' => $userId]
            );
            $this->db->execute(
                "UPDATE events SET places_restantes = places_restantes - 1 WHERE id = :id AND places_restantes > 0",
                [':id' => $eventId]
            );
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function removeParticipant(int $eventId, int $userId): bool {
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "DELETE FROM participations WHERE event_id = :e AND user_id = :u",
                [':e' => $eventId, ':u' => $userId]
            );
            $this->db->execute(
                "UPDATE events SET places_restantes = places_restantes + 1 WHERE id = :id",
                [':id' => $eventId]
            );
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function isParticipant(int $eventId, int $userId): bool {
        return (bool) $this->db->queryScalar(
            "SELECT COUNT(*) FROM participations WHERE event_id = :e AND user_id = :u",
            [':e' => $eventId, ':u' => $userId]
        );
    }

    public function countParticipants(int $eventId): int {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM participations WHERE event_id = :e",
            [':e' => $eventId]
        );
    }

    public function getParticipants(int $eventId): array {
        return $this->db->query("
            SELECT u.id, u.nom, u.prenom, u.email, u.telephone, p.date_inscription, p.statut
            FROM participations p
            JOIN users u ON p.user_id = u.id
            WHERE p.event_id = :e
            ORDER BY p.date_inscription DESC
        ", [':e' => $eventId]);
    }

    // ═══════════════════════════════════════════════════════════
    //  STATISTIQUES
    // ═══════════════════════════════════════════════════════════
    public function countAll(): int {
        return (int) $this->db->queryScalar("SELECT COUNT(*) FROM events");
    }

    public function countUpcoming(): int {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM events WHERE date_debut >= NOW() AND status = 'à venir'"
        );
    }

    public function countPast(): int {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM events WHERE date_debut < NOW() OR status = 'terminé'"
        );
    }

    public function countByStatus(string $status): int {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM events WHERE status = :s",
            [':s' => $status]
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  SPONSORS
    // ═══════════════════════════════════════════════════════════
    public function addSponsor(int $eventId, array $data): int {
        $this->db->execute("
            INSERT INTO event_sponsors (event_id, sponsor_name, sponsor_logo, sponsor_website, amount, contribution_type, status)
            VALUES (:event_id, :sponsor_name, :sponsor_logo, :sponsor_website, :amount, :contribution_type, 'actif')
        ", [
            ':event_id'          => $eventId,
            ':sponsor_name'      => $data['sponsor_name'],
            ':sponsor_logo'      => $data['sponsor_logo'] ?? null,
            ':sponsor_website'   => $data['sponsor_website'] ?? null,
            ':amount'            => $data['amount'] ?? 0,
            ':contribution_type' => $data['contribution_type'] ?? 'financier',
        ]);
        return $this->db->lastInsertId();
    }

    public function getSponsorsByEvent(int $eventId): array {
        return $this->db->query(
            "SELECT * FROM event_sponsors WHERE event_id = :e AND status = 'actif' ORDER BY amount DESC",
            [':e' => $eventId]
        );
    }

    public function updateSponsor(int $sponsorId, array $data): bool {
        return $this->db->execute("
            UPDATE event_sponsors 
            SET sponsor_name = :sponsor_name, sponsor_logo = :sponsor_logo,
                sponsor_website = :sponsor_website, amount = :amount, contribution_type = :contribution_type
            WHERE id = :id
        ", [
            ':id'                => $sponsorId,
            ':sponsor_name'      => $data['sponsor_name'],
            ':sponsor_logo'      => $data['sponsor_logo'] ?? null,
            ':sponsor_website'   => $data['sponsor_website'] ?? null,
            ':amount'            => $data['amount'] ?? 0,
            ':contribution_type' => $data['contribution_type'] ?? 'financier',
        ]);
    }

    public function deleteSponsor(int $sponsorId): bool {
        return $this->db->execute(
            "DELETE FROM event_sponsors WHERE id = :id",
            [':id' => $sponsorId]
        );
    }

    public function getTotalSponsorAmount(int $eventId): float {
        return (float) $this->db->queryScalar(
            "SELECT COALESCE(SUM(amount), 0) FROM event_sponsors WHERE event_id = :e AND status = 'actif' AND contribution_type = 'financier'",
            [':e' => $eventId]
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  ALIAS — compatibilité EventController
    // ═══════════════════════════════════════════════════════════

    public function getAllEvents(?string $category = null): array {
        if ($category) {
            return $this->db->query(
                "SELECT e.*, s.nom AS sponsor_nom,
                        (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
                 FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
                 WHERE e.type = :cat ORDER BY e.date_debut DESC",
                [':cat' => $category]
            );
        }
        return $this->getAll();
    }

    public function getUpcomingEvents(?string $category = null, int $limit = 100): array {
        $sql = "SELECT e.*, s.nom AS sponsor_nom,
                       (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
                FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
                WHERE e.date_debut >= NOW() AND e.status = 'à venir'";
        $params = [];
        if ($category) {
            $sql .= " AND e.type = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY e.date_debut ASC LIMIT :lim";
        // LIMIT ne fonctionne pas avec execute() bindé, on l'intègre directement
        $sql = str_replace(':lim', (int)$limit, $sql);
        return $this->db->query($sql, $params);
    }

    public function getPastEvents(?string $category = null): array {
        $sql = "SELECT e.*, s.nom AS sponsor_nom,
                       (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
                FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
                WHERE e.date_debut < NOW() OR e.status = 'terminé'";
        $params = [];
        if ($category) {
            $sql .= " AND e.type = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY e.date_debut DESC";
        return $this->db->query($sql, $params);
    }

    public function getCategories(): array {
        return $this->db->query("SELECT DISTINCT type FROM events WHERE type IS NOT NULL ORDER BY type");
    }

    public function isUserParticipant(int $eventId, int $userId): bool {
        return $this->isParticipant($eventId, $userId);
    }

    public function registerParticipant(int $eventId, int $userId): bool {
        return $this->addParticipant($eventId, $userId);
    }

    public function unregisterParticipant(int $eventId, int $userId): bool {
        return $this->removeParticipant($eventId, $userId);
    }

    public function getUpcomingEventsByParticipant(int $userId): array {
        return $this->db->query("
            SELECT e.*, p.date_inscription, p.statut AS statut_inscription
            FROM participations p
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = :u AND e.date_debut >= NOW()
            ORDER BY e.date_debut ASC
        ", [':u' => $userId]);
    }

    public function getPastEventsByParticipant(int $userId): array {
        return $this->db->query("
            SELECT e.*, p.date_inscription, p.statut AS statut_inscription
            FROM participations p
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = :u AND e.date_debut < NOW()
            ORDER BY e.date_debut DESC
        ", [':u' => $userId]);
    }

    public function getAllEventsByParticipant(int $userId): array {
        return $this->db->query("
            SELECT e.*, p.date_inscription, p.statut AS statut_inscription
            FROM participations p
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = :u
            ORDER BY e.date_debut DESC
        ", [':u' => $userId]);
    }

    public function getEventsByCreator(int $userId, string $role, ?string $statut = null): array {
        $sql = "SELECT e.* FROM events e WHERE 1=1";
        $params = [];
        if ($role !== 'admin') {
            $sql .= " AND e.createur_id = :uid AND e.createur_type = :role";
            $params[':uid']  = $userId;
            $params[':role'] = $role;
        }
        if ($statut) {
            $sql .= " AND e.statut = :statut";
            $params[':statut'] = $statut;
        }
        $sql .= " ORDER BY e.date_debut DESC";
        return $this->db->query($sql, $params);
    }

    public function search(string $query, ?string $category = null): array {
        $sql = "SELECT e.* FROM events e
                WHERE (e.titre LIKE :q OR e.description LIKE :q)";
        $params = [':q' => '%' . $query . '%'];
        if ($category) {
            $sql .= " AND e.type = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY e.date_debut DESC LIMIT 20";
        return $this->db->query($sql, $params);
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════
    private function generateSlug(string $text): string {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}
?>