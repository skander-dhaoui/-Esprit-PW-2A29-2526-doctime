<?php
// models/Commande.php
require_once __DIR__ . '/../config/database.php';

class CommandeLigne {

    // ─── Connexion BDD ────────────────────────────────────────────
    private Database $db;

    // ─── Attributs (propriétés encapsulées) ──────────────────────
    private ?int   $id            = null;
    private ?int   $commande_id   = null;
    private ?int   $produit_id    = null;
    private int    $quantite      = 1;
    private float  $prix_unitaire = 0.0;
    private float  $total_ligne   = 0.0;

    // ─── Constructeur ─────────────────────────────────────────────
    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ═══════════════════════════════════════════════════════════════
    //  GETTERS
    // ═══════════════════════════════════════════════════════════════
    public function getId(): ?int          { return $this->id; }
    public function getCommandeId(): ?int  { return $this->commande_id; }
    public function getProduitId(): ?int   { return $this->produit_id; }
    public function getQuantite(): int     { return $this->quantite; }
    public function getPrixUnitaire(): float { return $this->prix_unitaire; }
    public function getTotalLigne(): float { return $this->total_ligne; }

    // ═══════════════════════════════════════════════════════════════
    //  SETTERS (retournent $this pour chaining fluide)
    // ═══════════════════════════════════════════════════════════════
    public function setId(?int $v): self           { $this->id            = $v; return $this; }
    public function setCommandeId(?int $v): self   { $this->commande_id   = $v; return $this; }
    public function setProduitId(?int $v): self    { $this->produit_id    = $v; return $this; }
    public function setQuantite(int $v): self      { $this->quantite      = $v; return $this; }
    public function setPrixUnitaire(float $v): self { $this->prix_unitaire = $v; return $this; }
    public function setTotalLigne(float $v): self  { $this->total_ligne   = $v; return $this; }

    // ═══════════════════════════════════════════════════════════════
    //  HYDRATATION — remplit l'objet depuis un tableau (ex: row BDD)
    // ═══════════════════════════════════════════════════════════════
    public function hydrate(array $data): static {
        if (isset($data['id']))            $this->id            = (int)$data['id'];
        if (isset($data['commande_id']))   $this->commande_id   = (int)$data['commande_id'];
        if (isset($data['produit_id']))    $this->produit_id    = (int)$data['produit_id'];
        if (isset($data['quantite']))      $this->quantite      = (int)$data['quantite'];
        if (isset($data['prix_unitaire'])) $this->prix_unitaire = (float)$data['prix_unitaire'];
        if (isset($data['total_ligne']))   $this->total_ligne   = (float)$data['total_ligne'];
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CRUD — méthodes persistance
    // ═══════════════════════════════════════════════════════════════

    public function create(array $data): bool {
        try {
            return $this->db->execute(
                "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total_ligne)
                 VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :total_ligne)",
                $data
            );
        } catch (Exception $e) { error_log('Erreur CommandeLigne::create - ' . $e->getMessage()); return false; }
    }

    public function getByCommande(int $commandeId): array {
        try {
            return $this->db->query(
                "SELECT cd.*, p.nom AS produit_nom, p.reference
                 FROM commande_details cd LEFT JOIN produits p ON p.id = cd.produit_id
                 WHERE cd.commande_id = :commande_id ORDER BY cd.id ASC",
                ['commande_id' => $commandeId]
            );
        } catch (Exception $e) { error_log('Erreur CommandeLigne::getByCommande - ' . $e->getMessage()); return []; }
    }
}