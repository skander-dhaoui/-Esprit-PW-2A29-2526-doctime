<?php
require_once __DIR__ . '/../config/Database.php';

class Reply {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }

    /**
     * Récupère tous les commentaires d'un article
     */
    public function getByArticle(int $articleId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM reply WHERE id_article = :article_id ORDER BY date_reply ASC"
        );
        $stmt->execute([':article_id' => $articleId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les commentaires d'un article du plus récent au plus ancien
     */
    public function getByArticleRecent(int $articleId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM reply WHERE id_article = :article_id ORDER BY date_reply DESC"
        );
        $stmt->execute([':article_id' => $articleId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un commentaire par son ID
     */
    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM reply WHERE id_reply = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Compte le nombre de commentaires pour un article
     */
    public function countByArticle(int $articleId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reply WHERE id_article = :article_id");
        $stmt->execute([':article_id' => $articleId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Crée un nouveau commentaire
     */
    public function create(int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply): int {
        $stmt = $this->db->prepare(
            "INSERT INTO reply (id_article, contenu_text, emoji, photo, auteur, type_reply, date_reply)
             VALUES (:article_id, :contenu_text, :emoji, :photo, :auteur, :type_reply, NOW())"
        );
        $stmt->execute([
            ':article_id'    => $articleId,
            ':contenu_text'  => $contenuText,
            ':emoji'         => $emoji,
            ':photo'         => $photo,
            ':auteur'        => $auteur,
            ':type_reply'    => $typeReply
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Met à jour un commentaire
     */
    public function update(int $id, int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply): bool {
        $stmt = $this->db->prepare(
            "UPDATE reply SET 
                id_article = :article_id, 
                contenu_text = :contenu_text, 
                emoji = :emoji, 
                photo = :photo, 
                auteur = :auteur, 
                type_reply = :type_reply
             WHERE id_reply = :id"
        );
        return $stmt->execute([
            ':article_id'    => $articleId,
            ':contenu_text'  => $contenuText,
            ':emoji'         => $emoji,
            ':photo'         => $photo,
            ':auteur'        => $auteur,
            ':type_reply'    => $typeReply,
            ':id'            => $id
        ]);
    }

    /**
     * Supprime un commentaire
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM reply WHERE id_reply = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Supprime tous les commentaires d'un article
     */
    public function deleteByArticle(int $articleId): bool {
        $stmt = $this->db->prepare("DELETE FROM reply WHERE id_article = :article_id");
        return $stmt->execute([':article_id' => $articleId]);
    }

    /**
     * Récupère les derniers commentaires (pour le FrontOffice)
     */
    public function getLatest(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, a.titre as article_titre
             FROM reply r
             LEFT JOIN article a ON r.id_article = a.id_article
             ORDER BY r.date_reply DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère les commentaires d'un auteur
     */
    public function getByAuthor(string $auteur, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, a.titre as article_titre
             FROM reply r
             LEFT JOIN article a ON r.id_article = a.id_article
             WHERE r.auteur = :auteur
             ORDER BY r.date_reply DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':auteur', $auteur, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>