<?php

require_once __DIR__ . '/../config/database.php';  // ✅
class Produit {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO produits (nom, description, reference, categorie_id, prix_achat, prix_vente, tva, stock, stock_alerte, image, actif, created_at, updated_at)
                    VALUES (:nom, :description, :reference, :categorie_id, :prix_achat, :prix_vente, :tva, :stock, :stock_alerte, :image, :actif, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT a.id) as nb_avis,
                           AVG(a.note) as note_moyenne
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN avis_produits a ON p.id = a.produit_id
                    WHERE p.id = :id
                    GROUP BY p.id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getActive(int $offset = 0, int $limit = 100): array {
        try {
            $sql = "SELECT * FROM produits WHERE actif = 1 ORDER BY nom ASC LIMIT :offset, :limit";
            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getActive - ' . $e->getMessage());
            return [];
        }
    }

    public function getRecent(int $offset, int $limit, string $search = '', int $categorie = 0): array {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search OR p.reference LIKE :search)";
            }

            if ($categorie > 0) {
                $where .= " AND p.categorie_id = :categorie";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT a.id) as nb_avis,
                           AVG(a.note) as note_moyenne
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN avis_produits a ON p.id = a.produit_id
                    $where
                    GROUP BY p.id
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($categorie > 0) {
                $params['categorie'] = $categorie;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getPopular(int $offset, int $limit, string $search = '', int $categorie = 0): array {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            }

            if ($categorie > 0) {
                $where .= " AND p.categorie_id = :categorie";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT a.id) as nb_avis,
                           AVG(a.note) as note_moyenne
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN avis_produits a ON p.id = a.produit_id
                    $where
                    GROUP BY p.id
                    ORDER BY p.vues DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($categorie > 0) {
                $params['categorie'] = $categorie;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getPopular - ' . $e->getMessage());
            return [];
        }
    }

    public function getByPrixAsc(int $offset, int $limit, string $search = '', int $categorie = 0): array {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            }

            if ($categorie > 0) {
                $where .= " AND p.categorie_id = :categorie";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT a.id) as nb_avis,
                           AVG(a.note) as note_moyenne
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN avis_produits a ON p.id = a.produit_id
                    $where
                    GROUP BY p.id
                    ORDER BY p.prix_vente ASC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($categorie > 0) {
                $params['categorie'] = $categorie;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getByPrixAsc - ' . $e->getMessage());
            return [];
        }
    }

    public function getByPrixDesc(int $offset, int $limit, string $search = '', int $categorie = 0): array {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            }

            if ($categorie > 0) {
                $where .= " AND p.categorie_id = :categorie";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT a.id) as nb_avis,
                           AVG(a.note) as note_moyenne
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN avis_produits a ON p.id = a.produit_id
                    $where
                    GROUP BY p.id
                    ORDER BY p.prix_vente DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($categorie > 0) {
                $params['categorie'] = $categorie;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getByPrixDesc - ' . $e->getMessage());
            return [];
        }
    }

    public function getByStock(int $offset, int $limit, string $search = '', int $categorie = 0): array {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            }

            if ($categorie > 0) {
                $where .= " AND p.categorie_id = :categorie";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT a.id) as nb_avis,
                           AVG(a.note) as note_moyenne
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN avis_produits a ON p.id = a.produit_id
                    $where
                    GROUP BY p.id
                    ORDER BY p.stock DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($categorie > 0) {
                $params['categorie'] = $categorie;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getByStock - ' . $e->getMessage());
            return [];
        }
    }

    public function getByVentes(int $offset, int $limit, string $search = ''): array {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom,
                           COUNT(DISTINCT cl.id) as nb_ventes
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN commande_lignes cl ON p.id = cl.produit_id
                    $where
                    GROUP BY p.id
                    ORDER BY nb_ventes DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getByVentes - ' . $e->getMessage());
            return [];
        }
    }

    public function getInactive(int $offset, int $limit, string $search = ''): array {
        try {
            $where = "WHERE p.actif = 0";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.reference LIKE :search)";
            }

            $sql = "SELECT p.*, c.nom as categorie_nom FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    $where
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Produit::getInactive - ' . $e->getMessage());
            return [];
        }
    }

    public function getLowStock(int $offset, int $limit): array {
        try {
            $sql = "SELECT p.*, c.nom as categorie_nom FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    WHERE p.stock <= p.stock_alerte AND p.actif = 1
                    ORDER BY p.stock ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getLowStock - ' . $e->getMessage());
            return [];
        }
    }

    public function getAll(): array {
        try {
            $sql = "SELECT * FROM produits ORDER BY nom ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Produit::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getSimilar(int $categoryId, int $excludeId, int $limit): array {
        try {
            $sql = "SELECT p.* FROM produits p
                    WHERE p.categorie_id = :categorie AND p.id != :exclude AND p.actif = 1
                    ORDER BY p.vues DESC
                    LIMIT :limit";

            return $this->db->query($sql, [
                'categorie' => $categoryId,
                'exclude' => $excludeId,
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getSimilar - ' . $e->getMessage());
            return [];
        }
    }

    public function countActive(string $search = '', int $categorie = 0): int {
        try {
            $where = "WHERE p.actif = 1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.description LIKE :search)";
            }

            if ($categorie > 0) {
                $where .= " AND p.categorie_id = :categorie";
            }

            $sql = "SELECT COUNT(*) as count FROM produits p $where";

            $params = [];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($categorie > 0) {
                $params['categorie'] = $categorie;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Produit::countActive - ' . $e->getMessage());
            return 0;
        }
    }

    public function countAll(string $search = '', string $filter = 'all'): int {
        try {
            $where = "WHERE 1=1";

            if (!empty($search)) {
                $where .= " AND (p.nom LIKE :search OR p.reference LIKE :search)";
            }

            if ($filter === 'inactif') {
                $where .= " AND p.actif = 0";
            } elseif ($filter === 'actif') {
                $where .= " AND p.actif = 1";
            } elseif ($filter === 'stock_faible') {
                $where .= " AND p.stock <= p.stock_alerte AND p.actif = 1";
            }

            $sql = "SELECT COUNT(*) as count FROM produits p $where";

            $params = [];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Produit::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function countLowStock(): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM produits WHERE stock <= stock_alerte AND actif = 1";
            $result = $this->db->query($sql);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Produit::countLowStock - ' . $e->getMessage());
            return 0;
        }
    }

    public function getStockValue(): float {
        try {
            $sql = "SELECT SUM(stock * prix_achat) as value FROM produits WHERE actif = 1";
            $result = $this->db->query($sql);
            return (float)($result[0]['value'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Produit::getStockValue - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getAveragePrice(): float {
        try {
            $sql = "SELECT AVG(prix_vente) as avg FROM produits WHERE actif = 1";
            $result = $this->db->query($sql);
            return (float)($result[0]['avg'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Produit::getAveragePrice - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getPriceMin(): float {
        try {
            $sql = "SELECT MIN(prix_vente) as min FROM produits WHERE actif = 1";
            $result = $this->db->query($sql);
            return (float)($result[0]['min'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Produit::getPriceMin - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getPriceMax(): float {
        try {
            $sql = "SELECT MAX(prix_vente) as max FROM produits WHERE actif = 1";
            $result = $this->db->query($sql);
            return (float)($result[0]['max'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Produit::getPriceMax - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $fields = [];
            $values = ['id' => $id];

            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }

            $fields[] = "updated_at = NOW()";

            $sql = "UPDATE produits SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Produit::update - ' . $e->getMessage());
            return false;
        }
    }

    public function incrementViews(int $id): bool {
        try {
            $sql = "UPDATE produits SET vues = vues + 1 WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Produit::incrementViews - ' . $e->getMessage());
            return false;
        }
    }

    public function updateStock(int $id, int $quantite): bool {
        try {
            $sql = "UPDATE produits SET stock = :stock WHERE id = :id";
            return $this->db->execute($sql, ['stock' => $quantite, 'id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Produit::updateStock - ' . $e->getMessage());
            return false;
        }
    }

    public function decrementStock(int $id, int $quantite): bool {
        try {
            $sql = "UPDATE produits SET stock = stock - :quantite WHERE id = :id";
            return $this->db->execute($sql, ['quantite' => $quantite, 'id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Produit::decrementStock - ' . $e->getMessage());
            return false;
        }
    }

    public function incrementStock(int $id, int $quantite): bool {
        try {
            $sql = "UPDATE produits SET stock = stock + :quantite WHERE id = :id";
            return $this->db->execute($sql, ['quantite' => $quantite, 'id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Produit::incrementStock - ' . $e->getMessage());
            return false;
        }
    }

    public function addStockMovement(int $produitId, string $action, int $quantite, string $raison, int $userId): bool {
        try {
            $sql = "INSERT INTO mouvements_stock (produit_id, action, quantite, raison, user_id, created_at)
                    VALUES (:produit_id, :action, :quantite, :raison, :user_id, NOW())";

            return $this->db->execute($sql, [
                'produit_id' => $produitId,
                'action' => $action,
                'quantite' => $quantite,
                'raison' => $raison,
                'user_id' => $userId,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Produit::addStockMovement - ' . $e->getMessage());
            return false;
        }
    }

    public function getCategories(): array {
        try {
            $sql = "SELECT * FROM categories ORDER BY nom ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Produit::getCategories - ' . $e->getMessage());
            return [];
        }
    }

    public function addReview(int $produitId, int $userId, int $rating, string $titre, string $contenu): ?int {
        try {
            $sql = "INSERT INTO avis_produits (produit_id, user_id, note, titre, contenu, created_at)
                    VALUES (:produit_id, :user_id, :note, :titre, :contenu, NOW())";

            $result = $this->db->execute($sql, [
                'produit_id' => $produitId,
                'user_id' => $userId,
                'note' => $rating,
                'titre' => $titre,
                'contenu' => $contenu,
            ]);

            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::addReview - ' . $e->getMessage());
            return null;
        }
    }

    public function updateReview(int $reviewId, int $rating, string $titre, string $contenu): bool {
        try {
            $sql = "UPDATE avis_produits SET note = :note, titre = :titre, contenu = :contenu, updated_at = NOW() WHERE id = :id";

            return $this->db->execute($sql, [
                'id' => $reviewId,
                'note' => $rating,
                'titre' => $titre,
                'contenu' => $contenu,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Produit::updateReview - ' . $e->getMessage());
            return false;
        }
    }

    public function getReviews(int $produitId): array {
        try {
            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar FROM avis_produits a
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE a.produit_id = :produit_id
                    ORDER BY a.created_at DESC";

            return $this->db->query($sql, ['produit_id' => $produitId]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getReviews - ' . $e->getMessage());
            return [];
        }
    }

    public function getUserReview(int $produitId, int $userId): ?array {
        try {
            $sql = "SELECT * FROM avis_produits WHERE produit_id = :produit_id AND user_id = :user_id";
            $result = $this->db->query($sql, ['produit_id' => $produitId, 'user_id' => $userId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::getUserReview - ' . $e->getMessage());
            return null;
        }
    }

    public function getReviewById(int $reviewId): ?array {
        try {
            $sql = "SELECT * FROM avis_produits WHERE id = :id";
            $result = $this->db->query($sql, ['id' => $reviewId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Produit::getReviewById - ' . $e->getMessage());
            return null;
        }
    }

    public function deleteReview(int $reviewId): bool {
        try {
            $sql = "DELETE FROM avis_produits WHERE id = :id";
            return $this->db->execute($sql, ['id' => $reviewId]);
        } catch (Exception $e) {
            error_log('Erreur Produit::deleteReview - ' . $e->getMessage());
            return false;
        }
    }

    public function countReviews(int $produitId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM avis_produits WHERE produit_id = :produit_id";
            $result = $this->db->query($sql, ['produit_id' => $produitId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Produit::countReviews - ' . $e->getMessage());
            return 0;
        }
    }

    public function getAverageRating(int $produitId): float {
        try {
            $sql = "SELECT AVG(note) as avg FROM avis_produits WHERE produit_id = :produit_id";
            $result = $this->db->query($sql, ['produit_id' => $produitId]);
            return (float)($result[0]['avg'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Produit::getAverageRating - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getTopProduits(int $limit): array {
        try {
            $sql = "SELECT p.*, SUM(cl.quantite) as total_ventes
                    FROM produits p
                    LEFT JOIN commande_lignes cl ON p.id = cl.produit_id
                    WHERE p.actif = 1
                    GROUP BY p.id
                    ORDER BY total_ventes DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getTopProduits - ' . $e->getMessage());
            return [];
        }
    }

    public function autocomplete(string $search, int $limit): array {
        try {
            $sql = "SELECT id, nom, reference, prix_vente FROM produits
                    WHERE (nom LIKE :search OR reference LIKE :search) AND actif = 1
                    ORDER BY nom ASC
                    LIMIT :limit";

            return $this->db->query($sql, [
                'search' => "%$search%",
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Produit::autocomplete - ' . $e->getMessage());
            return [];
        }
    }

    public function getRecentApi(int $limit): array {
        try {
            $sql = "SELECT id, nom, reference, prix_vente, image FROM produits
                    WHERE actif = 1
                    ORDER BY created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getRecentApi - ' . $e->getMessage());
            return [];
        }
    }

    public function getPopularApi(int $limit): array {
        try {
            $sql = "SELECT id, nom, reference, prix_vente, image FROM produits
                    WHERE actif = 1
                    ORDER BY vues DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Produit::getPopularApi - ' . $e->getMessage());
            return [];
        }
    }
}
?>
