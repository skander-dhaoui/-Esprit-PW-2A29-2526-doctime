<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Reply {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les commentaires d'un article
     */
    public function getByArticle(int $articleId): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, 
                    r.emoji, r.photo, r.date_reply,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id_article = :article_id
             ORDER BY r.date_reply ASC"
        );
        $stmt->execute([':id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByArticleRecent(int $articleId): array
    {
        $stmt = $this->getDb()->prepare(
            "SELECT r.*, COALESCE(CONCAT(u.nom,' ',u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id_article = :id ORDER BY r.date_reply DESC"
        );
        $stmt->execute([':id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->getDb()->prepare(
            "SELECT r.*, COALESCE(CONCAT(u.nom,' ',u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN users u ON u.id = r.user_id WHERE r.id_reply = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countByArticle(int $articleId): int
    {
        $stmt = $this->getDb()->prepare("SELECT COUNT(*) FROM reply WHERE id_article = :id");
        $stmt->execute([':id' => $articleId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply, ?int $userId = null): int
    {
        if ($userId === null && !empty($_SESSION['user_id'])) $userId = (int)$_SESSION['user_id'];
        $stmt = $this->getDb()->prepare(
            "INSERT INTO reply (id_article, user_id, type_reply, contenu_text, emoji, photo, auteur, date_reply)
             VALUES (:id_article, :user_id, :type_reply, :contenu_text, :emoji, :photo, :auteur, NOW())"
        );
        $stmt->execute([':id_article' => $articleId, ':user_id' => $userId, ':type_reply' => $typeReply,
                        ':contenu_text' => $contenuText, ':emoji' => $emoji, ':photo' => $photo,
                        ':auteur' => $auteur ?? 'Anonyme']);
        return (int)$this->getDb()->lastInsertId();
    }

    public function createMixte(int $articleId, ?string $contenuText, ?string $emoji,
                                ?string $imagePath, ?string $auteur, ?int $userId = null): int
    {
        $type = 'mixte';
        if (!empty($emoji) && empty($contenuText) && empty($imagePath))     $type = 'emoji';
        elseif (!empty($imagePath) && empty($contenuText) && empty($emoji)) $type = 'photo';
        elseif (!empty($contenuText) && empty($emoji) && empty($imagePath)) $type = 'text';
        return $this->create($articleId, $contenuText, $emoji, $imagePath, $auteur, $type, $userId);
    }

    public function update(int $id, int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply): bool
    {
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $stmt   = $this->getDb()->prepare(
            "UPDATE reply SET id_article=:id_article, user_id=:user_id, type_reply=:type_reply,
             contenu_text=:contenu_text, emoji=:emoji, photo=:photo, auteur=:auteur WHERE id_reply=:id"
        );
        return $stmt->execute([':id_article' => $articleId, ':user_id' => $userId, ':type_reply' => $typeReply,
                               ':contenu_text' => $contenuText, ':emoji' => $emoji, ':photo' => $photo,
                               ':auteur' => $auteur ?? 'Anonyme', ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        return $this->getDb()->prepare("DELETE FROM reply WHERE id_reply = :id")->execute([':id' => $id]);
    }

    public function deleteByArticle(int $articleId): bool
    {
        return $this->getDb()->prepare("DELETE FROM reply WHERE id_article = :id")->execute([':id' => $articleId]);
    }

    public function getLatest(int $limit = 10): array
    {
        $stmt = $this->getDb()->prepare(
            "SELECT r.*, a.titre AS article_titre, COALESCE(CONCAT(u.nom,' ',u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN article a ON r.id_article = a.id_article LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.date_reply DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->getDb()->prepare(
            "SELECT r.*, a.titre AS article_titre, COALESCE(CONCAT(u.nom,' ',u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN article a ON r.id_article = a.id_article LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.date_reply DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        return (int)$this->getDb()->query("SELECT COUNT(*) FROM reply")->fetchColumn();
    }
}