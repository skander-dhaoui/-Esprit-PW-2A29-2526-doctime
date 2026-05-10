<?php
declare(strict_types=1);

class RendezVous
{
    private int     $id;
    private ?int    $clientId;
    private int     $userId;
    private string  $titre;
    private ?string $description;
    private string  $dateDebut;
    private string  $dateFin;
    private ?string $lieu;
    private string  $type;
    private string  $statut;
    private ?string $notes;
    private int     $rappel;
    private string  $createdAt;
    private string  $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id          = (int)    ($data['id']          ?? 0);
        $this->clientId    =          (($data['client_id'] ?? null) !== null ? (int)$data['client_id'] : null);
        $this->userId      = (int)    ($data['user_id']     ?? $data['medecin_id'] ?? 0);
        $this->titre       = (string) ($data['titre']       ?? '');
        $this->description =          ($data['description'] ?? null);
        $this->dateDebut   = (string) ($data['date_debut']  ?? $data['date'] ?? '');
        $this->dateFin     = (string) ($data['date_fin']    ?? '');
        $this->lieu        =          ($data['lieu']        ?? null);
        $this->type        = (string) ($data['type']        ?? 'consultation');
        $this->statut      = (string) ($data['statut']      ?? 'en_attente');
        $this->notes       =          ($data['notes']       ?? $data['note_medecin'] ?? null);
        $this->rappel      = (int)    ($data['rappel']      ?? 0);
        $this->createdAt   = (string) ($data['created_at']  ?? '');
        $this->updatedAt   = (string) ($data['updated_at']  ?? '');
    }

    public function __destruct() {}

    public function getId(): int             { return $this->id; }
    public function getClientId(): ?int      { return $this->clientId; }
    public function getUserId(): int         { return $this->userId; }
    public function getTitre(): string       { return $this->titre; }
    public function getDescription(): ?string { return $this->description; }
    public function getDateDebut(): string   { return $this->dateDebut; }
    public function getDateFin(): string     { return $this->dateFin; }
    public function getLieu(): ?string       { return $this->lieu; }
    public function getType(): string        { return $this->type; }
    public function getStatut(): string      { return $this->statut; }
    public function getNotes(): ?string      { return $this->notes; }
    public function getRappel(): int         { return $this->rappel; }
    public function getCreatedAt(): string   { return $this->createdAt; }
    public function getUpdatedAt(): string   { return $this->updatedAt; }

    public function setId(int $v): void              { $this->id          = $v; }
    public function setClientId(?int $v): void       { $this->clientId    = $v; }
    public function setUserId(int $v): void          { $this->userId      = $v; }
    public function setTitre(string $v): void         { $this->titre       = $v; }
    public function setDescription(?string $v): void  { $this->description = $v; }
    public function setDateDebut(string $v): void     { $this->dateDebut   = $v; }
    public function setDateFin(string $v): void       { $this->dateFin     = $v; }
    public function setLieu(?string $v): void         { $this->lieu        = $v; }
    public function setType(string $v): void          { $this->type        = $v; }
    public function setStatut(string $v): void        { $this->statut      = $v; }
    public function setNotes(?string $v): void        { $this->notes       = $v; }
    public function setRappel(int $v): void           { $this->rappel      = $v; }
    public function setCreatedAt(string $v): void     { $this->createdAt   = $v; }
    public function setUpdatedAt(string $v): void     { $this->updatedAt   = $v; }
}