<?php
declare(strict_types=1);

final class CommandeLigne
{
    private int   $id;
    private int   $commandeId;
    private int   $produitId;
    private int   $quantite;
    private float $prixUnitaire;
    private float $totalLigne;

    public function __construct(array $data = [])
    {
        $this->id           = (int)   ($data['id']            ?? 0);
        $this->commandeId   = (int)   ($data['commande_id']   ?? 0);
        $this->produitId    = (int)   ($data['produit_id']    ?? 0);
        $this->quantite     = (int)   ($data['quantite']      ?? 1);
        $this->prixUnitaire = (float) ($data['prix_unitaire'] ?? 0.0);
        $this->totalLigne   = (float) ($data['total_ligne']   ?? 0.0);
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int          { return $this->id; }
    public function getCommandeId(): int  { return $this->commandeId; }
    public function getProduitId(): int   { return $this->produitId; }
    public function getQuantite(): int    { return $this->quantite; }
    public function getPrixUnitaire(): float { return $this->prixUnitaire; }
    public function getTotalLigne(): float   { return $this->totalLigne; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void           { $this->id           = $v; }
    public function setCommandeId(int $v): void   { $this->commandeId   = $v; }
    public function setProduitId(int $v): void    { $this->produitId    = $v; }
    public function setQuantite(int $v): void     { $this->quantite     = $v; }
    public function setPrixUnitaire(float $v): void { $this->prixUnitaire = $v; }
    public function setTotalLigne(float $v): void { $this->totalLigne   = $v; }
}