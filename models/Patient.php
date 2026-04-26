<?php
declare(strict_types=1);

namespace App\Models;

final class Patient
{
    private int $id;
    private int $userId;
    private ?string $groupeSanguin;
    private ?string $allergie;
    private ?string $antecedents;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->groupeSanguin = $data['groupe_sanguin'] ?? null;
        $this->allergie = $data['allergie'] ?? null;
        $this->antecedents = $data['antecedents'] ?? null;
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

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function getAllergie(): ?string
    {
        return $this->allergie;
    }

    public function getAntecedents(): ?string
    {
        return $this->antecedents;
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

    public function setGroupeSanguin(?string $groupeSanguin): void
    {
        $this->groupeSanguin = $groupeSanguin;
    }

    public function setAllergie(?string $allergie): void
    {
        $this->allergie = $allergie;
    }

    public function setAntecedents(?string $antecedents): void
    {
        $this->antecedents = $antecedents;
    }
}