<?php
declare(strict_types=1);

namespace App\Models;

final class EventAvance
{
    private int $id;
    private string $titre;
    private string $status;
    private int $capaciteMax;
    private int $nbInscrits;
    private int $nbPresents;
    private int $nbAbsents;
    private float $tauxRemplissage;
    private float $recettesTotales;
    private ?string $sponsorNom;
    private ?string $sponsorNiveau;
    private string $dateDebut;
    private string $dateFin;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->titre = (string) ($data['titre'] ?? '');
        $this->status = (string) ($data['status'] ?? 'à venir');
        $this->capaciteMax = (int) ($data['capacite_max'] ?? 0);
        $this->nbInscrits = (int) ($data['nb_inscrits'] ?? 0);
        $this->nbPresents = (int) ($data['nb_presents'] ?? 0);
        $this->nbAbsents = (int) ($data['nb_absents'] ?? 0);
        $this->tauxRemplissage = (float) ($data['taux_remplissage'] ?? 0.0);
        $this->recettesTotales = (float) ($data['recettes_totales'] ?? 0.0);
        $this->sponsorNom = $data['sponsor_nom'] ?? null;
        $this->sponsorNiveau = $data['sponsor_niveau'] ?? null;
        $this->dateDebut = (string) ($data['date_debut'] ?? '');
        $this->dateFin = (string) ($data['date_fin'] ?? '');
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCapaciteMax(): int
    {
        return $this->capaciteMax;
    }

    public function getNbInscrits(): int
    {
        return $this->nbInscrits;
    }

    public function getNbPresents(): int
    {
        return $this->nbPresents;
    }

    public function getNbAbsents(): int
    {
        return $this->nbAbsents;
    }

    public function getTauxRemplissage(): float
    {
        return $this->tauxRemplissage;
    }

    public function getRecettesTotales(): float
    {
        return $this->recettesTotales;
    }

    public function getSponsorNom(): ?string
    {
        return $this->sponsorNom;
    }

    public function getSponsorNiveau(): ?string
    {
        return $this->sponsorNiveau;
    }

    public function getDateDebut(): string
    {
        return $this->dateDebut;
    }

    public function getDateFin(): string
    {
        return $this->dateFin;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setCapaciteMax(int $capaciteMax): void
    {
        $this->capaciteMax = $capaciteMax;
    }

    public function setNbInscrits(int $nbInscrits): void
    {
        $this->nbInscrits = $nbInscrits;
    }

    public function setNbPresents(int $nbPresents): void
    {
        $this->nbPresents = $nbPresents;
    }

    public function setNbAbsents(int $nbAbsents): void
    {
        $this->nbAbsents = $nbAbsents;
    }

    public function setTauxRemplissage(float $tauxRemplissage): void
    {
        $this->tauxRemplissage = $tauxRemplissage;
    }

    public function setRecettesTotales(float $recettesTotales): void
    {
        $this->recettesTotales = $recettesTotales;
    }

    public function setSponsorNom(?string $sponsorNom): void
    {
        $this->sponsorNom = $sponsorNom;
    }

    public function setSponsorNiveau(?string $sponsorNiveau): void
    {
        $this->sponsorNiveau = $sponsorNiveau;
    }

    public function setDateDebut(string $dateDebut): void
    {
        $this->dateDebut = $dateDebut;
    }

    public function setDateFin(string $dateFin): void
    {
        $this->dateFin = $dateFin;
    }
}