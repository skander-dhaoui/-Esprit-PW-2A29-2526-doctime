<?php
// models/Categorie.php

require_once __DIR__ . '/../config/database.php';

class Categorie {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO categories (nom, slug, description, image, parent_id)
                    VALUES (:nom, :slug, :description, :image, :parent_id)";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':nom',         $data['nom']         ?? '');
            $stmt->bindValue(':slug',        $data['slug']        ?? '');
            $stmt->bindValue(':description', $data['description'] ?? '');
            $stmt->bindValue(':image',       $data['image']       ?? '');

            if (!empty($data['parent_id'])) {
                $stmt->bindValue(':parent_id', (int)$data['parent_id'], PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
            }

            $result = $stmt->execute();
            if (!$result) {
                error_log('Categorie::create PDO error: ' . implode(' | ', $stmt->errorInfo()));
                return null;
            }
            return (int)$this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            error_log('Erreur Categorie::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT c.*, p.nom AS parent_nom
                    FROM categories c
                    LEFT JOIN categories p ON c.parent_id = p.id
                    WHERE c.id = :id";
            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Categorie::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(string $search = ''): array {
        try {
            $where  = "WHERE 1=1";
            $params = [];
            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.description LIKE :search)";
                $params['search'] = "%$search%";
            }
            $sql = "SELECT c.*,
                           p.nom AS parent_nom,
                           (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) AS nb_produits
                    FROM categories c
                    LEFT JOIN categories p ON c.parent_id = p.id
                    $where
                    ORDER BY c.nom ASC";
            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getActives(): array {
        try {
            return $this->db->query(
                "SELECT id, nom FROM categories ORDER BY nom ASC"
            );
        } catch (Exception $e) {
            return [];
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $sql = "UPDATE categories SET
                        nom         = :nom,
                        slug        = :slug,
                        description = :description,
                        image       = :image,
                        parent_id   = :parent_id
                    WHERE id = :id";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':id',          $id,                   PDO::PARAM_INT);
            $stmt->bindValue(':nom',         $data['nom']         ?? '');
            $stmt->bindValue(':slug',        $data['slug']        ?? '');
            $stmt->bindValue(':description', $data['description'] ?? '');
            $stmt->bindValue(':image',       $data['image']       ?? '');

            if (!empty($data['parent_id'])) {
                $stmt->bindValue(':parent_id', (int)$data['parent_id'], PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
            }

            $result = $stmt->execute();
            if (!$result) {
                error_log('Categorie::update PDO error: ' . implode(' | ', $stmt->errorInfo()));
            }
            return $result;
        } catch (Exception $e) {
            error_log('Erreur Categorie::update - ' . $e->getMessage());
            return false;
        }
    }


    public function delete(int $id): bool {
        try {
            $nb = (int)$this->db->queryScalar(
                "SELECT COUNT(*) FROM produits WHERE categorie_id = :id", ['id' => $id]
            );
            if ($nb > 0) return false;
            return $this->db->execute("DELETE FROM categories WHERE id = :id", ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::delete - ' . $e->getMessage());
            return false;
        }
    }

    public function slugExists(string $slug, int $excludeId = 0): bool {
        try {
            $n = (int)$this->db->queryScalar(
                "SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id",
                ['slug' => $slug, 'id' => $excludeId]
            );
            return $n > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getStats(): array {
        try {
            $total  = (int)$this->db->queryScalar("SELECT COUNT(*) FROM categories");
            return ['total' => $total, 'actives' => $total, 'inactives' => 0];
        } catch (Exception $e) {
            return ['total' => 0, 'actives' => 0, 'inactives' => 0];
        }
    }
}