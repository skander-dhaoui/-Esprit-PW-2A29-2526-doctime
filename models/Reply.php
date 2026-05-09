<?php
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
        $stmt->execute([':article_id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
/**
 * Crée un commentaire mixte (texte + image possible)
 */
public function createMixte(int $articleId, ?string $contenuText, ?string $emoji, 
                           ?string $imagePath, ?string $auteur, ?int $userId = null): int {
    
    if ($userId === null && !empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    }
    
    $type = 'mixte';
    if (!empty($emoji) && empty($contenuText) && empty($imagePath)) {
        $type = 'emoji';
    } elseif (!empty($imagePath) && empty($contenuText) && empty($emoji)) {
        $type = 'photo';
    } elseif (!empty($contenuText) && empty($emoji) && empty($imagePath)) {
        $type = 'text';
    }
    
    $stmt = $this->db->prepare(
        "INSERT INTO reply (id_article, user_id, type_reply, contenu_text, emoji, photo, auteur, date_reply)
         VALUES (:article_id, :user_id, :type_reply, :contenu_text, :emoji, :photo, :auteur, NOW())"
    );
    $stmt->execute([
        ':article_id' => $articleId,
        ':user_id' => $userId,
        ':type_reply' => $type,
        ':contenu_text' => $contenuText,
        ':emoji' => $emoji,
        ':photo' => $imagePath,
        ':auteur' => $auteur ?? 'Anonyme'
    ]);
    return (int)$this->db->lastInsertId();
}
    /**
     * Récupère les commentaires d'un article du plus récent au plus ancien
     */
    public function getByArticleRecent(int $articleId): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, 
                    r.emoji, r.photo, r.date_reply,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id_article = :article_id
             ORDER BY r.date_reply DESC"
        );
        $stmt->execute([':article_id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un commentaire par son ID
     */
    public function getById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.user_id, r.type_reply, r.contenu_text, 
                    r.emoji, r.photo, r.date_reply,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id_reply = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
                       ?string $photo, ?string $auteur, string $typeReply, ?int $userId = null): int {
    
    // Si userId n'est pas passé, on essaie de le récupérer de la session
    if ($userId === null && !empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    }
    
    $stmt = $this->db->prepare(
        "INSERT INTO reply (id_article, user_id, type_reply, contenu_text, emoji, photo, auteur, date_reply)
         VALUES (:article_id, :user_id, :type_reply, :contenu_text, :emoji, :photo, :auteur, NOW())"
    );
    $stmt->execute([
        ':article_id' => $articleId,
        ':user_id' => $userId,
        ':type_reply' => $typeReply,
        ':contenu_text' => $contenuText,
        ':emoji' => $emoji,
        ':photo' => $photo,
        ':auteur' => $auteur ?? 'Anonyme'
    ]);
    return (int)$this->db->lastInsertId();
}

    /**
     * Met à jour un commentaire
     */
public function update(int $id, int $articleId, ?string $contenuText, ?string $emoji,
                       ?string $photo, ?string $auteur, string $typeReply): bool {
    $userId = null;
    if (!empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    }
    
    $stmt = $this->db->prepare(
        "UPDATE reply SET 
            id_article = :article_id,
            user_id = :user_id,
            type_reply = :type_reply,
            contenu_text = :contenu_text,
            emoji = :emoji,
            photo = :photo,
            auteur = :auteur
         WHERE id_reply = :id"
    );
    return $stmt->execute([
        ':article_id' => $articleId,
        ':user_id' => $userId,
        ':type_reply' => $typeReply,
        ':contenu_text' => $contenuText,
        ':emoji' => $emoji,
        ':photo' => $photo,
        ':auteur' => $auteur ?? 'Anonyme',
        ':id' => $id
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
     * Récupère les derniers commentaires
     */
    public function getLatest(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, 
                    r.emoji, r.photo, r.date_reply,
                    a.titre AS article_titre,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r
             LEFT JOIN article a ON r.id_article = a.id_article
             LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.date_reply DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les commentaires d'un auteur
     */
    public function getByAuthor(string $auteur, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, 
                    r.emoji, r.photo, r.date_reply,
                    a.titre AS article_titre,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r
             LEFT JOIN article a ON r.id_article = a.id_article
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.auteur = :auteur OR CONCAT(u.nom, ' ', u.prenom) = :auteur
             ORDER BY r.date_reply DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':auteur', $auteur, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les commentaires (pour l'admin)
     */
    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, 
                    r.emoji, r.photo, r.date_reply,
                    a.titre AS article_titre,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r
             LEFT JOIN article a ON r.id_article = a.id_article
             LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.date_reply DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre total de commentaires
     */
    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM reply");
        return (int)$stmt->fetchColumn();
    }

    // ─────────────────────────────────────────
    //  Méthodes privées
    // ─────────────────────────────────────────

    /**
     * Résout l'ID utilisateur à partir du nom ou de l'email
     */
    private function resolveUserId(?string $auteur): ?int {
        // Si utilisateur connecté, utiliser son ID
        if (!empty($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        // Si auteur fourni, chercher l'utilisateur correspondant
        if (!empty($auteur)) {
            $stmt = $this->db->prepare(
                "SELECT id FROM users 
                 WHERE CONCAT(nom, ' ', prenom) = :auteur OR email = :auteur
                 LIMIT 1"
            );
            $stmt->execute([':auteur' => $auteur]);
            $id = (int)$stmt->fetchColumn();
            if ($id > 0) {
                return $id;
            }
        }

        // Retourner null pour les commentaires anonymes
        return null;
    }
}
?>