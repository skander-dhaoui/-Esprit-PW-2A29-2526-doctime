<?php
declare(strict_types=1);

class Ordonnance
{
    private int     $id;
    private string  $numeroOrdonnance;
    private int     $patientId;
    private int     $medecinId;
    private ?int    $rdvId;
    private string  $dateOrdonnance;
    private ?string $dateExpiration;
    private string  $contenu;
    private string  $statut;
    private string  $createdAt;

    public function __construct(array $data = [])
    {
        $this->id               = (int)    ($data['id']                ?? 0);
        $this->numeroOrdonnance = (string) ($data['numero_ordonnance'] ?? '');
        $this->patientId        = (int)    ($data['patient_id']        ?? 0);
        $this->medecinId        = (int)    ($data['medecin_id']        ?? 0);
        $this->rdvId            =          (($data['rdv_id'] ?? null) !== null ? (int)$data['rdv_id'] : null);
        $this->dateOrdonnance   = (string) ($data['date_ordonnance']   ?? '');
        $this->dateExpiration   =          ($data['date_expiration']   ?? $data['date_validite'] ?? null);
        $this->contenu          = (string) ($data['contenu']           ?? '');
        $this->statut           = (string) ($data['statut']            ?? 'actif');
        $this->createdAt        = (string) ($data['created_at']        ?? '');
    }

    public function __destruct() {}

    public function getId(): int                  { return $this->id; }
    public function getNumeroOrdonnance(): string { return $this->numeroOrdonnance; }
    public function getPatientId(): int           { return $this->patientId; }
    public function getMedecinId(): int           { return $this->medecinId; }
    public function getRdvId(): ?int              { return $this->rdvId; }
    public function getDateOrdonnance(): string   { return $this->dateOrdonnance; }
    public function getDateExpiration(): ?string  { return $this->dateExpiration; }
    public function getContenu(): string          { return $this->contenu; }
    public function getStatut(): string           { return $this->statut; }
    public function getCreatedAt(): string        { return $this->createdAt; }

    public function setId(int $v): void                   { $this->id               = $v; }
    public function setNumeroOrdonnance(string $v): void   { $this->numeroOrdonnance = $v; }
    public function setPatientId(int $v): void            { $this->patientId        = $v; }
    public function setMedecinId(int $v): void            { $this->medecinId        = $v; }
    public function setRdvId(?int $v): void               { $this->rdvId            = $v; }
    public function setDateOrdonnance(string $v): void     { $this->dateOrdonnance   = $v; }
    public function setDateExpiration(?string $v): void    { $this->dateExpiration   = $v; }
    public function setContenu(string $v): void            { $this->contenu          = $v; }
    public function setStatut(string $v): void             { $this->statut           = $v; }
    public function setCreatedAt(string $v): void          { $this->createdAt        = $v; }
}