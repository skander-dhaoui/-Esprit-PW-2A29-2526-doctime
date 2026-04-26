<?php
declare(strict_types=1);

namespace App\Models;

final class CommandeLigne
{
    private int $id;
    private int $commandeId;
    private int $produitId;
    private int $quantite;
    private float $prixUnitaire;
    private float $totalLigne;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->commandeId = (int) ($data['commande_id'] ?? 0);
        $this->produitId = (int) ($data['produit_id'] ?? 0);
        $this->quantite = (int) ($data['quantite'] ?? 1);
        $this->prixUnitaire = (float) ($data['prix_unitaire'] ?? 0.0);
        $this->totalLigne = (float) ($data['total_ligne'] ?? 0.0);
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCommandeId(): int
    {
        return $this->commandeId;
    }

    public function getProduitId(): int
    {
        return $this->produitId;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function getPrixUnitaire(): float
    {
        return $this->prixUnitaire;
    }

    public function getTotalLigne(): float
    {
        return $this->totalLigne;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setCommandeId(int $commandeId): void
    {
        $this->commandeId = $commandeId;
    }

    public function setProduitId(int $produitId): void
    {
        $this->produitId = $produitId;
    }

    public function setQuantite(int $quantite): void
    {
        $this->quantite = $quantite;
    }

    public function setPrixUnitaire(float $prixUnitaire): void
    {
        $this->prixUnitaire = $prixUnitaire;
    }

    public function setTotalLigne(float $totalLigne): void
    {
        $this->totalLigne = $totalLigne;
    }
}