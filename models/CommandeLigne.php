<?php
// models/CommandeLigne.php

require_once __DIR__ . '/../config/database.php';

class CommandeLigne {

    // ── Attributs ────────────────────────────────────────────────
    private ?int   $id;
    private int    $commande_id;
    private int    $produit_id;
    private int    $quantite;
    private float  $prix_unitaire;
    private float  $total_ligne;

    // ── Constructeur ─────────────────────────────────────────────
    public function __construct(
        ?int  $id            = null,
        int   $commande_id   = 0,
        int   $produit_id    = 0,
        int   $quantite      = 1,
        float $prix_unitaire = 0.0,
        float $total_ligne   = 0.0
    ) {
        $this->id            = $id;
        $this->commande_id   = $commande_id;
        $this->produit_id    = $produit_id;
        $this->quantite      = $quantite;
        $this->prix_unitaire = $prix_unitaire;
        $this->total_ligne   = $total_ligne;
    }

    // ── Destructeur ──────────────────────────────────────────────
    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): ?int          { return $this->id; }
    public function getCommandeId(): int   { return $this->commande_id; }
    public function getProduitId(): int    { return $this->produit_id; }
    public function getQuantite(): int     { return $this->quantite; }
    public function getPrixUnitaire(): float { return $this->prix_unitaire; }
    public function getTotalLigne(): float { return $this->total_ligne; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(?int $id): void            { $this->id = $id; }
    public function setCommandeId(int $id): void     { $this->commande_id = $id; }
    public function setProduitId(int $id): void      { $this->produit_id = $id; }
    public function setQuantite(int $q): void        { $this->quantite = $q; }
    public function setPrixUnitaire(float $p): void  { $this->prix_unitaire = $p; }
    public function setTotalLigne(float $t): void    { $this->total_ligne = $t; }

    // Pas de logique SQL dans le modèle — utiliser le controller pour les opérations DB.
}
// update
