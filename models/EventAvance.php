<?php
declare(strict_types=1);

/**
 * EventAvance — entité représentant des données statistiques agrégées d'un événement.
 * Pas une table directe : alimentée depuis les jointures events + participations + sponsors.
 */
final class EventAvance
{
    private int     $id;
    private string  $titre;
    private string  $status;
    private int     $capaciteMax;
    private int     $nbInscrits;
    private int     $nbPresents;
    private int     $nbAbsents;
    private float   $tauxRemplissage;
    private float   $recettesTotales;
    private ?string $sponsorNom;
    private ?string $sponsorNiveau;
    private string  $dateDebut;
    private string  $dateFin;

    public function __construct(array $data = [])
    {
        $this->id              = (int)    ($data['id']               ?? 0);
        $this->titre           = (string) ($data['titre']            ?? '');
        $this->status          = (string) ($data['status']           ?? 'à venir');
        $this->capaciteMax     = (int)    ($data['capacite_max']     ?? 0);
        $this->nbInscrits      = (int)    ($data['nb_inscrits']      ?? 0);
        $this->nbPresents      = (int)    ($data['nb_presents']      ?? 0);
        $this->nbAbsents       = (int)    ($data['nb_absents']       ?? 0);
        $this->tauxRemplissage = (float)  ($data['taux_remplissage'] ?? 0.0);
        $this->recettesTotales = (float)  ($data['recettes_totales'] ?? 0.0);
        $this->sponsorNom      =          ($data['sponsor_nom']      ?? null);
        $this->sponsorNiveau   =          ($data['sponsor_niveau']   ?? null);
        $this->dateDebut       = (string) ($data['date_debut']       ?? '');
        $this->dateFin         = (string) ($data['date_fin']         ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int               { return $this->id; }
    public function getTitre(): string         { return $this->titre; }
    public function getStatus(): string        { return $this->status; }
    public function getCapaciteMax(): int      { return $this->capaciteMax; }
    public function getNbInscrits(): int       { return $this->nbInscrits; }
    public function getNbPresents(): int       { return $this->nbPresents; }
    public function getNbAbsents(): int        { return $this->nbAbsents; }
    public function getTauxRemplissage(): float { return $this->tauxRemplissage; }
    public function getRecettesTotales(): float { return $this->recettesTotales; }
    public function getSponsorNom(): ?string    { return $this->sponsorNom; }
    public function getSponsorNiveau(): ?string { return $this->sponsorNiveau; }
    public function getDateDebut(): string     { return $this->dateDebut; }
    public function getDateFin(): string       { return $this->dateFin; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void               { $this->id              = $v; }
    public function setTitre(string $v): void          { $this->titre           = $v; }
    public function setStatus(string $v): void         { $this->status          = $v; }
    public function setCapaciteMax(int $v): void       { $this->capaciteMax     = $v; }
    public function setNbInscrits(int $v): void        { $this->nbInscrits      = $v; }
    public function setNbPresents(int $v): void        { $this->nbPresents      = $v; }
    public function setNbAbsents(int $v): void         { $this->nbAbsents       = $v; }
    public function setTauxRemplissage(float $v): void { $this->tauxRemplissage = $v; }
    public function setRecettesTotales(float $v): void { $this->recettesTotales = $v; }
    public function setSponsorNom(?string $v): void    { $this->sponsorNom      = $v; }
    public function setSponsorNiveau(?string $v): void { $this->sponsorNiveau   = $v; }
    public function setDateDebut(string $v): void      { $this->dateDebut       = $v; }
    public function setDateFin(string $v): void        { $this->dateFin         = $v; }
}