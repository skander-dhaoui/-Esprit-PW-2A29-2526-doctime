<?php
declare(strict_types=1);

final class Event
{
    private int     $id;
    private string  $titre;
    private ?string $slug;
    private ?string $description;
    private string  $dateDebut;
    private string  $dateFin;
    private ?string $lieu;
    private ?string $adresse;
    private int     $capaciteMax;
    private int     $placesRestantes;
    private ?string $image;
    private float   $prix;
    private string  $status;
    private ?int    $sponsorId;
    private string  $createdAt;

    public function __construct(array $data = [])
    {
        $this->id              = (int)    ($data['id']               ?? 0);
        $this->titre           = (string) ($data['titre']            ?? '');
        $this->slug            =          ($data['slug']             ?? null);
        $this->description     =          ($data['description']      ?? null);
        $this->dateDebut       = (string) ($data['date_debut']       ?? '');
        $this->dateFin         = (string) ($data['date_fin']         ?? '');
        $this->lieu            =          ($data['lieu']             ?? null);
        $this->adresse         =          ($data['adresse']          ?? null);
        $this->capaciteMax     = (int)    ($data['capacite_max']     ?? 0);
        $this->placesRestantes = (int)    ($data['places_restantes'] ?? 0);
        $this->image           =          ($data['image']            ?? null);
        $this->prix            = (float)  ($data['prix']             ?? 0.0);
        $this->status          = (string) ($data['status']           ?? 'à venir');
        $this->sponsorId       =          ($data['sponsor_id']  !== null ? (int)$data['sponsor_id'] : null);
        $this->createdAt       = (string) ($data['created_at']       ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int               { return $this->id; }
    public function getTitre(): string         { return $this->titre; }
    public function getSlug(): ?string         { return $this->slug; }
    public function getDescription(): ?string  { return $this->description; }
    public function getDateDebut(): string     { return $this->dateDebut; }
    public function getDateFin(): string       { return $this->dateFin; }
    public function getLieu(): ?string         { return $this->lieu; }
    public function getAdresse(): ?string      { return $this->adresse; }
    public function getCapaciteMax(): int      { return $this->capaciteMax; }
    public function getPlacesRestantes(): int  { return $this->placesRestantes; }
    public function getImage(): ?string        { return $this->image; }
    public function getPrix(): float           { return $this->prix; }
    public function getStatus(): string        { return $this->status; }
    public function getSponsorId(): ?int       { return $this->sponsorId; }
    public function getCreatedAt(): string     { return $this->createdAt; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void               { $this->id              = $v; }
    public function setTitre(string $v): void          { $this->titre           = $v; }
    public function setSlug(?string $v): void          { $this->slug            = $v; }
    public function setDescription(?string $v): void   { $this->description     = $v; }
    public function setDateDebut(string $v): void      { $this->dateDebut       = $v; }
    public function setDateFin(string $v): void        { $this->dateFin         = $v; }
    public function setLieu(?string $v): void          { $this->lieu            = $v; }
    public function setAdresse(?string $v): void       { $this->adresse         = $v; }
    public function setCapaciteMax(int $v): void       { $this->capaciteMax     = $v; }
    public function setPlacesRestantes(int $v): void   { $this->placesRestantes = $v; }
    public function setImage(?string $v): void         { $this->image           = $v; }
    public function setPrix(float $v): void            { $this->prix            = $v; }
    public function setStatus(string $v): void         { $this->status          = $v; }
    public function setSponsorId(?int $v): void        { $this->sponsorId       = $v; }
    public function setCreatedAt(string $v): void      { $this->createdAt       = $v; }
}