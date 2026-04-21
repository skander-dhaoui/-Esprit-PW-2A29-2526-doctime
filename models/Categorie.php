<?php
require_once __DIR__ . '/../config/database.php';

class Categorie {

    // ─── Connexion BDD ────────────────────────────────────────────
    private Database $db;

    // ─── Attributs (propriétés encapsulées) ──────────────────────
    private ?int    $id          = null;
    private string  $nom         = '';
    private ?string $slug        = null;
    private ?string $description = null;
    private ?string $image       = null;
    private ?int    $parent_id   = null;

    // ─── Constructeur ─────────────────────────────────────────────
    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ═══════════════════════════════════════════════════════════════
    //  GETTERS
    // ═══════════════════════════════════════════════════════════════
    public function getId(): ?int           { return $this->id; }
    public function getNom(): string        { return $this->nom; }
    public function getSlug(): ?string      { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function getImage(): ?string     { return $this->image; }
    public function getParentId(): ?int     { return $this->parent_id; }

    // ═══════════════════════════════════════════════════════════════
    //  SETTERS (retournent $this pour chaining fluide)
    // ═══════════════════════════════════════════════════════════════
    public function setId(?int $v): self          { $this->id          = $v; return $this; }
    public function setNom(string $v): self        { $this->nom         = $v; return $this; }
    public function setSlug(?string $v): self      { $this->slug        = $v; return $this; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }
    public function setImage(?string $v): self     { $this->image       = $v; return $this; }
    public function setParentId(?int $v): self     { $this->parent_id   = $v; return $this; }

    // ═══════════════════════════════════════════════════════════════
    //  HYDRATATION — remplit l'objet depuis un tableau (ex: row BDD)
    // ═══════════════════════════════════════════════════════════════
    public function hydrate(array $data): static {
        if (isset($data['id']))          $this->id          = (int)$data['id'];
        if (isset($data['nom']))         $this->nom         = $data['nom'];
        if (isset($data['slug']))        $this->slug        = $data['slug'];
        if (isset($data['description'])) $this->description = $data['description'];
        if (isset($data['image']))       $this->image       = $data['image'];
        if (isset($data['parent_id']))   $this->parent_id   = (int)$data['parent_id'];
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CRUD — méthodes persistance
    // ═══════════════════════════════════════════════════════════════

    public function create(array $data): ?int {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "INSERT INTO categories (nom, slug, description, image, parent_id) VALUES (:nom, :slug, :description, :image, :parent_id)"
            );
            $stmt->bindValue(':nom',         $data['nom']         ?? '');
            $stmt->bindValue(':slug',        $data['slug']        ?? '');
            $stmt->bindValue(':description', $data['description'] ?? '');
            $stmt->bindValue(':image',       $data['image']       ?? '');
            if (!empty($data['parent_id'])) $stmt->bindValue(':parent_id', (int)$data['parent_id'], PDO::PARAM_INT);
            else                            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
            $result = $stmt->execute();
            if (!$result) { error_log('Categorie::create error: ' . implode(' | ', $stmt->errorInfo())); return null; }
            return (int)$this->db->getConnection()->lastInsertId();
        } catch (Exception $e) { error_log('Erreur Categorie::create - ' . $e->getMessage()); return null; }
    }

    public function getById(int $id): ?array {
        try {
            $result = $this->db->query("SELECT c.*, p.nom AS parent_nom FROM categories c LEFT JOIN categories p ON c.parent_id = p.id WHERE c.id = :id", ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) { return null; }
    }

    public function getAll(string $search = ''): array {
        try {
            $where  = "WHERE 1=1";
            $params = [];
            if (!empty($search)) { $where .= " AND (c.nom LIKE :search OR c.description LIKE :search)"; $params['search'] = "%$search%"; }
            return $this->db->query(
                "SELECT c.*, p.nom AS parent_nom, (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) AS nb_produits
                 FROM categories c LEFT JOIN categories p ON c.parent_id = p.id $where ORDER BY c.nom ASC",
                $params
            );
        } catch (Exception $e) { return []; }
    }

    public function getActives(): array {
        try { return $this->db->query("SELECT id, nom FROM categories ORDER BY nom ASC"); }
        catch (Exception $e) { return []; }
    }

    public function update(int $id, array $data): bool {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "UPDATE categories SET nom=:nom, slug=:slug, description=:description, image=:image, parent_id=:parent_id WHERE id=:id"
            );
            $stmt->bindValue(':id',          $id,                   PDO::PARAM_INT);
            $stmt->bindValue(':nom',         $data['nom']         ?? '');
            $stmt->bindValue(':slug',        $data['slug']        ?? '');
            $stmt->bindValue(':description', $data['description'] ?? '');
            $stmt->bindValue(':image',       $data['image']       ?? '');
            if (!empty($data['parent_id'])) $stmt->bindValue(':parent_id', (int)$data['parent_id'], PDO::PARAM_INT);
            else                            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
            return $stmt->execute();
        } catch (Exception $e) { return false; }
    }

    public function delete(int $id): bool {
        try {
            $nb = (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE categorie_id = :id", ['id' => $id]);
            if ($nb > 0) return false;
            return $this->db->execute("DELETE FROM categories WHERE id = :id", ['id' => $id]);
        } catch (Exception $e) { return false; }
    }

    public function slugExists(string $slug, int $excludeId = 0): bool {
        try { return (int)$this->db->queryScalar("SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id", ['slug' => $slug, 'id' => $excludeId]) > 0; }
        catch (Exception $e) { return false; }
    }

    public function getStats(): array {
        try { $total = (int)$this->db->queryScalar("SELECT COUNT(*) FROM categories"); return ['total' => $total, 'actives' => $total, 'inactives' => 0]; }
        catch (Exception $e) { return ['total' => 0, 'actives' => 0, 'inactives' => 0]; }
    }
}