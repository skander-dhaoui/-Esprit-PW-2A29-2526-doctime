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

        $categorie = is_array($titreOrData) ? ($titreOrData['categorie'] ?? null) : null;
        $status    = is_array($titreOrData) ? ($titreOrData['status']    ?? 'brouillon') : 'brouillon';
        $tags      = is_array($titreOrData) ? ($titreOrData['tags']      ?? null) : null;

        $stmt = $this->db->prepare(
            "INSERT INTO articles (titre, contenu, auteur_id, categorie, status, tags, created_at)
             VALUES (:titre, :contenu, :auteur_id, :categorie, :status, :tags, NOW())"
        );
        $stmt->execute([
            ':titre'     => $titre,
            ':contenu'   => $contenu,
            ':auteur_id' => $auteur_id,
            ':categorie' => $categorie,
            ':status'    => $status,
            ':tags'      => $tags,
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

    public function advancedSearch(array $criteria): array {
        $sql = "SELECT a.*, u.nom as auteur_name, u.prenom as auteur_prenom, COUNT(r.id_reply) AS nb_replies
                FROM articles a
                LEFT JOIN users u ON u.id = a.auteur_id
                LEFT JOIN reply r ON r.id_article = a.id
                WHERE 1=1";
        $params = [];

        if (!empty($criteria['keyword'])) {
            $sql .= " AND (a.titre LIKE :kw1 OR a.contenu LIKE :kw2 OR a.tags LIKE :kw3)";
            $kw = '%' . $criteria['keyword'] . '%';
            $params[':kw1'] = $kw; $params[':kw2'] = $kw; $params[':kw3'] = $kw;
        }
        if (!empty($criteria['categorie'])) {
            $sql .= " AND a.categorie = :categorie";
            $params[':categorie'] = $criteria['categorie'];
        }
        if (!empty($criteria['status'])) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $criteria['status'];
        }
        if (!empty($criteria['auteur_id'])) {
            $sql .= " AND a.auteur_id = :auteur_id";
            $params[':auteur_id'] = (int)$criteria['auteur_id'];
        }
        if (!empty($criteria['date_min'])) {
            $sql .= " AND DATE(a.created_at) >= :date_min";
            $params[':date_min'] = $criteria['date_min'];
        }
        if (!empty($criteria['date_max'])) {
            $sql .= " AND DATE(a.created_at) <= :date_max";
            $params[':date_max'] = $criteria['date_max'];
        }
        if (!empty($criteria['tag'])) {
            $sql .= " AND a.tags LIKE :tag";
            $params[':tag'] = '%' . $criteria['tag'] . '%';
        }
        if (!empty($criteria['vues_min'])) {
            $sql .= " AND a.vues >= :vues_min";
            $params[':vues_min'] = (int)$criteria['vues_min'];
        }

        $sql .= " GROUP BY a.id";

        $tri = in_array($criteria['tri'] ?? '', ['created_at','titre','vues','likes']) ? $criteria['tri'] : 'created_at';
        $ordre = ($criteria['ordre'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY a.$tri $ordre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────
    //  Stats avancées
    // ─────────────────────────────────────────

    public function getCategories(): array {
        return $this->db->query("SELECT DISTINCT categorie FROM articles WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuteurs(): array {
        return $this->db->query("SELECT DISTINCT u.id, CONCAT(u.prenom, ' ', u.nom) as nom_complet FROM articles a JOIN users u ON a.auteur_id = u.id ORDER BY nom_complet")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatusDistribution(): array {
        return $this->db->query("SELECT status, COUNT(*) as total FROM articles GROUP BY status ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryDistribution(): array {
        return $this->db->query("SELECT COALESCE(categorie, 'Sans catégorie') as categorie, COUNT(*) as total FROM articles GROUP BY categorie ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopByViews(int $limit = 5): array {
        $stmt = $this->db->prepare("SELECT a.id, a.titre, a.vues, a.likes, a.status, u.nom as auteur_name FROM articles a LEFT JOIN users u ON a.auteur_id = u.id ORDER BY a.vues DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopByComments(int $limit = 5): array {
        return $this->db->query("SELECT a.id, a.titre, a.vues, a.status, COUNT(r.id_reply) as nb_replies, u.nom as auteur_name FROM articles a LEFT JOIN reply r ON r.id_article = a.id LEFT JOIN users u ON a.auteur_id = u.id GROUP BY a.id ORDER BY nb_replies DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyTrend(int $months = 6): array {
        return $this->db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as total FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH) GROUP BY mois ORDER BY mois ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalViews(): int {
        return (int)$this->db->query("SELECT COALESCE(SUM(vues), 0) FROM articles")->fetchColumn();
    }

    public function getTotalLikes(): int {
        return (int)$this->db->query("SELECT COALESCE(SUM(likes), 0) FROM articles")->fetchColumn();
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