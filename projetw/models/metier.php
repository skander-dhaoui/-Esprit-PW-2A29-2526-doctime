<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Metier
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM metiers ORDER BY categorie, nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupedByCategorie(): array
    {
        $grouped = [];
        foreach ($this->getAll() as $m) {
            $grouped[$m['categorie']][] = $m;
        }
        return $grouped;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM metiers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function search(string $query): array
    {
        $q    = '%' . $query . '%';
        $stmt = $this->db->prepare("SELECT * FROM metiers WHERE nom LIKE ? OR categorie LIKE ? ORDER BY categorie, nom");
        $stmt->execute([$q, $q]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $nom, string $categorie, ?string $description = null): int
    {
        $stmt = $this->db->prepare("INSERT INTO metiers (nom, categorie, description) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $categorie, $description]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nom, string $categorie, ?string $description = null): bool
    {
        $stmt = $this->db->prepare("UPDATE metiers SET nom = ?, categorie = ?, description = ? WHERE id = ?");
        return $stmt->execute([$nom, $categorie, $description, $id]);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM metiers WHERE id = ?")->execute([$id]);
    }
}