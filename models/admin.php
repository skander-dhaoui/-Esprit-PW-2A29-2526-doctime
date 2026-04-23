<?php
declare(strict_types=1);

final class Admin
{
    private int     $id;
    private int     $userId;
    private ?string $permissions;
    private string  $createdAt;

    public function __construct(array $data = [])
    {
        $this->id          = (int)    ($data['id']          ?? 0);
        $this->userId      = (int)    ($data['user_id']     ?? 0);
        $this->permissions =          ($data['permissions'] ?? null);
        $this->createdAt   = (string) ($data['created_at']  ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int              { return $this->id; }
    public function getUserId(): int          { return $this->userId; }
    public function getPermissions(): ?string { return $this->permissions; }
    public function getCreatedAt(): string    { return $this->createdAt; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void              { $this->id          = $v; }
    public function setUserId(int $v): void          { $this->userId      = $v; }
    public function setPermissions(?string $v): void { $this->permissions = $v; }
    public function setCreatedAt(string $v): void    { $this->createdAt   = $v; }
}