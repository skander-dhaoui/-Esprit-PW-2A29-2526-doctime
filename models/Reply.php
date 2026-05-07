<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Reply
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByArticle(int $articleId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.id AS id_reply, r.article_id, r.article_id AS id_article,
                   r.parent_id, r.replay AS contenu_text, r.emoji, r.image AS photo,
                   r.created_at AS date_reply, r.user_id, r.status,
                   r.moderation_status, r.moderation_reason,
                   'text' AS type_reply,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id = r.id AND rl.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id = r.id AND rl.type = 'dislike') AS nb_dislikes
            FROM replies r
            LEFT JOIN users u ON u.id = r.user_id
            WHERE r.article_id = :article_id
              AND r.parent_id IS NULL
              AND (r.moderation_status = 'approved' OR r.moderation_status IS NULL)
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([':article_id' => $articleId]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($replies as &$reply) {
            $reply['children'] = $this->getChildren($reply['id']);
        }

        return $replies;
    }

    public function getChildren(int $parentId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.id AS id_reply, r.article_id, r.article_id AS id_article,
                   r.parent_id, r.replay AS contenu_text, r.emoji, r.image AS photo,
                   r.created_at AS date_reply, r.user_id, r.status,
                   r.moderation_status,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id = r.id AND rl.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id = r.id AND rl.type = 'dislike') AS nb_dislikes
            FROM replies r
            LEFT JOIN users u ON u.id = r.user_id
            WHERE r.parent_id = :parent_id
              AND (r.moderation_status = 'approved' OR r.moderation_status IS NULL)
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([':parent_id' => $parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($children as &$child) {
            $child['children'] = $this->getChildren($child['id']);
        }

        return $children;
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.id AS id_reply, r.article_id, r.article_id AS id_article,
                   r.parent_id, r.replay AS contenu_text, r.emoji, r.image AS photo,
                   r.created_at AS date_reply, r.user_id, r.status,
                   r.moderation_status, r.moderation_reason,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id = r.id AND rl.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM reply_likes rl WHERE rl.reply_id = r.id AND rl.type = 'dislike') AS nb_dislikes
            FROM replies r
            LEFT JOIN users u ON u.id = r.user_id
            WHERE r.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.id AS id_reply, r.article_id, r.parent_id,
                   r.replay AS contenu_text, r.emoji, r.image AS photo,
                   r.created_at AS date_reply, r.user_id, r.status,
                   r.moderation_status, r.moderation_reason,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur,
                   a.titre AS article_titre
            FROM replies r
            LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN articles a ON a.id = r.article_id
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM replies")->fetchColumn();
    }

    public function countByArticle(int $articleId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM replies WHERE article_id = :id");
        $stmt->execute([':id' => $articleId]);
        return (int) $stmt->fetchColumn();
    }

    // ── LIKES ─────────────────────────────────────────────

    public function getUserLike(int $replyId, int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT type FROM reply_likes WHERE reply_id = ? AND user_id = ?");
        $stmt->execute([$replyId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['type'] : null;
    }

    public function toggleLike(int $replyId, int $userId, string $type): array
    {
        $existing = $this->getUserLike($replyId, $userId);

        if ($existing === $type) {
            $this->db->prepare("DELETE FROM reply_likes WHERE reply_id = ? AND user_id = ?")
                     ->execute([$replyId, $userId]);
            $action = 'removed';
        } elseif ($existing !== null) {
            $this->db->prepare("UPDATE reply_likes SET type = ? WHERE reply_id = ? AND user_id = ?")
                     ->execute([$type, $replyId, $userId]);
            $action = 'changed';
        } else {
            $this->db->prepare("INSERT INTO reply_likes (reply_id, user_id, type) VALUES (?, ?, ?)")
                     ->execute([$replyId, $userId, $type]);
            $action = 'added';
        }

        $stmt = $this->db->prepare("
            SELECT SUM(type='like') AS likes, SUM(type='dislike') AS dislikes
            FROM reply_likes WHERE reply_id = ?
        ");
        $stmt->execute([$replyId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'action'   => $action,
            'likes'    => (int)($counts['likes']    ?? 0),
            'dislikes' => (int)($counts['dislikes'] ?? 0),
        ];
    }

    // ── MODÉRATION ────────────────────────────────────────

    public function setModerationStatus(int $id, string $status, ?string $reason = null): void
    {
        $stmt = $this->db->prepare("
            UPDATE replies
            SET moderation_status = :status,
                moderation_reason = :reason,
                moderated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':status' => $status, ':reason' => $reason, ':id' => $id]);
    }

    // ── CRUD ──────────────────────────────────────────────

    public function create(int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply,
                           ?int $userId = null, ?int $parentId = null): int
    {
        if ($userId === null && !empty($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }

        $replay = $contenuText ?? $emoji ?? '📷 Image';

        $stmt = $this->db->prepare("
            INSERT INTO replies (article_id, parent_id, user_id, replay, emoji, image, status, moderation_status, created_at)
            VALUES (:article_id, :parent_id, :user_id, :replay, :emoji, :image, 'approuvé', 'pending', NOW())
        ");
        $stmt->execute([
            ':article_id' => $articleId,
            ':parent_id'  => $parentId,
            ':user_id'    => $userId,
            ':replay'     => $replay,
            ':emoji'      => $emoji,
            ':image'      => $photo,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function createMixte(int $articleId, ?string $contenuText, ?string $emoji,
                                ?string $imagePath, ?string $auteur,
                                ?int $userId = null, ?int $parentId = null): int
    {
        return $this->create($articleId, $contenuText, $emoji, $imagePath,
                             $auteur, 'text', $userId, $parentId);
    }

    public function update(int $id, int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply): bool
    {
        $replay = $contenuText ?? $emoji ?? '📷 Image';
        $stmt = $this->db->prepare("
            UPDATE replies SET replay = :replay, emoji = :emoji, image = :image WHERE id = :id
        ");
        return $stmt->execute([':replay' => $replay, ':emoji' => $emoji, ':image' => $photo, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM replies WHERE id = ?")->execute([$id]);
    }

    public function deleteByArticle(int $articleId): bool
    {
        return $this->db->prepare("DELETE FROM replies WHERE article_id = ?")->execute([$articleId]);
    }

    public function getLatest(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.id AS id_reply, r.article_id,
                   r.replay AS contenu_text, r.emoji, r.image AS photo,
                   r.created_at AS date_reply, r.user_id, r.moderation_status,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur,
                   a.titre AS article_titre
            FROM replies r
            LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN articles a ON a.id = r.article_id
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}