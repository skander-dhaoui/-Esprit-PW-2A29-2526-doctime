<?php
// models/Produit.php

class Produit {

    // ── Attributs ────────────────────────────────────────────────
    private ?int    $id;
    private string  $nom;
    private string  $reference;
    private ?string $description;
    private ?int    $categorie_id;
    private float   $prix_achat;
    private float   $prix_vente;
    private float   $tva;
    private int     $stock;
    private int     $stock_alerte;
    private ?string $image;
    private bool    $prescription;
    private bool    $actif;
    private ?string $created_at;
    private ?string $updated_at;

    // ── Constructeur  ───────────────
    public function __construct(array $data = []) {
        $this->id           = isset($data['id']) ? (int)$data['id'] : null;
        $this->nom          = (string)($data['nom'] ?? '');
        $this->reference    = (string)($data['reference'] ?? '');
        $this->description  = array_key_exists('description', $data) ? (string)$data['description'] : null;
        $this->categorie_id = isset($data['categorie_id']) ? (int)$data['categorie_id'] : null;
        $this->prix_achat   = isset($data['prix_achat']) ? (float)$data['prix_achat'] : 0.0;
        $this->prix_vente   = isset($data['prix_vente']) ? (float)$data['prix_vente'] : 0.0;
        $this->tva          = isset($data['tva']) ? (float)$data['tva'] : 0.0;
        $this->stock        = isset($data['stock']) ? (int)$data['stock'] : 0;
        $this->stock_alerte = isset($data['stock_alerte']) ? (int)$data['stock_alerte'] : 0;
        $this->image        = array_key_exists('image', $data) ? (string)$data['image'] : null;
        $this->prescription = isset($data['prescription']) ? (bool)$data['prescription'] : false;
        $this->actif        = isset($data['actif']) ? (bool)$data['actif'] : true;
        $this->created_at   = array_key_exists('created_at', $data) ? (string)$data['created_at'] : null;
        $this->updated_at   = array_key_exists('updated_at', $data) ? (string)$data['updated_at'] : null;
    }

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): ?int            { return $this->id; }
    public function getNom(): string         { return $this->nom; }
    public function getReference(): string   { return $this->reference; }
    public function getDescription(): ?string { return $this->description; }
    public function getCategorieId(): ?int   { return $this->categorie_id; }
    public function getPrixAchat(): float    { return $this->prix_achat; }
    public function getPrixVente(): float    { return $this->prix_vente; }
    public function getTva(): float          { return $this->tva; }
    public function getStock(): int          { return $this->stock; }
    public function getStockAlerte(): int    { return $this->stock_alerte; }
    public function getImage(): ?string      { return $this->image; }
    public function isPrescription(): bool   { return $this->prescription; }
    public function isActif(): bool          { return $this->actif; }
    public function getCreatedAt(): ?string  { return $this->created_at; }
    public function getUpdatedAt(): ?string  { return $this->updated_at; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(?int $id): void            { $this->id = $id; }
    public function setNom(string $nom): void        { $this->nom = $nom; }
    public function setReference(string $ref): void  { $this->reference = $ref; }
    public function setDescription(?string $d): void { $this->description = $d; }
    public function setCategorieId(?int $id): void   { $this->categorie_id = $id; }
    public function setPrixAchat(float $p): void     { $this->prix_achat = $p; }
    public function setPrixVente(float $p): void     { $this->prix_vente = $p; }
    public function setTva(float $t): void           { $this->tva = $t; }
    public function setStock(int $s): void           { $this->stock = $s; }
    public function setStockAlerte(int $s): void     { $this->stock_alerte = $s; }
    public function setImage(?string $img): void     { $this->image = $img; }
    public function setPrescription(bool $p): void   { $this->prescription = $p; }
    public function setActif(bool $a): void          { $this->actif = $a; }
    public function setCreatedAt(?string $d): void   { $this->created_at = $d; }
    public function setUpdatedAt(?string $d): void   { $this->updated_at = $d; }

}
