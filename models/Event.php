<?php
require_once __DIR__ . '/../config/database.php';

class Event {

    // ─── Connexion BDD ────────────────────────────────────────────
    private Database $db;

    // ─── Attributs (propriétés encapsulées) ──────────────────────
    private ?int    $id           = null;
    private string  $titre        = '';
    private ?string $slug         = null;
    private ?string $description  = null;
    private ?string $date_debut   = null;
    private ?string $date_fin     = null;
    private ?string $lieu         = null;
    private ?string $adresse      = null;
    private int     $capacite_max = 0;
    private int     $places_restantes = 0;
    private ?string $image        = null;
    private float   $prix         = 0.0;
    private string  $status       = 'à venir';
    private ?int    $sponsor_id   = null;
    private ?string $created_at   = null;

    // ─── Constructeur ─────────────────────────────────────────────
    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ═══════════════════════════════════════════════════════════════
    //  GETTERS
    // ═══════════════════════════════════════════════════════════════
    public function getId(): ?int              { return $this->id; }
    public function getTitre(): string         { return $this->titre; }
    public function getSlug(): ?string         { return $this->slug; }
    public function getDescription(): ?string  { return $this->description; }
    public function getDateDebut(): ?string    { return $this->date_debut; }
    public function getDateFin(): ?string      { return $this->date_fin; }
    public function getLieu(): ?string         { return $this->lieu; }
    public function getAdresse(): ?string      { return $this->adresse; }
    public function getCapaciteMax(): int      { return $this->capacite_max; }
    public function getPlacesRestantes(): int  { return $this->places_restantes; }
    public function getImage(): ?string        { return $this->image; }
    public function getPrix(): float           { return $this->prix; }
    public function getStatus(): string        { return $this->status; }
    public function getSponsorId(): ?int       { return $this->sponsor_id; }
    public function getCreatedAt(): ?string    { return $this->created_at; }

    // ═══════════════════════════════════════════════════════════════
    //  SETTERS (retournent $this pour chaining fluide)
    // ═══════════════════════════════════════════════════════════════
    public function setId(?int $v): self             { $this->id              = $v; return $this; }
    public function setTitre(string $v): self         { $this->titre           = $v; return $this; }
    public function setSlug(?string $v): self         { $this->slug            = $v; return $this; }
    public function setDescription(?string $v): self  { $this->description     = $v; return $this; }
    public function setDateDebut(?string $v): self    { $this->date_debut      = $v; return $this; }
    public function setDateFin(?string $v): self      { $this->date_fin        = $v; return $this; }
    public function setLieu(?string $v): self         { $this->lieu            = $v; return $this; }
    public function setAdresse(?string $v): self      { $this->adresse         = $v; return $this; }
    public function setCapaciteMax(int $v): self      { $this->capacite_max    = $v; return $this; }
    public function setPlacesRestantes(int $v): self  { $this->places_restantes = $v; return $this; }
    public function setImage(?string $v): self        { $this->image           = $v; return $this; }
    public function setPrix(float $v): self           { $this->prix            = $v; return $this; }
    public function setStatus(string $v): self        { $this->status          = $v; return $this; }
    public function setSponsorId(?int $v): self       { $this->sponsor_id      = $v; return $this; }

    // ═══════════════════════════════════════════════════════════════
    //  HYDRATATION — remplit l'objet depuis un tableau (ex: row BDD)
    // ═══════════════════════════════════════════════════════════════
    public function hydrate(array $data): static {
        if (isset($data['id']))               $this->id               = (int)$data['id'];
        if (isset($data['titre']))            $this->titre            = $data['titre'];
        if (isset($data['slug']))             $this->slug             = $data['slug'];
        if (isset($data['description']))      $this->description      = $data['description'];
        if (isset($data['date_debut']))       $this->date_debut       = $data['date_debut'];
        if (isset($data['date_fin']))         $this->date_fin         = $data['date_fin'];
        if (isset($data['lieu']))             $this->lieu             = $data['lieu'];
        if (isset($data['adresse']))          $this->adresse          = $data['adresse'];
        if (isset($data['capacite_max']))     $this->capacite_max     = (int)$data['capacite_max'];
        if (isset($data['places_restantes'])) $this->places_restantes = (int)$data['places_restantes'];
        if (isset($data['image']))            $this->image            = $data['image'];
        if (isset($data['prix']))             $this->prix             = (float)$data['prix'];
        if (isset($data['status']))           $this->status           = $data['status'];
        if (isset($data['sponsor_id']))       $this->sponsor_id       = (int)$data['sponsor_id'];
        if (isset($data['created_at']))       $this->created_at       = $data['created_at'];
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CRUD
    // ═══════════════════════════════════════════════════════════════

    public function create(array $data): int {
        $slug = $this->generateSlug($data['titre']);
        $this->db->execute(
            "INSERT INTO events (titre, slug, description, date_debut, date_fin, lieu, adresse, capacite_max, image, prix, status, sponsor_id, created_at)
             VALUES (:titre, :slug, :description, :date_debut, :date_fin, :lieu, :adresse, :capacite_max, :image, :prix, :status, :sponsor_id, NOW())",
            [
                ':titre'        => $data['titre'],
                ':slug'         => $slug,
                ':description'  => $data['description']  ?? null,
                ':date_debut'   => $data['date_debut'],
                ':date_fin'     => $data['date_fin'],
                ':lieu'         => $data['lieu']          ?? null,
                ':adresse'      => $data['adresse']       ?? null,
                ':capacite_max' => $data['capacite_max']  ?? 0,
                ':image'        => $data['image']         ?? null,
                ':prix'         => $data['prix']          ?? 0,
                ':status'       => $data['status']        ?? 'à venir',
                ':sponsor_id'   => $data['sponsor_id']    ?? null,
            ]
        );
        return $this->db->lastInsertId();
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT e.*, s.nom AS sponsor_nom,
                    (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
             FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
             ORDER BY e.date_debut DESC"
        );
    }

    public function getById(int $id): ?array {
        return $this->db->queryOne(
            "SELECT e.*, s.nom AS sponsor_nom,
                    (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
             FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
             WHERE e.id = :id",
            [':id' => $id]
        );
    }

    public function getBySlug(string $slug): ?array {
        return $this->db->queryOne(
            "SELECT e.*, s.nom AS sponsor_nom,
                    (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
             FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
             WHERE e.slug = :slug",
            [':slug' => $slug]
        );
    }

    public function getUpcoming(): array {
        return $this->db->query(
            "SELECT e.*, s.nom AS sponsor_nom,
                    (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
             FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
             WHERE e.date_debut >= NOW() AND e.status = 'à venir'
             ORDER BY e.date_debut ASC LIMIT 10"
        );
    }

    public function getPast(): array {
        return $this->db->query(
            "SELECT e.*, s.nom AS sponsor_nom,
                    (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
             FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id
             WHERE e.date_debut < NOW() OR e.status = 'terminé'
             ORDER BY e.date_debut DESC"
        );
    }

    public function getFeatured(): array {
        return $this->db->query(
            "SELECT e.*, (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants
             FROM events e
             WHERE e.status = 'à venir' AND e.date_debut >= NOW()
             ORDER BY e.date_debut ASC LIMIT 3"
        );
    }

    public function update(int $id, array $data): bool {
        $allowed = ['titre','description','contenu','date_debut','date_fin','lieu','adresse','capacite_max','image','prix','status','sponsor_id'];
        $fields  = [];
        $params  = [':id' => $id];
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
            $params[':places_restantes'] = max(0, $data['capacite_max'] - $this->countParticipants($id));
            $fields[]                   = "places_restantes = :places_restantes";
        }
        if (empty($fields)) return false;
        return $this->db->execute("UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id", $params);
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->db->execute("UPDATE events SET status = :status WHERE id = :id", [':status' => $status, ':id' => $id]);
    }

    public function delete(int $id): bool {
        $this->db->execute("DELETE FROM participations WHERE event_id = :id",  [':id' => $id]);
        $this->db->execute("DELETE FROM event_sponsors WHERE event_id = :id",  [':id' => $id]);
        return $this->db->execute("DELETE FROM events WHERE id = :id",          [':id' => $id]);
    }

    // ─── Participations ───────────────────────────────────────────
    public function addParticipant(int $eventId, int $userId): bool {
        $already = $this->db->queryScalar("SELECT COUNT(*) FROM participations WHERE event_id = :e AND user_id = :u", [':e' => $eventId, ':u' => $userId]);
        if ($already > 0) return false;
        $event = $this->getById($eventId);
        if ($event['capacite_max'] > 0 && $this->countParticipants($eventId) >= $event['capacite_max']) return false;
        $this->db->beginTransaction();
        try {
            $this->db->execute("INSERT INTO participations (event_id, user_id, statut, date_inscription) VALUES (:e, :u, 'inscrit', NOW())", [':e' => $eventId, ':u' => $userId]);
            $this->db->execute("UPDATE events SET places_restantes = places_restantes - 1 WHERE id = :id AND places_restantes > 0", [':id' => $eventId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollback(); return false; }
    }

    public function removeParticipant(int $eventId, int $userId): bool {
        $this->db->beginTransaction();
        try {
            $this->db->execute("DELETE FROM participations WHERE event_id = :e AND user_id = :u", [':e' => $eventId, ':u' => $userId]);
            $this->db->execute("UPDATE events SET places_restantes = places_restantes + 1 WHERE id = :id", [':id' => $eventId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollback(); return false; }
    }

    public function isParticipant(int $eventId, int $userId): bool {
        return (bool)$this->db->queryScalar("SELECT COUNT(*) FROM participations WHERE event_id = :e AND user_id = :u", [':e' => $eventId, ':u' => $userId]);
    }

    public function countParticipants(int $eventId): int {
        return (int)$this->db->queryScalar("SELECT COUNT(*) FROM participations WHERE event_id = :e", [':e' => $eventId]);
    }

    public function getParticipants(int $eventId): array {
        return $this->db->query(
            "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, p.date_inscription, p.statut
             FROM participations p JOIN users u ON p.user_id = u.id
             WHERE p.event_id = :e ORDER BY p.date_inscription DESC",
            [':e' => $eventId]
        );
    }

    // ─── Stats ────────────────────────────────────────────────────
    public function countAll(): int      { return (int)$this->db->queryScalar("SELECT COUNT(*) FROM events"); }
    public function countUpcoming(): int { return (int)$this->db->queryScalar("SELECT COUNT(*) FROM events WHERE date_debut >= NOW() AND status = 'à venir'"); }
    public function countPast(): int     { return (int)$this->db->queryScalar("SELECT COUNT(*) FROM events WHERE date_debut < NOW() OR status = 'terminé'"); }

    public function countByStatus(string $status): int {
        return (int)$this->db->queryScalar("SELECT COUNT(*) FROM events WHERE status = :s", [':s' => $status]);
    }

    public function getTopEventsByParticipants(int $limit = 5): array {
        return $this->db->query("SELECT e.id, e.titre, e.type AS specialite, e.capacite_max, (SELECT COUNT(*) FROM participations WHERE event_id = e.id) AS inscrits FROM events e ORDER BY inscrits DESC LIMIT $limit");
    }

    public function getSpecialtyDistribution(): array {
        return $this->db->query("SELECT type AS specialite, COUNT(*) AS count FROM events WHERE type IS NOT NULL AND type != '' GROUP BY type ORDER BY count DESC");
    }

    // ─── Sponsors ─────────────────────────────────────────────────
    public function addSponsor(int $eventId, array $data): int {
        $this->db->execute(
            "INSERT INTO event_sponsors (event_id, sponsor_name, sponsor_logo, sponsor_website, amount, contribution_type, status)
             VALUES (:event_id, :sponsor_name, :sponsor_logo, :sponsor_website, :amount, :contribution_type, 'actif')",
            [':event_id' => $eventId, ':sponsor_name' => $data['sponsor_name'], ':sponsor_logo' => $data['sponsor_logo'] ?? null,
             ':sponsor_website' => $data['sponsor_website'] ?? null, ':amount' => $data['amount'] ?? 0,
             ':contribution_type' => $data['contribution_type'] ?? 'financier']
        );
        return $this->db->lastInsertId();
    }

    public function getSponsorsByEvent(int $eventId): array {
        return $this->db->query("SELECT * FROM event_sponsors WHERE event_id = :e AND status = 'actif' ORDER BY amount DESC", [':e' => $eventId]);
    }

    public function deleteSponsor(int $sponsorId): bool {
        return $this->db->execute("DELETE FROM event_sponsors WHERE id = :id", [':id' => $sponsorId]);
    }

    public function getTotalSponsorAmount(int $eventId): float {
        return (float)$this->db->queryScalar("SELECT COALESCE(SUM(amount), 0) FROM event_sponsors WHERE event_id = :e AND status = 'actif' AND contribution_type = 'financier'", [':e' => $eventId]);
    }

    // ─── Alias compatibilité ──────────────────────────────────────
    public function getAllEvents(?string $category = null): array   { return $category ? $this->db->query("SELECT e.*, s.nom AS sponsor_nom, (SELECT COUNT(*) FROM participations WHERE event_id = e.id) as nb_participants FROM events e LEFT JOIN sponsors s ON e.sponsor_id = s.id WHERE e.type = :cat ORDER BY e.date_debut DESC", [':cat' => $category]) : $this->getAll(); }
    public function isUserParticipant(int $e, int $u): bool         { return $this->isParticipant($e, $u); }
    public function registerParticipant(int $e, int $u): bool       { return $this->addParticipant($e, $u); }
    public function unregisterParticipant(int $e, int $u): bool     { return $this->removeParticipant($e, $u); }
    public function getCategories(): array                          { return $this->db->query("SELECT DISTINCT type FROM events WHERE type IS NOT NULL ORDER BY type"); }

    public function getUpcomingEventsByParticipant(int $userId): array {
        return $this->db->query("SELECT e.*, p.date_inscription, p.statut AS statut_inscription FROM participations p JOIN events e ON p.event_id = e.id WHERE p.user_id = :u AND e.date_debut >= NOW() ORDER BY e.date_debut ASC", [':u' => $userId]);
    }

    public function getPastEventsByParticipant(int $userId): array {
        return $this->db->query("SELECT e.*, p.date_inscription, p.statut AS statut_inscription FROM participations p JOIN events e ON p.event_id = e.id WHERE p.user_id = :u AND e.date_debut < NOW() ORDER BY e.date_debut DESC", [':u' => $userId]);
    }

    public function search(string $query, ?string $category = null): array {
        $sql    = "SELECT e.* FROM events e WHERE (e.titre LIKE :q OR e.description LIKE :q)";
        $params = [':q' => '%' . $query . '%'];
        if ($category) { $sql .= " AND e.type = :cat"; $params[':cat'] = $category; }
        $sql .= " ORDER BY e.date_debut DESC LIMIT 20";
        return $this->db->query($sql, $params);
    }

    // ─── Privé ────────────────────────────────────────────────────
    private function generateSlug(string $text): string {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}
?>