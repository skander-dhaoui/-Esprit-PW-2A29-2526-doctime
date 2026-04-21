<?php
// models/Categorie.php

class Categorie {

    // ── Attributs ────────────────────────────────────────────────
    private ?int    $id;
    private string  $nom;
    private string  $slug;
    private ?string $description;
    private ?string $image;
    private ?int    $parent_id;
    private string  $statut;
    private ?string $created_at;
    private ?string $updated_at;

    // ── Constructeur ───────────────
    public function __construct(array $data = []) {
        $this->id          = isset($data['id']) ? (int)$data['id'] : null;
        $this->nom         = (string)($data['nom'] ?? '');
        $this->slug        = (string)($data['slug'] ?? '');
        $this->description = array_key_exists('description', $data) ? (string)$data['description'] : null;
        $this->image       = array_key_exists('image', $data) ? (string)$data['image'] : null;
        $this->parent_id   = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
        $this->statut      = (string)($data['statut'] ?? 'actif');
        $this->created_at  = array_key_exists('created_at', $data) ? (string)$data['created_at'] : null;
        $this->updated_at  = array_key_exists('updated_at', $data) ? (string)$data['updated_at'] : null;
    }

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): ?int            { return $this->id; }
    public function getNom(): string         { return $this->nom; }
    public function getSlug(): string        { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function getImage(): ?string      { return $this->image; }
    public function getParentId(): ?int      { return $this->parent_id; }
    public function getStatut(): string      { return $this->statut; }
    public function getCreatedAt(): ?string  { return $this->created_at; }
    public function getUpdatedAt(): ?string  { return $this->updated_at; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(?int $id): void            { $this->id = $id; }
    public function setNom(string $nom): void        { $this->nom = $nom; }
    public function setSlug(string $slug): void      { $this->slug = $slug; }
    public function setDescription(?string $d): void { $this->description = $d; }
    public function setImage(?string $img): void     { $this->image = $img; }
    public function setParentId(?int $id): void      { $this->parent_id = $id; }
    public function setStatut(string $s): void       { $this->statut = $s; }
    public function setCreatedAt(?string $d): void   { $this->created_at = $d; }
    public function setUpdatedAt(?string $d): void   { $this->updated_at = $d; }

}
// update
