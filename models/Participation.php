<?php
declare(strict_types=1);

final class Participation
{
    private int     $id;
    private int     $eventId;
    private int     $userId;
    private string  $statut;
    private ?string $codeQr;
    private string  $dateInscription;

    public function __construct(array $data = [])
    {
        $this->id              = (int)    ($data['id']               ?? 0);
        $this->eventId         = (int)    ($data['event_id']         ?? $data['evenement_id'] ?? 0);
        $this->userId          = (int)    ($data['user_id']          ?? 0);
        $this->statut          = (string) ($data['statut']           ?? 'inscrit');
        $this->codeQr          =          ($data['code_qr']          ?? null);
        $this->dateInscription = (string) ($data['date_inscription'] ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int               { return $this->id; }
    public function getEventId(): int          { return $this->eventId; }
    public function getUserId(): int           { return $this->userId; }
    public function getStatut(): string        { return $this->statut; }
    public function getCodeQr(): ?string       { return $this->codeQr; }
    public function getDateInscription(): string { return $this->dateInscription; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void               { $this->id              = $v; }
    public function setEventId(int $v): void          { $this->eventId         = $v; }
    public function setUserId(int $v): void           { $this->userId          = $v; }
    public function setStatut(string $v): void        { $this->statut          = $v; }
    public function setCodeQr(?string $v): void       { $this->codeQr          = $v; }
    public function setDateInscription(string $v): void { $this->dateInscription = $v; }
}