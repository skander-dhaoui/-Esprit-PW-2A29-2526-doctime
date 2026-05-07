<?php
declare(strict_types=1);

namespace App\Models;

final class RendezVous
{
    private int $id;
    private ?int $clientId;
    private int $userId;
    private string $titre;
    private ?string $description;
    private string $dateDebut;
    private string $dateFin;
    private ?string $lieu;
    private string $type;
    private string $statut;
    private ?string $notes;
    private int $rappel;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->clientId = (($data['client_id'] ?? null) !== null ? (int) $data['client_id'] : null);
        $this->userId = (int) ($data['user_id'] ?? $data['medecin_id'] ?? 0);
        $this->titre = (string) ($data['titre'] ?? '');
        $this->description = $data['description'] ?? null;
        $this->dateDebut = (string) ($data['date_debut'] ?? $data['date'] ?? '');
        $this->dateFin = (string) ($data['date_fin'] ?? '');
        $this->lieu = $data['lieu'] ?? null;
        $this->type = (string) ($data['type'] ?? 'consultation');
        $this->statut = (string) ($data['statut'] ?? 'en_attente');
        $this->notes = $data['notes'] ?? $data['note_medecin'] ?? null;
        $this->rappel = (int) ($data['rappel'] ?? 0);
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

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTitre(): string
    {
        return $this->titre;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getRappel(): int
    {
        return $this->rappel;
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

    public function setClientId(?int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
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

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function setRappel(int $rappel): void
    {
        $this->rappel = $rappel;
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