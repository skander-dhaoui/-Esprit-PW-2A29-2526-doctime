<?php
declare(strict_types=1);

namespace App\Models;

final class Article
{
    private int $id;
    private string $titre;
    private string $contenu;
    private ?int $auteurId;
    private ?string $categorie;
    private string $status;
    private ?string $tags;
    private ?string $image;
    private int $vues;
    private int $likes;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? $data['id_article'] ?? 0);
        $this->titre = (string) ($data['titre'] ?? '');
        $this->contenu = (string) ($data['contenu'] ?? '');
        $this->auteurId = (($data['auteur_id'] ?? null) !== null ? (int) $data['auteur_id'] : null);
        $this->categorie = $data['categorie'] ?? null;
        $this->status = (string) ($data['status'] ?? 'brouillon');
        $this->tags = $data['tags'] ?? null;
        $this->image = $data['image'] ?? null;
        $this->vues = (int) ($data['vues'] ?? 0);
        $this->likes = (int) ($data['likes'] ?? 0);
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

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function getAuteurId(): ?int
    {
        return $this->auteurId;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getVues(): int
    {
        return $this->vues;
    }

    public function getLikes(): int
    {
        return $this->likes;
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

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function setContenu(string $contenu): void
    {
        $this->contenu = $contenu;
    }

    public function setAuteurId(?int $auteurId): void
    {
        $this->auteurId = $auteurId;
    }

    public function setCategorie(?string $categorie): void
    {
        $this->categorie = $categorie;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setTags(?string $tags): void
    {
        $this->tags = $tags;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function setVues(int $vues): void
    {
        $this->vues = $vues;
    }

    public function setLikes(int $likes): void
    {
        $this->likes = $likes;
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