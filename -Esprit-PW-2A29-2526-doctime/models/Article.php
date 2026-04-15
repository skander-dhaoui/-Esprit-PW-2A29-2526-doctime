<?php

require_once __DIR__ . '/../config/database.php';
class Article {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO articles (titre, description, contenu, categorie, user_id, code, langue_code, vues, votes, resolu, fermé, created_at, updated_at)
                    VALUES (:titre, :description, :contenu, :categorie, :user_id, :code, :langue_code, 0, 0, 0, 0, NOW(), NOW())";

            $result = $this->db->execute($sql, [
                'titre' => $data['titre'],
                'description' => $data['description'],
                'contenu' => $data['contenu'],
                'categorie' => $data['categorie'],
                'user_id' => $data['user_id'],
                'code' => $data['code'] ?? null,
                'langue_code' => $data['langue_code'] ?? 'php',
            ]);

            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Article::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar, 
                           COUNT(DISTINCT r.id) as nb_reponses,
                           COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    WHERE a.id = :id
                    GROUP BY a.id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Article::getById - ' . $e->getMessage());
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

            $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Article::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM articles WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Article::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getRecent(int $offset, int $limit, string $filter = 'all', string $search = '', string $tag = ''): array {
        try {
            $where = "WHERE a.fermé = 0";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            } elseif ($filter === 'closed') {
                $where .= " AND a.fermé = 1";
            }

            if (!empty($search)) {
                $where .= " AND (a.titre LIKE :search OR a.description LIKE :search)";
            }

            $tagJoin = '';
            if (!empty($tag)) {
                $tagJoin = "LEFT JOIN article_tags at ON a.id = at.article_id
                           LEFT JOIN tags t ON at.tag_id = t.id";
                $where .= " AND t.nom = :tag";
            }

            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar,
                           COUNT(DISTINCT r.id) as nb_reponses,
                           COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $tagJoin
                    $where
                    GROUP BY a.id
                    ORDER BY a.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($tag)) {
                $params['tag'] = $tag;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Article::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getPopular(int $offset, int $limit, string $filter = 'all', string $search = '', string $tag = ''): array {
        try {
            $where = "WHERE a.fermé = 0";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            }

            if (!empty($search)) {
                $where .= " AND (a.titre LIKE :search OR a.description LIKE :search)";
            }

            $tagJoin = '';
            if (!empty($tag)) {
                $tagJoin = "LEFT JOIN article_tags at ON a.id = at.article_id
                           LEFT JOIN tags t ON at.tag_id = t.id";
                $where .= " AND t.nom = :tag";
            }

            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar,
                           COUNT(DISTINCT r.id) as nb_reponses,
                           COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $tagJoin
                    $where
                    GROUP BY a.id
                    ORDER BY a.vues DESC, a.votes DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($tag)) {
                $params['tag'] = $tag;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Article::getPopular - ' . $e->getMessage());
            return [];
        }
    }

    public function getUnanswered(int $offset, int $limit, string $search = '', string $tag = ''): array {
        try {
            $where = "WHERE nb_reponses = 0 AND a.fermé = 0";

            if (!empty($search)) {
                $where .= " AND (a.titre LIKE :search OR a.description LIKE :search)";
            }

            $tagJoin = '';
            if (!empty($tag)) {
                $tagJoin = "LEFT JOIN article_tags at ON a.id = at.article_id
                           LEFT JOIN tags t ON at.tag_id = t.id";
                $where .= " AND t.nom = :tag";
            }

            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar,
                           (SELECT COUNT(*) FROM reponses WHERE article_id = a.id) as nb_reponses,
                           (SELECT COUNT(*) FROM votes_articles WHERE article_id = a.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    $tagJoin
                    $where
                    ORDER BY a.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($tag)) {
                $params['tag'] = $tag;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Article::getUnanswered - ' . $e->getMessage());
            return [];
        }
    }

    public function getResolved(int $offset, int $limit, string $search = '', string $tag = ''): array {
        try {
            $where = "WHERE a.resolu = 1 AND a.fermé = 0";

            if (!empty($search)) {
                $where .= " AND (a.titre LIKE :search OR a.description LIKE :search)";
            }

            $tagJoin = '';
            if (!empty($tag)) {
                $tagJoin = "LEFT JOIN article_tags at ON a.id = at.article_id
                           LEFT JOIN tags t ON at.tag_id = t.id";
                $where .= " AND t.nom = :tag";
            }

            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar,
                           COUNT(DISTINCT r.id) as nb_reponses,
                           COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $tagJoin
                    $where
                    GROUP BY a.id
                    ORDER BY a.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($tag)) {
                $params['tag'] = $tag;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Article::getResolved - ' . $e->getMessage());
            return [];
        }
    }

    public function getByViews(int $offset, int $limit, string $filter = 'all', string $search = '', string $tag = ''): array {
        try {
            $where = "WHERE a.fermé = 0";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            }

            if (!empty($search)) {
                $where .= " AND (a.titre LIKE :search OR a.description LIKE :search)";
            }

            $tagJoin = '';
            if (!empty($tag)) {
                $tagJoin = "LEFT JOIN article_tags at ON a.id = at.article_id
                           LEFT JOIN tags t ON at.tag_id = t.id";
                $where .= " AND t.nom = :tag";
            }

            $sql = "SELECT a.*, u.nom, u.prenom, u.avatar,
                           COUNT(DISTINCT r.id) as nb_reponses,
                           COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $tagJoin
                    $where
                    GROUP BY a.id
                    ORDER BY a.vues DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($tag)) {
                $params['tag'] = $tag;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Article::getByViews - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUser(int $userId): array {
        try {
            $sql = "SELECT a.*, COUNT(DISTINCT r.id) as nb_reponses, COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    WHERE a.user_id = :user_id
                    GROUP BY a.id
                    ORDER BY a.created_at DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserRecent(int $userId, string $filter = 'all'): array {
        try {
            $where = "WHERE a.user_id = :user_id";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0 AND a.fermé = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            } elseif ($filter === 'closed') {
                $where .= " AND a.fermé = 1";
            }

            $sql = "SELECT a.*, COUNT(DISTINCT r.id) as nb_reponses, COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $where
                    GROUP BY a.id
                    ORDER BY a.created_at DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::getByUserRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserPopular(int $userId, string $filter = 'all'): array {
        try {
            $where = "WHERE a.user_id = :user_id";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0 AND a.fermé = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            } elseif ($filter === 'closed') {
                $where .= " AND a.fermé = 1";
            }

            $sql = "SELECT a.*, COUNT(DISTINCT r.id) as nb_reponses, COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $where
                    GROUP BY a.id
                    ORDER BY a.vues DESC, a.votes DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::getByUserPopular - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserViews(int $userId, string $filter = 'all'): array {
        try {
            $where = "WHERE a.user_id = :user_id";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0 AND a.fermé = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            } elseif ($filter === 'closed') {
                $where .= " AND a.fermé = 1";
            }

            $sql = "SELECT a.*, COUNT(DISTINCT r.id) as nb_reponses, COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    $where
                    GROUP BY a.id
                    ORDER BY a.vues DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::getByUserViews - ' . $e->getMessage());
            return [];
        }
    }

    public function getByTag(int $tagId, int $offset, int $limit): array {
        try {
            $sql = "SELECT DISTINCT a.*, u.nom, u.prenom, u.avatar,
                           COUNT(DISTINCT r.id) as nb_reponses,
                           COUNT(DISTINCT v.id) as nb_votes
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN reponses r ON a.id = r.article_id
                    LEFT JOIN votes_articles v ON a.id = v.article_id
                    WHERE at.tag_id = :tag_id AND a.fermé = 0
                    GROUP BY a.id
                    ORDER BY a.created_at DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['tag_id' => $tagId, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Article::getByTag - ' . $e->getMessage());
            return [];
        }
    }

    public function getRelated(int $articleId, int $limit): array {
        try {
            $article = $this->getById($articleId);
            if (!$article) {
                return [];
            }

            $sql = "SELECT DISTINCT a.*, COUNT(DISTINCT r.id) as nb_reponses
                    FROM articles a
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN article_tags at2 ON :article_id = (SELECT article_id FROM article_tags WHERE tag_id = at.tag_id LIMIT 1)
                    LEFT JOIN reponses r ON a.id = r.article_id
                    WHERE a.id != :article_id AND a.categorie = :categorie AND a.fermé = 0
                    GROUP BY a.id
                    ORDER BY a.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, [
                'article_id' => $articleId,
                'categorie' => $article['categorie'],
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Article::getRelated - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Compteurs
    // ─────────────────────────────────────────
    public function countAll(string $filter = 'all', string $search = '', string $tag = ''): int {
        try {
            $where = "WHERE a.fermé = 0";

            if ($filter === 'open') {
                $where .= " AND a.resolu = 0";
            } elseif ($filter === 'resolved') {
                $where .= " AND a.resolu = 1";
            } elseif ($filter === 'closed') {
                $where .= " AND a.fermé = 1";
            }

            if (!empty($search)) {
                $where .= " AND (a.titre LIKE :search OR a.description LIKE :search)";
            }

            $tagJoin = '';
            if (!empty($tag)) {
                $tagJoin = "LEFT JOIN article_tags at ON a.id = at.article_id
                           LEFT JOIN tags t ON at.tag_id = t.id";
                $where .= " AND t.nom = :tag";
            }

            $sql = "SELECT COUNT(*) as count
                    FROM articles a
                    $tagJoin
                    $where";

            $params = [];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($tag)) {
                $params['tag'] = $tag;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Article::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByTag(int $tagId): int {
        try {
            $sql = "SELECT COUNT(DISTINCT a.id) as count
                    FROM articles a
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    WHERE at.tag_id = :tag_id AND a.fermé = 0";

            $result = $this->db->query($sql, ['tag_id' => $tagId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Article::countByTag - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Votes
    // ─────────────────────────────────────────
    public function addVote(int $articleId, int $userId, string $type): bool {
        try {
            $sql = "INSERT INTO votes_articles (article_id, user_id, type, created_at)
                    VALUES (:article_id, :user_id, :type, NOW())";

            return $this->db->execute($sql, [
                'article_id' => $articleId,
                'user_id' => $userId,
                'type' => $type,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Article::addVote - ' . $e->getMessage());
            return false;
        }
    }

    public function removeVote(int $articleId, int $userId): bool {
        try {
            $sql = "DELETE FROM votes_articles WHERE article_id = :article_id AND user_id = :user_id";
            return $this->db->execute($sql, ['article_id' => $articleId, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::removeVote - ' . $e->getMessage());
            return false;
        }
    }

    public function updateVote(int $articleId, int $userId, string $type): bool {
        try {
            $sql = "UPDATE votes_articles SET type = :type WHERE article_id = :article_id AND user_id = :user_id";
            return $this->db->execute($sql, [
                'article_id' => $articleId,
                'user_id' => $userId,
                'type' => $type,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Article::updateVote - ' . $e->getMessage());
            return false;
        }
    }

    public function getUserVote(int $articleId, int $userId): ?array {
        try {
            $sql = "SELECT * FROM votes_articles WHERE article_id = :article_id AND user_id = :user_id";
            $result = $this->db->query($sql, ['article_id' => $articleId, 'user_id' => $userId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Article::getUserVote - ' . $e->getMessage());
            return null;
        }
    }

    public function countVotes(int $articleId): int {
        try {
            $sql = "SELECT 
                           SUM(CASE WHEN type = 'upvote' THEN 1 ELSE -1 END) as total
                    FROM votes_articles
                    WHERE article_id = :article_id";

            $result = $this->db->query($sql, ['article_id' => $articleId]);
            return $result[0]['total'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Article::countVotes - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Favoris
    // ─────────────────────────────────────────
    public function addLike(int $articleId, int $userId): bool {
        try {
            $sql = "INSERT INTO favoris_articles (article_id, user_id, created_at)
                    VALUES (:article_id, :user_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()";

            return $this->db->execute($sql, ['article_id' => $articleId, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::addLike - ' . $e->getMessage());
            return false;
        }
    }

    public function removeLike(int $articleId, int $userId): bool {
        try {
            $sql = "DELETE FROM favoris_articles WHERE article_id = :article_id AND user_id = :user_id";
            return $this->db->execute($sql, ['article_id' => $articleId, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Article::removeLike - ' . $e->getMessage());
            return false;
        }
    }

    public function isLikedByUser(int $articleId, int $userId): bool {
        try {
            $sql = "SELECT 1 FROM favoris_articles WHERE article_id = :article_id AND user_id = :user_id LIMIT 1";
            $result = $this->db->query($sql, ['article_id' => $articleId, 'user_id' => $userId]);
            return !empty($result);
        } catch (Exception $e) {
            error_log('Erreur Article::isLikedByUser - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Signalements
    // ─────────────────────────────────────────
    public function addReport(int $articleId, int $userId, string $raison): bool {
        try {
            $sql = "INSERT INTO signalements_articles (article_id, user_id, raison, created_at)
                    VALUES (:article_id, :user_id, :raison, NOW())";

            return $this->db->execute($sql, [
                'article_id' => $articleId,
                'user_id' => $userId,
                'raison' => $raison,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Article::addReport - ' . $e->getMessage());
            return false;
        }
    }

    public function isReportedByUser(int $articleId, int $userId): bool {
        try {
            $sql = "SELECT 1 FROM signalements_articles WHERE article_id = :article_id AND user_id = :user_id LIMIT 1";
            $result = $this->db->query($sql, ['article_id' => $articleId, 'user_id' => $userId]);
            return !empty($result);
        } catch (Exception $e) {
            error_log('Erreur Article::isReportedByUser - ' . $e->getMessage());
            return false;
        }
    }

    public function deleteReports(int $articleId): bool {
        try {
            $sql = "DELETE FROM signalements_articles WHERE article_id = :article_id";
            return $this->db->execute($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::deleteReports - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Tags
    // ─────────────────────────────────────────
    public function getAllTags(): array {
        try {
            $sql = "SELECT * FROM tags ORDER BY nom ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Article::getAllTags - ' . $e->getMessage());
            return [];
        }
    }

    public function getPopularTags(int $limit = 10): array {
        try {
            $sql = "SELECT t.*, COUNT(at.id) as usage
                    FROM tags t
                    LEFT JOIN article_tags at ON t.id = at.tag_id
                    GROUP BY t.id
                    ORDER BY usage DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Article::getPopularTags - ' . $e->getMessage());
            return [];
        }
    }

    public function getArticleTags(int $articleId): array {
        try {
            $sql = "SELECT t.* FROM tags t
                    LEFT JOIN article_tags at ON t.id = at.tag_id
                    WHERE at.article_id = :article_id";

            return $this->db->query($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::getArticleTags - ' . $e->getMessage());
            return [];
        }
    }

    public function findOrCreateTag(string $tagName): ?array {
        try {
            $tagName = trim($tagName);
            $sql = "SELECT * FROM tags WHERE LOWER(nom) = LOWER(:nom)";
            $result = $this->db->query($sql, ['nom' => $tagName]);

            if (!empty($result)) {
                return $result[0];
            }

            $sql = "INSERT INTO tags (nom, created_at) VALUES (:nom, NOW())";
            $this->db->execute($sql, ['nom' => $tagName]);

            $tagId = $this->db->lastInsertId();
            return ['id' => $tagId, 'nom' => $tagName];
        } catch (Exception $e) {
            error_log('Erreur Article::findOrCreateTag - ' . $e->getMessage());
            return null;
        }
    }

    public function attachTag(int $articleId, int $tagId): bool {
        try {
            $sql = "INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (:article_id, :tag_id)";
            return $this->db->execute($sql, ['article_id' => $articleId, 'tag_id' => $tagId]);
        } catch (Exception $e) {
            error_log('Erreur Article::attachTag - ' . $e->getMessage());
            return false;
        }
    }

    public function detachAllTags(int $articleId): bool {
        try {
            $sql = "DELETE FROM article_tags WHERE article_id = :article_id";
            return $this->db->execute($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::detachAllTags - ' . $e->getMessage());
            return false;
        }
    }

    public function findTagByName(string $tagName): ?array {
        try {
            $sql = "SELECT * FROM tags WHERE LOWER(nom) = LOWER(:nom)";
            $result = $this->db->query($sql, ['nom' => $tagName]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Article::findTagByName - ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────
    //  Divers
    // ─────────────────────────────────────────
    public function incrementViews(int $articleId): bool {
        try {
            $sql = "UPDATE articles SET vues = vues + 1 WHERE id = :id";
            return $this->db->execute($sql, ['id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::incrementViews - ' . $e->getMessage());
            return false;
        }
    }

    public function incrementReplies(int $articleId): bool {
        try {
            $sql = "UPDATE articles SET nb_reponses = nb_reponses + 1 WHERE id = :id";
            return $this->db->execute($sql, ['id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::incrementReplies - ' . $e->getMessage());
            return false;
        }
    }

    public function decrementReplies(int $articleId): bool {
        try {
            $sql = "UPDATE articles SET nb_reponses = nb_reponses - 1 WHERE id = :id";
            return $this->db->execute($sql, ['id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::decrementReplies - ' . $e->getMessage());
            return false;
        }
    }

    public function getLastArticleByUser(int $userId): ?array {
        try {
            $sql = "SELECT * FROM articles WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->query($sql, ['user_id' => $userId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Article::getLastArticleByUser - ' . $e->getMessage());
            return null;
        }
    }

    public function deleteVotes(int $articleId): bool {
        try {
            $sql = "DELETE FROM votes_articles WHERE article_id = :article_id";
            return $this->db->execute($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::deleteVotes - ' . $e->getMessage());
            return false;
        }
    }

    public function deleteLikes(int $articleId): bool {
        try {
            $sql = "DELETE FROM favoris_articles WHERE article_id = :article_id";
            return $this->db->execute($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Article::deleteLikes - ' . $e->getMessage());
            return false;
        }
    }

    public function getCategories(): array {
        try {
            $sql = "SELECT DISTINCT categorie FROM articles WHERE categorie IS NOT NULL ORDER BY categorie ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Article::getCategories - ' . $e->getMessage());
            return [];
        }
    }

    public function getRecentApi(int $limit): array {
        try {
            $sql = "SELECT a.id, a.titre, a.description, a.vues, a.created_at, u.nom, u.prenom
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE a.fermé = 0
                    ORDER BY a.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Article::getRecentApi - ' . $e->getMessage());
            return [];
        }
    }

    public function getPopularApi(int $limit): array {
        try {
            $sql = "SELECT a.id, a.titre, a.description, a.vues, a.created_at, u.nom, u.prenom
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE a.fermé = 0
                    ORDER BY a.vues DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Article::getPopularApi - ' . $e->getMessage());
            return [];
        }
    }

    public function getUnansweredApi(int $limit): array {
        try {
            $sql = "SELECT a.id, a.titre, a.description, a.vues, a.created_at, u.nom, u.prenom,
                           (SELECT COUNT(*) FROM reponses WHERE article_id = a.id) as nb_reponses
                    FROM articles a
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE a.fermé = 0 AND (SELECT COUNT(*) FROM reponses WHERE article_id = a.id) = 0
                    ORDER BY a.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Article::getUnansweredApi - ' . $e->getMessage());
            return [];
        }
    }
}
?>
