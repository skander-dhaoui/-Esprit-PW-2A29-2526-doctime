<?php
declare(strict_types=1);

namespace App\Models;

final class Produit
{
    private int $id;
    private string $nom;
    private ?string $slug;
    private ?string $description;
    private ?int $categorieId;
    private float $prix;
    private int $stock;
    private ?string $image;
    private int $prescription;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->nom = (string) ($data['nom'] ?? '');
        $this->slug = $data['slug'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->categorieId = ($data['categorie_id'] !== null ? (int) $data['categorie_id'] : null);
        $this->prix = (float) ($data['prix'] ?? 0.0);
        $this->stock = (int) ($data['stock'] ?? 0);
        $this->image = $data['image'] ?? null;
        $this->prescription = (int) ($data['prescription'] ?? 0);
        $this->status = (string) ($data['status'] ?? 'actif');
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->updatedAt = (string) ($data['updated_at'] ?? '');
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategorieId(): ?int
    {
        return $this->categorieId;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPrescription(): int
    {
        return $this->prescription;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setCategorieId(?int $categorieId): void
    {
        $this->categorieId = $categorieId;
    }

    public function setPrix(float $prix): void
    {
        $this->prix = $prix;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function setPrescription(int $prescription): void
    {
        $this->prescription = $prescription;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}