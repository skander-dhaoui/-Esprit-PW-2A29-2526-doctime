<?php

require_once __DIR__ . '/../config/database.php'';  // ✅
class Reply {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO reponses (article_id, user_id, reponse_parent_id, contenu, code, langue_code, marquee, created_at, updated_at)
                    VALUES (:article_id, :user_id, :reponse_parent_id, :contenu, :code, :langue_code, 0, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Reply::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, u.avatar,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    WHERE r.id = :id
                    GROUP BY r.id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Reply::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getByArticleOrderByRecent(int $articleId): array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, u.avatar,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    WHERE r.article_id = :article_id
                    GROUP BY r.id
                    ORDER BY r.marquee DESC, votes DESC, r.created_at DESC";

            return $this->db->query($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByArticleOrderByRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getByArticleOrderByVotes(int $articleId): array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, u.avatar,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    WHERE r.article_id = :article_id
                    GROUP BY r.id
                    ORDER BY r.marquee DESC, votes DESC";

            return $this->db->query($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByArticleOrderByVotes - ' . $e->getMessage());
            return [];
        }
    }

    public function getByArticleOrderByOldest(int $articleId): array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, u.avatar,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    WHERE r.article_id = :article_id
                    GROUP BY r.id
                    ORDER BY r.created_at ASC";

            return $this->db->query($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByArticleOrderByOldest - ' . $e->getMessage());
            return [];
        }
    }

    public function getMarkedByArticle(int $articleId): array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, u.avatar
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.article_id = :article_id AND r.marquee = 1";

            return $this->db->query($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getMarkedByArticle - ' . $e->getMessage());
            return [];
        }
    }

    public function getByArticle(int $articleId): array {
        try {
            $sql = "SELECT * FROM reponses WHERE article_id = :article_id ORDER BY created_at DESC";
            return $this->db->query($sql, ['article_id' => $articleId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByArticle - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserOrderByRecent(int $userId): array {
        try {
            $sql = "SELECT r.*, a.titre as article_titre,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN articles a ON r.article_id = a.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    WHERE r.user_id = :user_id
                    GROUP BY r.id
                    ORDER BY r.created_at DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByUserOrderByRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserOrderByVotes(int $userId): array {
        try {
            $sql = "SELECT r.*, a.titre as article_titre,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN articles a ON r.article_id = a.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    WHERE r.user_id = :user_id
                    GROUP BY r.id
                    ORDER BY votes DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByUserOrderByVotes - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserOrderByLikes(int $userId): array {
        try {
            $sql = "SELECT r.*, a.titre as article_titre,
                           COUNT(DISTINCT l.id) as likes
                    FROM reponses r
                    LEFT JOIN articles a ON r.article_id = a.id
                    LEFT JOIN favoris_reponses l ON r.id = l.reponse_id
                    WHERE r.user_id = :user_id
                    GROUP BY r.id
                    ORDER BY likes DESC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getByUserOrderByLikes - ' . $e->getMessage());
            return [];
        }
    }

    public function getLastReplyByUser(int $userId): ?array {
        try {
            $sql = "SELECT * FROM reponses WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->query($sql, ['user_id' => $userId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Reply::getLastReplyByUser - ' . $e->getMessage());
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

            $sql = "UPDATE reponses SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Reply::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM reponses WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Reply::delete - ' . $e->getMessage());
            return false;
        }
    }

    public function addVote(int $replyId, int $userId, string $type): bool {
        try {
            $sql = "INSERT INTO votes_reponses (reponse_id, user_id, type, created_at)
                    VALUES (:reponse_id, :user_id, :type, NOW())";

            return $this->db->execute($sql, [
                'reponse_id' => $replyId,
                'user_id' => $userId,
                'type' => $type,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Reply::addVote - ' . $e->getMessage());
            return false;
        }
    }

    public function removeVote(int $replyId, int $userId): bool {
        try {
            $sql = "DELETE FROM votes_reponses WHERE reponse_id = :reponse_id AND user_id = :user_id";
            return $this->db->execute($sql, ['reponse_id' => $replyId, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::removeVote - ' . $e->getMessage());
            return false;
        }
    }

    public function updateVote(int $replyId, int $userId, string $type): bool {
        try {
            $sql = "UPDATE votes_reponses SET type = :type WHERE reponse_id = :reponse_id AND user_id = :user_id";
            return $this->db->execute($sql, [
                'reponse_id' => $replyId,
                'user_id' => $userId,
                'type' => $type,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Reply::updateVote - ' . $e->getMessage());
            return false;
        }
    }

    public function getUserVote(int $replyId, int $userId): ?array {
        try {
            $sql = "SELECT * FROM votes_reponses WHERE reponse_id = :reponse_id AND user_id = :user_id";
            $result = $this->db->query($sql, ['reponse_id' => $replyId, 'user_id' => $userId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Reply::getUserVote - ' . $e->getMessage());
            return null;
        }
    }

    public function countVotes(int $replyId): int {
        try {
            $sql = "SELECT SUM(CASE WHEN type = 'upvote' THEN 1 ELSE -1 END) as total
                    FROM votes_reponses WHERE reponse_id = :reponse_id";

            $result = $this->db->query($sql, ['reponse_id' => $replyId]);
            return $result[0]['total'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Reply::countVotes - ' . $e->getMessage());
            return 0;
        }
    }

    public function getVotes(int $replyId): int {
        return $this->countVotes($replyId);
    }

    public function addLike(int $replyId, int $userId): bool {
        try {
            $sql = "INSERT INTO favoris_reponses (reponse_id, user_id, created_at)
                    VALUES (:reponse_id, :user_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()";

            return $this->db->execute($sql, ['reponse_id' => $replyId, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::addLike - ' . $e->getMessage());
            return false;
        }
    }

    public function removeLike(int $replyId, int $userId): bool {
        try {
            $sql = "DELETE FROM favoris_reponses WHERE reponse_id = :reponse_id AND user_id = :user_id";
            return $this->db->execute($sql, ['reponse_id' => $replyId, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Reply::removeLike - ' . $e->getMessage());
            return false;
        }
    }

    public function isLikedByUser(int $replyId, int $userId): bool {
        try {
            $sql = "SELECT 1 FROM favoris_reponses WHERE reponse_id = :reponse_id AND user_id = :user_id LIMIT 1";
            $result = $this->db->query($sql, ['reponse_id' => $replyId, 'user_id' => $userId]);
            return !empty($result);
        } catch (Exception $e) {
            error_log('Erreur Reply::isLikedByUser - ' . $e->getMessage());
            return false;
        }
    }

    public function addReport(int $replyId, int $userId, string $raison): bool {
        try {
            $sql = "INSERT INTO signalements_reponses (reponse_id, user_id, raison, created_at)
                    VALUES (:reponse_id, :user_id, :raison, NOW())";

            return $this->db->execute($sql, [
                'reponse_id' => $replyId,
                'user_id' => $userId,
                'raison' => $raison,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Reply::addReport - ' . $e->getMessage());
            return false;
        }
    }

    public function isReportedByUser(int $replyId, int $userId): bool {
        try {
            $sql = "SELECT 1 FROM signalements_reponses WHERE reponse_id = :reponse_id AND user_id = :user_id LIMIT 1";
            $result = $this->db->query($sql, ['reponse_id' => $replyId, 'user_id' => $userId]);
            return !empty($result);
        } catch (Exception $e) {
            error_log('Erreur Reply::isReportedByUser - ' . $e->getMessage());
            return false;
        }
    }

    public function getRecent(int $limit): array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, a.titre as article_titre
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN articles a ON r.article_id = a.id
                    ORDER BY r.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getPopular(int $limit): array {
        try {
            $sql = "SELECT r.*, u.nom, u.prenom, a.titre as article_titre,
                           SUM(CASE WHEN v.type = 'upvote' THEN 1 WHEN v.type = 'downvote' THEN -1 ELSE 0 END) as votes
                    FROM reponses r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN articles a ON r.article_id = a.id
                    LEFT JOIN votes_reponses v ON r.id = v.reponse_id
                    GROUP BY r.id
                    ORDER BY votes DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Reply::getPopular - ' . $e->getMessage());
            return [];
        }
    }
}
?>
