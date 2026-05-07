<?php
declare(strict_types=1);

namespace App\Models;

final class Categorie
{
    private int $id;
    private string $nom;
    private ?string $slug;
    private ?string $description;
    private ?string $image;
    private ?int $parentId;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->nom = (string) ($data['nom'] ?? '');
        $this->slug = $data['slug'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->image = $data['image'] ?? null;
        $this->parentId = ($data['parent_id'] !== null ? (int) $data['parent_id'] : null);
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
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

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function setParentId(?int $parentId): void
    {
        $this->parentId = $parentId;
    }
}