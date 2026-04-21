<?php
require_once __DIR__ . '/../config/database.php';

class Produit {

    // ─── Connexion BDD ────────────────────────────────────────────
    private Database $db;

    // ─── Attributs (propriétés encapsulées) ──────────────────────
    private ?int    $id           = null;
    private string  $nom          = '';
    private ?string $slug         = null;
    private ?string $description  = null;
    private ?int    $categorie_id = null;
    private float   $prix         = 0.0;
    private int     $stock        = 0;
    private ?string $image        = null;
    private int     $prescription = 0;
    private string  $status       = 'actif';
    private ?string $created_at   = null;
    private ?string $updated_at   = null;

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
    public function getCategorieId(): ?int  { return $this->categorie_id; }
    public function getPrix(): float        { return $this->prix; }
    public function getStock(): int         { return $this->stock; }
    public function getImage(): ?string     { return $this->image; }
    public function getPrescription(): int  { return $this->prescription; }
    public function getStatus(): string     { return $this->status; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }

    // ═══════════════════════════════════════════════════════════════
    //  SETTERS (retournent $this pour chaining fluide)
    // ═══════════════════════════════════════════════════════════════
    public function setId(?int $v): self          { $this->id           = $v; return $this; }
    public function setNom(string $v): self        { $this->nom          = $v; return $this; }
    public function setSlug(?string $v): self      { $this->slug         = $v; return $this; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }
    public function setCategorieId(?int $v): self  { $this->categorie_id = $v; return $this; }
    public function setPrix(float $v): self        { $this->prix         = $v; return $this; }
    public function setStock(int $v): self         { $this->stock        = $v; return $this; }
    public function setImage(?string $v): self     { $this->image        = $v; return $this; }
    public function setPrescription(int $v): self  { $this->prescription = $v; return $this; }
    public function setStatus(string $v): self     { $this->status       = $v; return $this; }

    // ═══════════════════════════════════════════════════════════════
    //  HYDRATATION — remplit l'objet depuis un tableau (ex: row BDD)
    // ═══════════════════════════════════════════════════════════════
    public function hydrate(array $data): static {
        if (isset($data['id']))           $this->id           = (int)$data['id'];
        if (isset($data['nom']))          $this->nom          = $data['nom'];
        if (isset($data['slug']))         $this->slug         = $data['slug'];
        if (isset($data['description']))  $this->description  = $data['description'];
        if (isset($data['categorie_id'])) $this->categorie_id = (int)$data['categorie_id'];
        if (isset($data['prix']))         $this->prix         = (float)$data['prix'];
        if (isset($data['stock']))        $this->stock        = (int)$data['stock'];
        if (isset($data['image']))        $this->image        = $data['image'];
        if (isset($data['prescription'])) $this->prescription = (int)$data['prescription'];
        if (isset($data['status']))       $this->status       = $data['status'];
        if (isset($data['created_at']))   $this->created_at   = $data['created_at'];
        if (isset($data['updated_at']))   $this->updated_at   = $data['updated_at'];
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CRUD — méthodes persistance
    // ═══════════════════════════════════════════════════════════════

    public function create(array $data): ?int {
        try {
            $slug   = $data['slug']   ?? $this->generateSlug($data['nom'] ?? '');
            $status = $data['status'] ?? 'actif';
            $prix   = $data['prix']   ?? ($data['prix_vente'] ?? 0);
            $result = $this->db->execute(
                "INSERT INTO produits (nom, slug, description, categorie_id, prix, stock, image, prescription, status, created_at, updated_at)
                 VALUES (:nom, :slug, :description, :categorie_id, :prix, :stock, :image, :prescription, :status, NOW(), NOW())",
                [
                    'nom'          => $data['nom']          ?? '',
                    'slug'         => $slug,
                    'description'  => $data['description']  ?? '',
                    'categorie_id' => $data['categorie_id'] ?? null,
                    'prix'         => $prix,
                    'stock'        => $data['stock']        ?? 0,
                    'image'        => $data['image']        ?? null,
                    'prescription' => $data['prescription'] ?? 0,
                    'status'       => $status,
                ]
            );
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $result = $this->db->query("SELECT p.*, c.nom AS categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id WHERE p.id = :id", ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) { return null; }
    }

    public function getAll(string $search = '', int $categorieId = 0, string $statut = ''): array {
        try {
            $where  = "WHERE 1=1";
            $params = [];
            if (!empty($search)) { $where .= " AND (p.nom LIKE :search OR p.slug LIKE :search OR p.description LIKE :search)"; $params['search'] = "%$search%"; }
            if ($categorieId > 0) { $where .= " AND p.categorie_id = :cat"; $params['cat'] = $categorieId; }
            if (in_array($statut, ['actif','inactif','rupture'])) { $where .= " AND p.status = :statut"; $params['statut'] = $statut; }
            return $this->db->query("SELECT p.*, c.nom AS categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id $where ORDER BY p.created_at DESC", $params);
        } catch (Exception $e) { return []; }
    }

    public function getActifs(): array {
        try { return $this->db->query("SELECT p.*, c.nom AS categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id WHERE p.status = 'actif' ORDER BY p.nom ASC"); }
        catch (Exception $e) { return []; }
    }

    public function update(int $id, array $data): bool {
        try {
            $slug   = $data['slug']   ?? ($data['nom'] ? $this->generateSlug($data['nom']) : null);
            $status = $data['status'] ?? 'actif';
            $prix   = $data['prix']   ?? ($data['prix_vente'] ?? null);
            return $this->db->execute(
                "UPDATE produits SET nom=:nom, slug=:slug, description=:description, categorie_id=:categorie_id, prix=:prix, stock=:stock, image=:image, prescription=:prescription, status=:status, updated_at=NOW() WHERE id=:id",
                ['id' => $id, 'nom' => $data['nom'] ?? '', 'slug' => $slug, 'description' => $data['description'] ?? '', 'categorie_id' => $data['categorie_id'] ?? null, 'prix' => $prix, 'stock' => $data['stock'] ?? 0, 'image' => $data['image'] ?? null, 'prescription' => $data['prescription'] ?? 0, 'status' => $status]
            );
        } catch (Exception $e) { return false; }
    }

    public function delete(int $id): bool {
        try {
            $nb = (int)$this->db->queryScalar("SELECT COUNT(*) FROM commande_details WHERE produit_id = :id", ['id' => $id]);
            if ($nb > 0) return false;
            return $this->db->execute("DELETE FROM produits WHERE id = :id", ['id' => $id]);
        } catch (Exception $e) { return false; }
    }

    public function getProduitsByCategorie(int $categorieId): array {
        try {
            return $this->db->query(
                "SELECT p.id, p.nom, p.slug, p.description, p.prix, p.stock, p.image, p.prescription, p.status, p.created_at, c.id AS categorie_id, c.nom AS categorie_nom
                 FROM produits p INNER JOIN categories c ON p.categorie_id = c.id
                 WHERE p.categorie_id = :categorie_id AND p.status = 'actif' ORDER BY p.nom ASC",
                ['categorie_id' => $categorieId]
            );
        } catch (Exception $e) { return []; }
    }

    public function search(string $query, int $limit = 10): array {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT p.id, p.nom, p.slug, p.prix, p.stock, p.image, p.status, c.nom AS categorie_nom
                 FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id
                 WHERE p.status = 'actif' AND (p.nom LIKE :q OR p.description LIKE :q OR p.slug LIKE :q)
                 ORDER BY p.nom ASC LIMIT :limit"
            );
            $stmt->bindValue(':q', '%' . $query . '%');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { return []; }
    }

    public function getStats(): array {
        try {
            return [
                'total'        => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits"),
                'actifs'       => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE status='actif'"),
                'rupture'      => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE stock=0"),
                'alerte'       => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE status='rupture'"),
                'valeur_stock' => (float)$this->db->queryScalar("SELECT COALESCE(SUM(stock*prix),0) FROM produits WHERE status='actif'"),
            ];
        } catch (Exception $e) { return ['total'=>0,'actifs'=>0,'rupture'=>0,'alerte'=>0,'valeur_stock'=>0]; }
    }

    public function getAllCategories(): array {
        try {
            return $this->db->query("SELECT c.id, c.nom, c.slug, COUNT(p.id) AS nombre_produits FROM categories c LEFT JOIN produits p ON c.id = p.categorie_id AND p.status = 'actif' GROUP BY c.id, c.nom, c.slug ORDER BY c.nom ASC");
        } catch (Exception $e) { return []; }
    }

    public function referenceExists(string $ref, int $excludeId = 0): bool {
        try { return (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE slug = :slug AND id != :id", ['slug' => $ref, 'id' => $excludeId]) > 0; }
        catch (Exception $e) { return false; }
    }

    // ─── Privé ────────────────────────────────────────────────────
    private function generateSlug(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}