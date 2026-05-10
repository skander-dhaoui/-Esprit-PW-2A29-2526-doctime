<?php
namespace App\Repositories;

require_once __DIR__ . '/../config/database.php';

class CategorieRepository
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT c1.*, c2.nom as parent_nom, COUNT(p.id) as nb_produits 
                FROM categories c1 
                LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                LEFT JOIN produits p ON p.categorie_id = c1.id 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (c1.nom LIKE :search OR c1.description LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $sql .= " GROUP BY c1.id ORDER BY c1.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActives(): array
    {
        return $this->db->query("SELECT * FROM categories ORDER BY nom ASC")->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO categories (nom, slug, description, image, parent_id) 
                VALUES (:nom, :slug, :description, :image, :parent_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom'         => $data['nom'],
            ':slug'        => $data['slug'],
            ':description' => $data['description'] ?? '',
            ':image'       => $data['image'] ?? '',
            ':parent_id'   => $data['parent_id']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE categories SET 
                nom = :nom, 
                slug = :slug, 
                description = :description, 
                image = :image, 
                parent_id = :parent_id 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom'         => $data['nom'],
            ':slug'        => $data['slug'],
            ':description' => $data['description'],
            ':image'       => $data['image'],
            ':parent_id'   => $data['parent_id'],
            ':id'          => $id
        ]);
    }

    public function delete(int $id): bool
    {
        // Check if category has products
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = :id");
        $stmt->execute([':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM categories WHERE slug = :slug";
        $params = [':slug' => $slug];
        if ($excludeId > 0) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getStats(): array
    {
        return [
            'total' => (int)$this->db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        ];
    }
}
