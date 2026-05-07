<?php
declare(strict_types=1);

namespace App\Models;

final class Participation
{
    private int $id;
    private int $eventId;
    private int $userId;
    private string $statut;
    private ?string $codeQr;
    private string $dateInscription;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->eventId = (int) ($data['event_id'] ?? $data['evenement_id'] ?? 0);
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->statut = (string) ($data['statut'] ?? 'inscrit');
        $this->codeQr = $data['code_qr'] ?? null;
        $this->dateInscription = (string) ($data['date_inscription'] ?? '');
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getCodeQr(): ?string
    {
        return $this->codeQr;
    }

    public function getDateInscription(): string
    {
        return $this->dateInscription;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setEventId(int $eventId): void
    {
        $this->eventId = $eventId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function setCodeQr(?string $codeQr): void
    {
        $this->codeQr = $codeQr;
    }

    public function setDateInscription(string $dateInscription): void
    {
        $this->dateInscription = $dateInscription;
    }
}