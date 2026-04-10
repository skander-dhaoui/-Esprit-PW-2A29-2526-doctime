<?php
// models/Commande.php

class Commande {

    // ── Attributs ────────────────────────────────────────────────
    private ?int    $id;
    private string  $numero_commande;
    private int     $user_id;
    private string  $adresse_livraison;
    private string  $ville;
    private string  $code_postal;
    private string  $telephone;
    private string  $mode_paiement;
    private float   $total_ht;
    private float   $tva_montant;
    private float   $total_ttc;
    private string  $statut;
    private ?string $notes;
    private ?string $created_at;
    private ?string $updated_at;

    // ── Constructeur  ───────────────
    public function __construct(array $data = []) {
        $this->id                = isset($data['id']) ? (int)$data['id'] : null;
        $this->numero_commande   = (string)($data['numero_commande'] ?? '');
        $this->user_id           = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $this->adresse_livraison = (string)($data['adresse_livraison'] ?? '');
        $this->ville             = (string)($data['ville'] ?? '');
        $this->code_postal       = (string)($data['code_postal'] ?? '');
        $this->telephone         = (string)($data['telephone'] ?? '');
        $this->mode_paiement     = (string)($data['mode_paiement'] ?? '');
        $this->total_ht          = isset($data['total_ht']) ? (float)$data['total_ht'] : 0.0;
        $this->tva_montant       = isset($data['tva_montant']) ? (float)$data['tva_montant'] : 0.0;
        $this->total_ttc         = isset($data['total_ttc']) ? (float)$data['total_ttc'] : 0.0;
        $this->statut            = (string)($data['statut'] ?? 'en_attente');
        $this->notes             = array_key_exists('notes', $data) ? (string)$data['notes'] : null;
        $this->created_at        = array_key_exists('created_at', $data) ? (string)$data['created_at'] : null;
        $this->updated_at        = array_key_exists('updated_at', $data) ? (string)$data['updated_at'] : null;
    }

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): ?int               { return $this->id; }
    public function getNumeroCommande(): string  { return $this->numero_commande; }
    public function getUserId(): int            { return $this->user_id; }
    public function getAdresseLivraison(): string { return $this->adresse_livraison; }
    public function getVille(): string          { return $this->ville; }
    public function getCodePostal(): string     { return $this->code_postal; }
    public function getTelephone(): string      { return $this->telephone; }
    public function getModePaiement(): string   { return $this->mode_paiement; }
    public function getTotalHt(): float         { return $this->total_ht; }
    public function getTvaMontant(): float      { return $this->tva_montant; }
    public function getTotalTtc(): float        { return $this->total_ttc; }
    public function getStatut(): string         { return $this->statut; }
    public function getNotes(): ?string         { return $this->notes; }
    public function getCreatedAt(): ?string     { return $this->created_at; }
    public function getUpdatedAt(): ?string     { return $this->updated_at; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(?int $id): void                    { $this->id = $id; }
    public function setNumeroCommande(string $n): void       { $this->numero_commande = $n; }
    public function setUserId(int $id): void                 { $this->user_id = $id; }
    public function setAdresseLivraison(string $a): void     { $this->adresse_livraison = $a; }
    public function setVille(string $v): void                { $this->ville = $v; }
    public function setCodePostal(string $c): void           { $this->code_postal = $c; }
    public function setTelephone(string $t): void            { $this->telephone = $t; }
    public function setModePaiement(string $m): void         { $this->mode_paiement = $m; }
    public function setTotalHt(float $t): void               { $this->total_ht = $t; }
    public function setTvaMontant(float $t): void            { $this->tva_montant = $t; }
    public function setTotalTtc(float $t): void              { $this->total_ttc = $t; }
    public function setStatut(string $s): void               { $this->statut = $s; }
    public function setNotes(?string $n): void               { $this->notes = $n; }
    public function setCreatedAt(?string $d): void           { $this->created_at = $d; }
    public function setUpdatedAt(?string $d): void           { $this->updated_at = $d; }

}
