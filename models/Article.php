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
             WHERE a.titre LIKE :kw OR a.contenu LIKE :kw
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([':kw' => $kw]);
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