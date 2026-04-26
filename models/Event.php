<?php
declare(strict_types=1);

namespace App\Models;

final class Event
{
    private int $id;
    private string $titre;
    private ?string $slug;
    private ?string $description;
    private string $dateDebut;
    private string $dateFin;
    private ?string $lieu;
    private ?string $adresse;
    private int $capaciteMax;
    private int $placesRestantes;
    private ?string $image;
    private float $prix;
    private string $status;
    private ?int $sponsorId;
    private string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->titre = (string) ($data['titre'] ?? '');
        $this->slug = $data['slug'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->dateDebut = (string) ($data['date_debut'] ?? '');
        $this->dateFin = (string) ($data['date_fin'] ?? '');
        $this->lieu = $data['lieu'] ?? null;
        $this->adresse = $data['adresse'] ?? null;
        $this->capaciteMax = (int) ($data['capacite_max'] ?? 0);
        $this->placesRestantes = (int) ($data['places_restantes'] ?? 0);
        $this->image = $data['image'] ?? null;
        $this->prix = (float) ($data['prix'] ?? 0.0);
        $this->status = (string) ($data['status'] ?? 'à venir');
        $this->sponsorId = ($data['sponsor_id'] !== null ? (int) $data['sponsor_id'] : null);
        $this->createdAt = (string) ($data['created_at'] ?? '');
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDateDebut(): string
    {
        return $this->dateDebut;
    }

    public function getDateFin(): string
    {
        return $this->dateFin;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getCapaciteMax(): int
    {
        return $this->capaciteMax;
    }

    public function getPlacesRestantes(): int
    {
        return $this->placesRestantes;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSponsorId(): ?int
    {
        return $this->sponsorId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setDateDebut(string $dateDebut): void
    {
        $this->dateDebut = $dateDebut;
    }

    public function setDateFin(string $dateFin): void
    {
        $this->dateFin = $dateFin;
    }

    public function setLieu(?string $lieu): void
    {
        $this->lieu = $lieu;
    }

    public function setAdresse(?string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function setCapaciteMax(int $capaciteMax): void
    {
        $this->capaciteMax = $capaciteMax;
    }

    public function setPlacesRestantes(int $placesRestantes): void
    {
        $this->placesRestantes = $placesRestantes;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function setPrix(float $prix): void
    {
        $this->prix = $prix;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setSponsorId(?int $sponsorId): void
    {
        $this->sponsorId = $sponsorId;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}