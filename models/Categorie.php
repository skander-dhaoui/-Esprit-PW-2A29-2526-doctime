<?php
<?php

require_once __DIR__ . '/../config/database.php'';

class Categorie {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO categories (nom, slug, description, icone, couleur, parent_id, ordre, statut, created_at, updated_at)
                    VALUES (:nom, :slug, :description, :icone, :couleur, :parent_id, :ordre, :statut, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Categorie::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT c.*, p.nom as parent_nom FROM categories c
                    LEFT JOIN categories p ON c.parent_id = p.id
                    WHERE c.id = :id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Categorie::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getBySlug(string $slug): ?array {
        try {
            $sql = "SELECT c.*, p.nom as parent_nom FROM categories c
                    LEFT JOIN categories p ON c.parent_id = p.id
                    WHERE c.slug = :slug";

            $result = $this->db->query($sql, ['slug' => $slug]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Categorie::getBySlug - ' . $e->getMessage());
            return null;
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

            $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Categorie::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM categories WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getAll(int $offset = 0, int $limit = 20, string $filter = 'tous', string $search = ''): array {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND c.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.description LIKE :search)";
            }

            $sql = "SELECT c.*, p.nom as parent_nom, COUNT(DISTINCT sc.id) as nb_sous_categories
                    FROM categories c
                    LEFT JOIN categories p ON c.parent_id = p.id
                    LEFT JOIN categories sc ON sc.parent_id = c.id
                    $where
                    GROUP BY c.id
                    ORDER BY c.ordre ASC, c.nom ASC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(string $filter = 'tous', string $search = ''): int {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND c.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.description LIKE :search)";
            }

            $sql = "SELECT COUNT(*) as count FROM categories c $where";

            $params = [];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function getActive(int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT c.*, COUNT(DISTINCT sc.id) as nb_sous_categories
                    FROM categories c
                    LEFT JOIN categories sc ON sc.parent_id = c.id
                    WHERE c.statut = 'active'
                    GROUP BY c.id
                    ORDER BY c.ordre ASC, c.nom ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getActive - ' . $e->getMessage());
            return [];
        }
    }

    public function countActive(): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE statut = 'active'";
            $result = $this->db->query($sql);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::countActive - ' . $e->getMessage());
            return 0;
        }
    }

    public function getInactive(): array {
        try {
            $sql = "SELECT c.* FROM categories c
                    WHERE c.statut = 'inactive'
                    ORDER BY c.nom ASC";

            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getInactive - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Gestion hiérarchique
    // ─────────────────────────────────────────
    public function getParent(int $id): ?array {
        try {
            $category = $this->getById($id);
            if (!$category || !$category['parent_id']) {
                return null;
            }

            return $this->getById($category['parent_id']);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getParent - ' . $e->getMessage());
            return null;
        }
    }

    public function getChildren(int $parentId, int $offset = 0, int $limit = 50): array {
        try {
            $sql = "SELECT c.* FROM categories c
                    WHERE c.parent_id = :parent_id
                    ORDER BY c.ordre ASC, c.nom ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['parent_id' => $parentId, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getChildren - ' . $e->getMessage());
            return [];
        }
    }

    public function countChildren(int $parentId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = :parent_id";
            $result = $this->db->query($sql, ['parent_id' => $parentId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::countChildren - ' . $e->getMessage());
            return 0;
        }
    }

    public function getTree(int $parentId = 0, int $level = 0, int $maxLevel = 5): array {
        try {
            if ($level > $maxLevel) {
                return [];
            }

            $sql = "SELECT c.* FROM categories c
                    WHERE c.parent_id = :parent_id AND c.statut = 'active'
                    ORDER BY c.ordre ASC, c.nom ASC";

            $categories = $this->db->query($sql, ['parent_id' => $parentId]);

            $tree = [];
            foreach ($categories as $cat) {
                $cat['level'] = $level;
                $cat['children'] = $this->getTree($cat['id'], $level + 1, $maxLevel);
                $tree[] = $cat;
            }

            return $tree;
        } catch (Exception $e) {
            error_log('Erreur Categorie::getTree - ' . $e->getMessage());
            return [];
        }
    }

    public function getFullTree(): array {
        try {
            return $this->getTree(0);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getFullTree - ' . $e->getMessage());
            return [];
        }
    }

    public function getBreadcrumb(int $id): array {
        try {
            $breadcrumb = [];
            $current = $this->getById($id);

            while ($current) {
                array_unshift($breadcrumb, [
                    'id' => $current['id'],
                    'nom' => $current['nom'],
                    'slug' => $current['slug'],
                ]);

                if ($current['parent_id']) {
                    $current = $this->getById($current['parent_id']);
                } else {
                    break;
                }
            }

            return $breadcrumb;
        } catch (Exception $e) {
            error_log('Erreur Categorie::getBreadcrumb - ' . $e->getMessage());
            return [];
        }
    }

    public function setParent(int $id, ?int $parentId): bool {
        try {
            // Vérifier qu'on n'est pas en train de créer une boucle
            if ($parentId) {
                $parent = $this->getById($parentId);
                if (!$parent || $this->isAncestor($id, $parentId)) {
                    return false;
                }
            }

            $sql = "UPDATE categories SET parent_id = :parent_id, updated_at = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id, 'parent_id' => $parentId]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::setParent - ' . $e->getMessage());
            return false;
        }
    }

    public function isAncestor(int $ancestorId, int $descendantId): bool {
        try {
            $current = $this->getById($descendantId);

            while ($current && $current['parent_id']) {
                if ($current['parent_id'] === $ancestorId) {
                    return true;
                }
                $current = $this->getById($current['parent_id']);
            }

            return false;
        } catch (Exception $e) {
            error_log('Erreur Categorie::isAncestor - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Tri et ordre
    // ─────────────────────────────────────────
    public function reorder(array $ids): bool {
        try {
            foreach ($ids as $ordre => $id) {
                $sql = "UPDATE categories SET ordre = :ordre WHERE id = :id";
                if (!$this->db->execute($sql, ['ordre' => $ordre, 'id' => $id])) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            error_log('Erreur Categorie::reorder - ' . $e->getMessage());
            return false;
        }
    }

    public function moveUp(int $id): bool {
        try {
            $category = $this->getById($id);
            if (!$category) {
                return false;
            }

            $sql = "SELECT c.* FROM categories c WHERE c.parent_id = :parent_id AND c.ordre < :ordre ORDER BY c.ordre DESC LIMIT 1";
            $result = $this->db->query($sql, [
                'parent_id' => $category['parent_id'],
                'ordre' => $category['ordre'],
            ]);

            if (!$result) {
                return false;
            }

            $previous = $result[0];

            // Échanger les ordres
            $this->db->execute("UPDATE categories SET ordre = :ordre WHERE id = :id", ['ordre' => $previous['ordre'], 'id' => $id]);
            $this->db->execute("UPDATE categories SET ordre = :ordre WHERE id = :id", ['ordre' => $category['ordre'], 'id' => $previous['id']]);

            return true;
        } catch (Exception $e) {
            error_log('Erreur Categorie::moveUp - ' . $e->getMessage());
            return false;
        }
    }

    public function moveDown(int $id): bool {
        try {
            $category = $this->getById($id);
            if (!$category) {
                return false;
            }

            $sql = "SELECT c.* FROM categories c WHERE c.parent_id = :parent_id AND c.ordre > :ordre ORDER BY c.ordre ASC LIMIT 1";
            $result = $this->db->query($sql, [
                'parent_id' => $category['parent_id'],
                'ordre' => $category['ordre'],
            ]);

            if (!$result) {
                return false;
            }

            $next = $result[0];

            // Échanger les ordres
            $this->db->execute("UPDATE categories SET ordre = :ordre WHERE id = :id", ['ordre' => $next['ordre'], 'id' => $id]);
            $this->db->execute("UPDATE categories SET ordre = :ordre WHERE id = :id", ['ordre' => $category['ordre'], 'id' => $next['id']]);

            return true;
        } catch (Exception $e) {
            error_log('Erreur Categorie::moveDown - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Gestion du slug
    // ─────────────────────────────────────────
    public function generateSlug(string $nom): string {
        try {
            $slug = strtolower(trim($nom));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');

            // Vérifier l'unicité
            $count = 1;
            $originalSlug = $slug;
            while ($this->getBySlug($slug)) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            return $slug;
        } catch (Exception $e) {
            error_log('Erreur Categorie::generateSlug - ' . $e->getMessage());
            return '';
        }
    }

    // ─────────────────────────────────────────
    //  Statut
    // ─────────────────────────────────────────
    public function activate(int $id): bool {
        try {
            $sql = "UPDATE categories SET statut = 'active', updated_at = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::activate - ' . $e->getMessage());
            return false;
        }
    }

    public function deactivate(int $id): bool {
        try {
            $sql = "UPDATE categories SET statut = 'inactive', updated_at = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::deactivate - ' . $e->getMessage());
            return false;
        }
    }

    public function toggleStatus(int $id): bool {
        try {
            $category = $this->getById($id);
            if (!$category) {
                return false;
            }

            $newStatus = $category['statut'] === 'active' ? 'inactive' : 'active';
            $sql = "UPDATE categories SET statut = :statut, updated_at = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id, 'statut' => $newStatus]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::toggleStatus - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Associations
    // ─────────────────────────────────────────
    public function getServices(int $id, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT s.* FROM services s
                    WHERE s.categorie_id = :categorie_id
                    ORDER BY s.nom ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['categorie_id' => $id, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getServices - ' . $e->getMessage());
            return [];
        }
    }

    public function countServices(int $id): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM services WHERE categorie_id = :categorie_id";
            $result = $this->db->query($sql, ['categorie_id' => $id]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::countServices - ' . $e->getMessage());
            return 0;
        }
    }

    public function getProduits(int $id, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT p.* FROM produits p
                    WHERE p.categorie_id = :categorie_id
                    ORDER BY p.nom ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['categorie_id' => $id, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getProduits - ' . $e->getMessage());
            return [];
        }
    }

    public function countProduits(int $id): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM produits WHERE categorie_id = :categorie_id";
            $result = $this->db->query($sql, ['categorie_id' => $id]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::countProduits - ' . $e->getMessage());
            return 0;
        }
    }

    public function getEvenements(int $id, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT e.* FROM evenements e
                    WHERE e.categorie_id = :categorie_id
                    ORDER BY e.date_debut DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['categorie_id' => $id, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getEvenements - ' . $e->getMessage());
            return [];
        }
    }

    public function countEvenements(int $id): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM evenements WHERE categorie_id = :categorie_id";
            $result = $this->db->query($sql, ['categorie_id' => $id]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::countEvenements - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────
    public function getStats(int $id): array {
        try {
            $services = $this->countServices($id);
            $produits = $this->countProduits($id);
            $evenements = $this->countEvenements($id);
            $children = $this->countChildren($id);

            return [
                'id' => $id,
                'services' => $services,
                'produits' => $produits,
                'evenements' => $evenements,
                'sous_categories' => $children,
                'total_items' => $services + $produits + $evenements,
            ];
        } catch (Exception $e) {
            error_log('Erreur Categorie::getStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getGlobalStats(): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total_categories,
                           SUM(CASE WHEN statut = 'active' THEN 1 ELSE 0 END) as active_categories,
                           SUM(CASE WHEN statut = 'inactive' THEN 1 ELSE 0 END) as inactive_categories,
                           SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as root_categories
                    FROM categories";

            $result = $this->db->query($sql);

            return [
                'total' => $result[0]['total_categories'] ?? 0,
                'active' => $result[0]['active_categories'] ?? 0,
                'inactive' => $result[0]['inactive_categories'] ?? 0,
                'root' => $result[0]['root_categories'] ?? 0,
            ];
        } catch (Exception $e) {
            error_log('Erreur Categorie::getGlobalStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getCategoriesWithContent(): array {
        try {
            $sql = "SELECT c.id, c.nom, c.slug,
                           COUNT(DISTINCT s.id) as services,
                           COUNT(DISTINCT p.id) as produits,
                           COUNT(DISTINCT e.id) as evenements
                    FROM categories c
                    LEFT JOIN services s ON c.id = s.categorie_id
                    LEFT JOIN produits p ON c.id = p.categorie_id
                    LEFT JOIN evenements e ON c.id = e.categorie_id
                    WHERE c.statut = 'active'
                    GROUP BY c.id, c.nom, c.slug
                    HAVING (services + produits + evenements) > 0
                    ORDER BY c.nom ASC";

            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getCategoriesWithContent - ' . $e->getMessage());
            return [];
        }
    }

    public function getMostUsed(int $limit = 10): array {
        try {
            $sql = "SELECT c.id, c.nom, c.slug, c.couleur,
                           COUNT(DISTINCT s.id) as nb_services,
                           COUNT(DISTINCT p.id) as nb_produits,
                           COUNT(DISTINCT e.id) as nb_evenements,
                           (COUNT(DISTINCT s.id) + COUNT(DISTINCT p.id) + COUNT(DISTINCT e.id)) as total
                    FROM categories c
                    LEFT JOIN services s ON c.id = s.categorie_id
                    LEFT JOIN produits p ON c.id = p.categorie_id
                    LEFT JOIN evenements e ON c.id = e.categorie_id
                    WHERE c.statut = 'active'
                    GROUP BY c.id, c.nom, c.slug, c.couleur
                    ORDER BY total DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getMostUsed - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Recherche et filtres
    // ─────────────────────────────────────────
    public function search(string $query, int $limit = 20): array {
        try {
            $sql = "SELECT c.* FROM categories c
                    WHERE (c.nom LIKE :query OR c.description LIKE :query OR c.slug LIKE :query)
                    AND c.statut = 'active'
                    ORDER BY 
                        CASE WHEN c.nom LIKE :query_exact THEN 1 ELSE 2 END,
                        c.nom ASC
                    LIMIT :limit";

            return $this->db->query($sql, [
                'query' => "%$query%",
                'query_exact' => $query,
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::search - ' . $e->getMessage());
            return [];
        }
    }

    public function getByColor(string $couleur): array {
        try {
            $sql = "SELECT c.* FROM categories c
                    WHERE c.couleur = :couleur AND c.statut = 'active'
                    ORDER BY c.nom ASC";

            return $this->db->query($sql, ['couleur' => $couleur]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getByColor - ' . $e->getMessage());
            return [];
        }
    }

    public function getRandomCategories(int $limit = 5): array {
        try {
            $sql = "SELECT c.* FROM categories c
                    WHERE c.statut = 'active'
                    ORDER BY RAND()
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getRandomCategories - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Export et API
    // ─────────────────────────────────────────
    public function export(): array {
        try {
            $sql = "SELECT c.id, c.nom, c.slug, c.description, c.couleur, c.parent_id, c.statut FROM categories c
                    WHERE c.statut = 'active'
                    ORDER BY c.parent_id ASC, c.ordre ASC";

            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Categorie::export - ' . $e->getMessage());
            return [];
        }
    }

    public function getApiListing(int $offset = 0, int $limit = 50): array {
        try {
            $sql = "SELECT c.id, c.nom, c.slug, c.description, c.icone, c.couleur, c.parent_id,
                           COUNT(DISTINCT s.id) as services,
                           COUNT(DISTINCT p.id) as produits
                    FROM categories c
                    LEFT JOIN services s ON c.id = s.categorie_id
                    LEFT JOIN produits p ON c.id = p.categorie_id
                    WHERE c.statut = 'active'
                    GROUP BY c.id
                    ORDER BY c.ordre ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Categorie::getApiListing - ' . $e->getMessage());
            return [];
        }
    }

    public function getNavigationMenu(): array {
        try {
            $categories = $this->getTree(0);

            $menu = [];
            foreach ($categories as $cat) {
                $item = [
                    'id' => $cat['id'],
                    'label' => $cat['nom'],
                    'url' => '/categories/' . $cat['slug'],
                    'icon' => $cat['icone'],
                    'color' => $cat['couleur'],
                    'children' => [],
                ];

                if (!empty($cat['children'])) {
                    foreach ($cat['children'] as $child) {
                        $item['children'][] = [
                            'id' => $child['id'],
                            'label' => $child['nom'],
                            'url' => '/categories/' . $child['slug'],
                            'icon' => $child['icone'],
                        ];
                    }
                }

                $menu[] = $item;
            }

            return $menu;
        } catch (Exception $e) {
            error_log('Erreur Categorie::getNavigationMenu - ' . $e->getMessage());
            return [];
        }
    }

    public function checkSlugExists(string $slug, int $excludeId = 0): bool {
        try {
            $where = "WHERE slug = :slug";
            if ($excludeId > 0) {
                $where .= " AND id != :exclude_id";
            }

            $sql = "SELECT COUNT(*) as count FROM categories $where";

            $params = ['slug' => $slug];
            if ($excludeId > 0) {
                $params['exclude_id'] = $excludeId;
            }

            $result = $this->db->query($sql, $params);
            return ($result[0]['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log('Erreur Categorie::checkSlugExists - ' . $e->getMessage());
            return false;
        }
    }

    public function duplicate(int $id, string $newName = ''): ?int {
        try {
            $original = $this->getById($id);
            if (!$original) {
                return null;
            }

            $name = !empty($newName) ? $newName : $original['nom'] . ' (Copie)';
            $slug = $this->generateSlug($name);

            $data = [
                'nom' => $name,
                'slug' => $slug,
                'description' => $original['description'],
                'icone' => $original['icone'],
                'couleur' => $original['couleur'],
                'parent_id' => $original['parent_id'],
                'statut' => 'inactive',
            ];

            return $this->create($data);
        } catch (Exception $e) {
            error_log('Erreur Categorie::duplicate - ' . $e->getMessage());
            return null;
        }
    }

    public function deleteWithTransfer(int $id, int $transferToId): bool {
        try {
            // Transférer tous les enfants
            $sql = "UPDATE categories SET parent_id = :new_parent WHERE parent_id = :old_parent";
            $this->db->execute($sql, ['new_parent' => $transferToId, 'old_parent' => $id]);

            // Supprimer la catégorie
            return $this->delete($id);
        } catch (Exception $e) {
            error_log('Erreur Categorie::deleteWithTransfer - ' . $e->getMessage());
            return false;
        }
    }
}
?>
