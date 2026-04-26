<?php
declare(strict_types=1);

namespace App\Models;

final class Sponsor
{
    private int $id;
    private string $nom;
    private string $niveau;
    private string $email;
    private ?string $telephone;
    private ?string $secteur;
    private float $budget;
    private ?string $description;
    private ?string $logo;
    private ?string $siteWeb;
    private string $statut;
    private string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->nom = (string) ($data['nom'] ?? '');
        $this->niveau = (string) ($data['niveau'] ?? 'bronze');
        $this->email = (string) ($data['email'] ?? '');
        $this->telephone = $data['telephone'] ?? null;
        $this->secteur = $data['secteur'] ?? null;
        $this->budget = (float) ($data['budget'] ?? 0.0);
        $this->description = $data['description'] ?? null;
        $this->logo = $data['logo'] ?? null;
        $this->siteWeb = $data['site_web'] ?? null;
        $this->statut = (string) ($data['statut'] ?? 'actif');
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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getSecteur(): ?string
    {
        return $this->secteur;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setNiveau(string $niveau): void
    {
        $this->niveau = $niveau;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function setSecteur(?string $secteur): void
    {
        $this->secteur = $secteur;
    }

    public function setBudget(float $budget): void
    {
        $this->budget = $budget;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function setSiteWeb(?string $siteWeb): void
    {
        $this->siteWeb = $siteWeb;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}