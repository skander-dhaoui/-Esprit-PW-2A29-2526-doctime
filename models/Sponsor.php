<?php
declare(strict_types=1);

final class Sponsor
{
    private int     $id;
    private string  $nom;
    private string  $niveau;
    private string  $email;
    private ?string $telephone;
    private ?string $secteur;
    private float   $budget;
    private ?string $description;
    private ?string $logo;
    private ?string $siteWeb;
    private string  $statut;
    private string  $createdAt;

    public function __construct(array $data = [])
    {
        $this->id          = (int)    ($data['id']          ?? 0);
        $this->nom         = (string) ($data['nom']         ?? '');
        $this->niveau      = (string) ($data['niveau']      ?? 'bronze');
        $this->email       = (string) ($data['email']       ?? '');
        $this->telephone   =          ($data['telephone']   ?? null);
        $this->secteur     =          ($data['secteur']     ?? null);
        $this->budget      = (float)  ($data['budget']      ?? 0.0);
        $this->description =          ($data['description'] ?? null);
        $this->logo        =          ($data['logo']        ?? null);
        $this->siteWeb     =          ($data['site_web']    ?? null);
        $this->statut      = (string) ($data['statut']      ?? 'actif');
        $this->createdAt   = (string) ($data['created_at']  ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int           { return $this->id; }
    public function getNom(): string       { return $this->nom; }
    public function getNiveau(): string    { return $this->niveau; }
    public function getEmail(): string     { return $this->email; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getSecteur(): ?string  { return $this->secteur; }
    public function getBudget(): float     { return $this->budget; }
    public function getDescription(): ?string { return $this->description; }
    public function getLogo(): ?string     { return $this->logo; }
    public function getSiteWeb(): ?string  { return $this->siteWeb; }
    public function getStatut(): string    { return $this->statut; }
    public function getCreatedAt(): string { return $this->createdAt; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void           { $this->id          = $v; }
    public function setNom(string $v): void        { $this->nom         = $v; }
    public function setNiveau(string $v): void     { $this->niveau      = $v; }
    public function setEmail(string $v): void      { $this->email       = $v; }
    public function setTelephone(?string $v): void { $this->telephone   = $v; }
    public function setSecteur(?string $v): void   { $this->secteur     = $v; }
    public function setBudget(float $v): void      { $this->budget      = $v; }
    public function setDescription(?string $v): void { $this->description = $v; }
    public function setLogo(?string $v): void      { $this->logo        = $v; }
    public function setSiteWeb(?string $v): void   { $this->siteWeb     = $v; }
    public function setStatut(string $v): void     { $this->statut      = $v; }
    public function setCreatedAt(string $v): void  { $this->createdAt   = $v; }
}