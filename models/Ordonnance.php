<?php
declare(strict_types=1);

namespace App\Models;

final class Ordonnance
{
    private int $id;
    private string $numeroOrdonnance;
    private int $patientId;
    private int $medecinId;
    private ?int $rdvId;
    private string $dateOrdonnance;
    private ?string $dateExpiration;
    private string $contenu;
    private string $statut;
    private string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->numeroOrdonnance = (string) ($data['numero_ordonnance'] ?? '');
        $this->patientId = (int) ($data['patient_id'] ?? 0);
        $this->medecinId = (int) ($data['medecin_id'] ?? 0);
        $this->rdvId = (($data['rdv_id'] ?? null) !== null ? (int) $data['rdv_id'] : null);
        $this->dateOrdonnance = (string) ($data['date_ordonnance'] ?? '');
        $this->dateExpiration = $data['date_expiration'] ?? $data['date_validite'] ?? null;
        $this->contenu = (string) ($data['contenu'] ?? '');
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

    public function getNumeroOrdonnance(): string
    {
        return $this->numeroOrdonnance;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getMedecinId(): int
    {
        return $this->medecinId;
    }

    public function getRdvId(): ?int
    {
        return $this->rdvId;
    }

    public function getDateOrdonnance(): string
    {
        return $this->dateOrdonnance;
    }

    public function getDateExpiration(): ?string
    {
        return $this->dateExpiration;
    }

    public function getContenu(): string
    {
        return $this->contenu;
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

    public function setNumeroOrdonnance(string $numeroOrdonnance): void
    {
        $this->numeroOrdonnance = $numeroOrdonnance;
    }

    public function setPatientId(int $patientId): void
    {
        $this->patientId = $patientId;
    }

    public function setMedecinId(int $medecinId): void
    {
        $this->medecinId = $medecinId;
    }

    public function setRdvId(?int $rdvId): void
    {
        $this->rdvId = $rdvId;
    }

    public function setDateOrdonnance(string $dateOrdonnance): void
    {
        $this->dateOrdonnance = $dateOrdonnance;
    }

    public function setDateExpiration(?string $dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
    }

    public function setContenu(string $contenu): void
    {
        $this->contenu = $contenu;
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