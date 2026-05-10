<?php
declare(strict_types=1);

final class Produit
{
    private int     $id;
    private string  $nom;
    private ?string $slug;
    private ?string $description;
    private ?int    $categorieId;
    private float   $prix;
    private int     $stock;
    private ?string $image;
    private int     $prescription;
    private string  $status;
    private string  $createdAt;
    private string  $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id           = (int)    ($data['id']           ?? 0);
        $this->nom          = (string) ($data['nom']          ?? '');
        $this->slug         =          ($data['slug']         ?? null);
        $this->description  =          ($data['description']  ?? null);
        $this->categorieId  =          ($data['categorie_id'] !== null ? (int)$data['categorie_id'] : null);
        $this->prix         = (float)  ($data['prix']         ?? 0.0);
        $this->stock        = (int)    ($data['stock']        ?? 0);
        $this->image        =          ($data['image']        ?? null);
        $this->prescription = (int)    ($data['prescription'] ?? 0);
        $this->status       = (string) ($data['status']       ?? 'actif');
        $this->createdAt    = (string) ($data['created_at']   ?? '');
        $this->updatedAt    = (string) ($data['updated_at']   ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int            { return $this->id; }
    public function getNom(): string        { return $this->nom; }
    public function getSlug(): ?string      { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function getCategorieId(): ?int  { return $this->categorieId; }
    public function getPrix(): float        { return $this->prix; }
    public function getStock(): int         { return $this->stock; }
    public function getImage(): ?string     { return $this->image; }
    public function getPrescription(): int  { return $this->prescription; }
    public function getStatus(): string     { return $this->status; }
    public function getCreatedAt(): string  { return $this->createdAt; }
    public function getUpdatedAt(): string  { return $this->updatedAt; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void           { $this->id           = $v; }
    public function setNom(string $v): void        { $this->nom          = $v; }
    public function setSlug(?string $v): void      { $this->slug         = $v; }
    public function setDescription(?string $v): void { $this->description = $v; }
    public function setCategorieId(?int $v): void  { $this->categorieId  = $v; }
    public function setPrix(float $v): void        { $this->prix         = $v; }
    public function setStock(int $v): void         { $this->stock        = $v; }
    public function setImage(?string $v): void     { $this->image        = $v; }
    public function setPrescription(int $v): void  { $this->prescription = $v; }
    public function setStatus(string $v): void     { $this->status       = $v; }
    public function setCreatedAt(string $v): void  { $this->createdAt    = $v; }
    public function setUpdatedAt(string $v): void  { $this->updatedAt    = $v; }
}