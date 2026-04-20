<?php
// models/Produit.php

require_once __DIR__ . '/../config/database.php';

class Produit {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): ?int {
        try {
            // Map the data to actual column names
            $slug = $data['slug'] ?? $this->generateSlug($data['nom'] ?? '');
            $status = $data['status'] ?? 'actif';
            $prix = $data['prix'] ?? ($data['prix_vente'] ?? 0);
            
            $sql = "INSERT INTO produits
                        (nom, slug, description, categorie_id, prix, stock, image, 
                         prescription, status, created_at, updated_at)
                    VALUES
                        (:nom, :slug, :description, :categorie_id, :prix, :stock, :image,
                         :prescription, :status, NOW(), NOW())";
            
            $params = [
                'nom' => $data['nom'] ?? '',
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'categorie_id' => $data['categorie_id'] ?? null,
                'prix' => $prix,
                'stock' => $data['stock'] ?? 0,
                'image' => $data['image'] ?? null,
                'prescription' => $data['prescription'] ?? 0,
                'status' => $status,
            ];
            
            $result = $this->db->execute($sql, $params);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::create - ' . $e->getMessage());
            return null;
        }
    }

    private function generateSlug(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT p.*, c.nom AS categorie_nom
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    WHERE p.id = :id";
            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(string $search = '', int $categorieId = 0, string $statut = ''): array {
        try {
            $where  = "WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.slug LIKE :search OR p.description LIKE :search)";
                $params['search'] = "%$search%";
            }
            if ($categorieId > 0) {
                $where .= " AND p.categorie_id = :cat";
                $params['cat'] = $categorieId;
            }
            if ($statut === 'actif') {
                $where .= " AND p.status = 'actif'";
            } elseif ($statut === 'inactif') {
                $where .= " AND p.status = 'inactif'";
            } elseif ($statut === 'rupture') {
                $where .= " AND p.status = 'rupture'";
            }

            $sql = "SELECT p.*, c.nom AS categorie_nom
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    $where
                    ORDER BY p.created_at DESC";
            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getActifs(): array {
        try {
            $sql = "SELECT p.*, c.nom AS categorie_nom
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    WHERE p.status = 'actif'
                    ORDER BY p.nom ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            return [];
        }
    }

    public function update(int $id, array $data): bool {
        try {
            // Map data to actual column names
            $slug = $data['slug'] ?? ($data['nom'] ? $this->generateSlug($data['nom']) : null);
            $status = $data['status'] ?? 'actif';
            $prix = $data['prix'] ?? ($data['prix_vente'] ?? null);
            
            $sql = "UPDATE produits SET
                        nom          = :nom,
                        slug         = :slug,
                        description  = :description,
                        categorie_id = :categorie_id,
                        prix         = :prix,
                        stock        = :stock,
                        image        = :image,
                        prescription = :prescription,
                        status       = :status,
                        updated_at   = NOW()
                    WHERE id = :id";
            
            $params = [
                'id' => $id,
                'nom' => $data['nom'] ?? '',
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'categorie_id' => $data['categorie_id'] ?? null,
                'prix' => $prix,
                'stock' => $data['stock'] ?? 0,
                'image' => $data['image'] ?? null,
                'prescription' => $data['prescription'] ?? 0,
                'status' => $status,
            ];
            
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            // Vérifier s'il est dans une commande
            $nb = (int)$this->db->queryScalar(
                "SELECT COUNT(*) FROM commande_details WHERE produit_id = :id", ['id' => $id]
            );
            if ($nb > 0) return false;
            return $this->db->execute("DELETE FROM produits WHERE id = :id", ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Produit::delete - ' . $e->getMessage());
            return false;
        }
    }

    public function referenceExists(string $ref, int $excludeId = 0): bool {
        try {
            $n = (int)$this->db->queryScalar(
                "SELECT COUNT(*) FROM produits WHERE slug = :slug AND id != :id",
                ['slug' => $ref, 'id' => $excludeId]
            );
            return $n > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getStats(): array {
        try {
            return [
                'total'        => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits"),
                'actifs'       => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE status='actif'"),
                'rupture'      => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE stock=0"),
                'alerte'       => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE status='rupture'"),
                'valeur_stock' => (float)$this->db->queryScalar("SELECT COALESCE(SUM(stock * prix),0) FROM produits WHERE status='actif'"),
            ];
        } catch (Exception $e) {
            return ['total'=>0,'actifs'=>0,'rupture'=>0,'alerte'=>0,'valeur_stock'=>0];
        }
    }

    /**
     * Récupère les produits d'une catégorie spécifique via une JOINTURE
     * 
     * @param int $categorieId ID de la catégorie
     * @return array Liste des produits avec informations de catégorie
     */
    public function getProduitsByCategorie(int $categorieId): array {
        try {
            $sql = "SELECT p.id, p.nom, p.slug, p.description, p.prix, p.stock, 
                           p.image, p.prescription, p.status, p.created_at,
                           c.id AS categorie_id, c.nom AS categorie_nom
                    FROM produits p
                    INNER JOIN categories c ON p.categorie_id = c.id
                    WHERE p.categorie_id = :categorie_id
                    AND p.status = 'actif'
                    ORDER BY p.nom ASC";
            
            return $this->db->query($sql, ['categorie_id' => $categorieId]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getProduitsByCategorie - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les catégories avec le nombre de produits actifs
     * Utilisé pour le formulaire de sélection de catégorie
     * 
     * @return array Liste des catégories avec comptage
     */
    public function getAllCategories(): array {
        try {
            $sql = "SELECT c.id, c.nom, c.slug,
                           COUNT(p.id) AS nombre_produits
                    FROM categories c
                    LEFT JOIN produits p ON c.id = p.categorie_id AND p.status = 'actif'
                    GROUP BY c.id, c.nom, c.slug
                    ORDER BY c.nom ASC";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Produit::getAllCategories - ' . $e->getMessage());
            return [];
        }
    }
}