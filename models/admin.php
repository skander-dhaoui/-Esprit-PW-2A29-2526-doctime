<?php
declare(strict_types=1);

namespace App\Models;

final class Admin
{
    private int $id;
    private int $userId;
    private ?string $permissions;
    private string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->permissions = $data['permissions'] ?? null;
        $this->createdAt = (string) ($data['created_at'] ?? '');
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPermissions(): ?string
    {
        return $this->permissions;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setPermissions(?string $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}