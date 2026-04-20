<?php
require_once __DIR__ . '/../config/database.php';

class Article {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crée un nouvel article
     * Accepte soit un tableau associatif, soit des paramètres séparés
     */
    public function create($titreOrData, ?string $contenu = null, $auteurIdOrName = null): int {
        // Support appel avec tableau : create(['titre'=>..., 'contenu'=>..., 'auteur_id'=>...])
        if (is_array($titreOrData)) {
            $titre    = $titreOrData['titre']    ?? '';
            $contenu  = $titreOrData['contenu']  ?? '';
            $auteur_id = $titreOrData['auteur_id'] ?? null;
        } else {
            // Support appel avec paramètres séparés : create($titre, $contenu, $auteur)
            $titre    = $titreOrData;
            $auteur_id = null;
            // Si le 3ème paramètre est un int, c'est un auteur_id
            if (is_int($auteurIdOrName)) {
                $auteur_id = $auteurIdOrName;
            } elseif (!empty($auteurIdOrName)) {
                // C'est un nom d'auteur, on essaie de trouver l'ID
                $auteur_id = $this->resolveAuthorId($auteurIdOrName);
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO articles (titre, contenu, auteur_id, created_at) 
             VALUES (:titre, :contenu, :auteur_id, NOW())"
        );
        $stmt->execute([
            ':titre'     => $titre,
            ':contenu'   => $contenu,
            ':auteur_id' => $auteur_id,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Récupère un article par son ID avec le nombre de commentaires
     */
    public function getById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             WHERE a.id = :id
             GROUP BY a.id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les articles avec le nombre de commentaires
     */
    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les derniers articles
     */
    public function getLatest(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             GROUP BY a.id
             ORDER BY a.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
public function updateFull(int $id, string $titre, string $contenu, $auteur = null, ?string $image = null, 
                          ?string $categorie = null, ?string $tags = null, ?string $status = null): bool {
    $auteur_id = null;
    if (is_int($auteur)) {
        $auteur_id = $auteur;
    } elseif (!empty($auteur)) {
        $auteur_id = $this->resolveAuthorId($auteur);
    }
    
    $stmt = $this->db->prepare(
        "UPDATE articles SET 
            titre = :titre, 
            contenu = :contenu, 
            auteur_id = :auteur_id, 
            image = :image,
            categorie = :categorie,
            tags = :tags,
            status = :status,
            updated_at = NOW()
         WHERE id = :id"
    );
    return $stmt->execute([
        ':titre' => $titre,
        ':contenu' => $contenu,
        ':auteur_id' => $auteur_id,
        ':image' => $image,
        ':categorie' => $categorie,
        ':tags' => $tags,
        ':status' => $status,
        ':id' => $id,
    ]);
}
    /**
     * Met à jour un article
     */
public function update(int $id, string $titre, string $contenu, $auteur = null, ?string $image = null): bool {
    $auteur_id = null;
    if (is_int($auteur)) {
        $auteur_id = $auteur;
    } elseif (!empty($auteur)) {
        $auteur_id = $this->resolveAuthorId($auteur);
    }
    
    if ($image !== null) {
        $stmt = $this->db->prepare(
            "UPDATE articles SET titre = :titre, contenu = :contenu, auteur_id = :auteur_id, image = :image 
             WHERE id = :id"
        );
        return $stmt->execute([
            ':titre' => $titre,
            ':contenu' => $contenu,
            ':auteur_id' => $auteur_id,
            ':image' => $image,
            ':id' => $id,
        ]);
    } else {
        $stmt = $this->db->prepare(
            "UPDATE articles SET titre = :titre, contenu = :contenu, auteur_id = :auteur_id 
             WHERE id = :id"
        );
        return $stmt->execute([
            ':titre' => $titre,
            ':contenu' => $contenu,
            ':auteur_id' => $auteur_id,
            ':id' => $id,
        ]);
    }
}

    /**
     * Supprime un article et tous ses commentaires associés
     */
    public function delete(int $id): bool {
        try {
            $this->db->beginTransaction();

            $stmt1 = $this->db->prepare("DELETE FROM reply WHERE id_article = :id");
            $stmt1->execute([':id' => $id]);

            $stmt2 = $this->db->prepare("DELETE FROM articles WHERE id = :id");
            $result = $stmt2->execute([':id' => $id]);

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erreur Article::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Compteurs
    // ─────────────────────────────────────────

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM articles");
        return (int)$stmt->fetchColumn();
    }

    public function countThisMonth(): int {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM articles
             WHERE MONTH(created_at) = MONTH(NOW()) 
             AND YEAR(created_at)  = YEAR(NOW())"
        );
        return (int)$stmt->fetchColumn();
    }

    // ─────────────────────────────────────────
    //  Recherche
    // ─────────────────────────────────────────

    public function search(string $keyword): array {
        $kw   = '%' . $keyword . '%';
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             WHERE a.titre LIKE :kw1 OR a.contenu LIKE :kw2
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([':kw1' => $kw, ':kw2' => $kw]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategorie(string $categorie): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             WHERE a.categorie = :categorie
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([':categorie' => $categorie]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────
    //  Utilitaires
    // ─────────────────────────────────────────

    public function incrementViews(int $id): bool {
        $stmt = $this->db->prepare("UPDATE articles SET vues = vues + 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getPopular(int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             GROUP BY a.id
             ORDER BY a.vues DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAuthor(string $auteur): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             WHERE a.auteur_id = :auteur
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([':auteur' => $auteur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDate(string $date): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nom as auteur_name, COUNT(r.id_reply) AS nb_replies
             FROM articles a
             LEFT JOIN users u ON u.id = a.auteur_id
             LEFT JOIN reply r ON r.id_article = a.id
             WHERE DATE(a.created_at) = :date
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════════════════
    //  JOINTURES - Relation Articles ↔ Replies
    // ═══════════════════════════════════════════════════════════

    /**
     * Récupère toutes les replies d'un article (INNER JOIN)
     * Pattern: getRepliesByArticle($articleId)
     * 
     * @param int $articleId ID de l'article
     * @return array Liste des replies jointes avec données utilisateur
     */
    public function getRepliesByArticle(int $articleId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.id_reply, r.contenu_text, r.emoji, r.photo, r.auteur, r.type_reply, r.date_reply,
                        r.user_id, r.image_url, r.text_content,
                        u.id AS user_id_db, u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email,
                        a.id AS article_id, a.titre AS article_titre
                 FROM reply r
                 INNER JOIN articles a ON r.id_article = a.id
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.id_article = :article_id
                 ORDER BY r.date_reply DESC"
            );
            $stmt->execute([':article_id' => $articleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Article::getRepliesByArticle - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les articles avec le nombre de replies (LEFT JOIN)
     * Pattern: getArticlesWithReplyCount()
     * Utilisé pour afficher le formulaire de sélection
     * 
     * @return array Liste des articles avec comptage des replies
     */
    public function getArticlesWithReplyCount(): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT a.id, a.titre, a.slug,
                        COUNT(r.id_reply) AS nombre_replies
                 FROM articles a
                 LEFT JOIN reply r ON a.id = r.id_article
                 GROUP BY a.id, a.titre, a.slug
                 ORDER BY a.titre ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Article::getArticlesWithReplyCount - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un article avec toutes ses replies (JOINTURE complète)
     * Pattern: getArticleWithReplies($id)
     * 
     * @param int $id ID de l'article
     * @return array Données article + tableau replies
     */
    public function getArticleWithReplies(int $id): array {
        try {
            // Récupérer l'article
            $stmt = $this->db->prepare(
                "SELECT a.id, a.titre, a.slug, a.contenu, a.resume, a.image, 
                        a.auteur_id, a.categorie, a.tags, a.status, a.vues, a.likes, 
                        a.created_at, a.updated_at,
                        u.nom AS auteur_nom, u.prenom AS auteur_prenom
                 FROM articles a
                 LEFT JOIN users u ON a.auteur_id = u.id
                 WHERE a.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$article) return [];
            
            // Récupérer les replies de cet article
            $article['replies'] = $this->getRepliesByArticle($id);
            
            return $article;
        } catch (Exception $e) {
            error_log('Erreur Article::getArticleWithReplies - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Privé
    // ─────────────────────────────────────────

    private function resolveAuthorId(string $name): ?int {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE nom = :name OR CONCAT(nom, ' ', prenom) = :name LIMIT 1"
        );
        $stmt->execute([':name' => $name]);
        $id = (int)$stmt->fetchColumn();
        return $id > 0 ? $id : null;
    }
}
?>