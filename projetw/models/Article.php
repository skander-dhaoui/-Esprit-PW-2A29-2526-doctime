<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Article
{
    private PDO $db;

    private int     $id;
    private string  $titre;
    private string  $contenu;
    private ?int    $auteurId;
    private ?string $categorie;
    private string  $status;
    private ?string $tags;
    private ?string $image;
    private int     $vues;
    private int     $likes;
    private string  $createdAt;
    private string  $updatedAt;

    public function __construct(array $data = [])
    {
        $this->db = Database::getInstance()->getConnection();

        $this->id        = (int)    ($data['id']         ?? $data['id_article'] ?? 0);
        $this->titre     = (string) ($data['titre']      ?? '');
        $this->contenu   = (string) ($data['contenu']    ?? '');
        $this->auteurId  =          (($data['auteur_id'] ?? null) !== null ? (int)$data['auteur_id'] : null);
        $this->categorie =          ($data['categorie']  ?? null);
        $this->status    = (string) ($data['status']     ?? 'brouillon');
        $this->tags      =          ($data['tags']       ?? null);
        $this->image     =          ($data['image']      ?? null);
        $this->vues      = (int)    ($data['vues']       ?? 0);
        $this->likes     = (int)    ($data['likes']      ?? 0);
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->updatedAt = (string) ($data['updated_at'] ?? '');
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────
    public function getId(): int            { return $this->id; }
    public function getTitre(): string      { return $this->titre; }
    public function getContenu(): string    { return $this->contenu; }
    public function getAuteurId(): ?int     { return $this->auteurId; }
    public function getCategorie(): ?string { return $this->categorie; }
    public function getStatus(): string     { return $this->status; }
    public function getTags(): ?string      { return $this->tags; }
    public function getImage(): ?string     { return $this->image; }
    public function getVues(): int          { return $this->vues; }
    public function getLikes(): int         { return $this->likes; }
    public function getCreatedAt(): string  { return $this->createdAt; }
    public function getUpdatedAt(): string  { return $this->updatedAt; }

    // ── Setters ──────────────────────────────────────────
    public function setId(int $v): void            { $this->id        = $v; }
    public function setTitre(string $v): void       { $this->titre     = $v; }
    public function setContenu(string $v): void     { $this->contenu   = $v; }
    public function setAuteurId(?int $v): void      { $this->auteurId  = $v; }
    public function setCategorie(?string $v): void  { $this->categorie = $v; }
    public function setStatus(string $v): void      { $this->status    = $v; }
    public function setTags(?string $v): void       { $this->tags      = $v; }
    public function setImage(?string $v): void      { $this->image     = $v; }
    public function setVues(int $v): void           { $this->vues      = $v; }
    public function setLikes(int $v): void          { $this->likes     = $v; }
    public function setCreatedAt(string $v): void   { $this->createdAt = $v; }
    public function setUpdatedAt(string $v): void   { $this->updatedAt = $v; }

    // ── Méthodes Base de Données ─────────────────────────

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT a.*,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur_name,
                   (SELECT COUNT(*) FROM replies r WHERE r.article_id = a.id) AS nb_replies,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'dislike') AS nb_dislikes
            FROM articles a
            LEFT JOIN users u ON u.id = a.auteur_id
            WHERE a.status = 'publié' AND (a.moderation_status = 'approved' OR a.moderation_status IS NULL)
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur_name,
                   (SELECT COUNT(*) FROM replies r WHERE r.article_id = a.id) AS nb_replies,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'dislike') AS nb_dislikes
            FROM articles a
            LEFT JOIN users u ON u.id = a.auteur_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ── RECHERCHE AVANCÉE ─────────────────────────────────

    public function search(string $query): array
    {
        $q = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT a.*,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur_name,
                   (SELECT COUNT(*) FROM replies r WHERE r.article_id = a.id) AS nb_replies,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'dislike') AS nb_dislikes
            FROM articles a
            LEFT JOIN users u ON u.id = a.auteur_id
            WHERE a.status = 'publié'
              AND (a.moderation_status = 'approved' OR a.moderation_status IS NULL)
              AND (
                  a.titre   LIKE :q
                  OR a.contenu LIKE :q2
                  OR CONCAT(u.prenom, ' ', u.nom) LIKE :q3
              )
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':q' => $q, ':q2' => $q, ':q3' => $q]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function advancedSearch(array $filters): array
    {
        $where  = ["a.status = 'publié'", "(a.moderation_status = 'approved' OR a.moderation_status IS NULL)"];
        $params = [];

        if (!empty($filters['keyword'])) {
            $q = '%' . $filters['keyword'] . '%';
            $where[] = "(a.titre LIKE :kw OR a.contenu LIKE :kw2 OR CONCAT(u.prenom,' ',u.nom) LIKE :kw3)";
            $params[':kw']  = $q;
            $params[':kw2'] = $q;
            $params[':kw3'] = $q;
        }
        if (!empty($filters['categorie'])) {
            $where[]              = "a.categorie = :categorie";
            $params[':categorie'] = $filters['categorie'];
        }
        if (!empty($filters['tag'])) {
            $where[]        = "a.tags LIKE :tag";
            $params[':tag'] = '%' . $filters['tag'] . '%';
        }
        if (!empty($filters['date_min'])) {
            $where[]             = "a.created_at >= :date_min";
            $params[':date_min'] = $filters['date_min'];
        }

        $sql = "
            SELECT a.*,
                   CONCAT(u.prenom, ' ', u.nom) AS auteur_name,
                   (SELECT COUNT(*) FROM replies r WHERE r.article_id = a.id) AS nb_replies,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'like') AS nb_likes,
                   (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.type = 'dislike') AS nb_dislikes
            FROM articles a
            LEFT JOIN users u ON u.id = a.auteur_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── LIKES ─────────────────────────────────────────────

    public function getUserLike(int $articleId, int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT type FROM article_likes WHERE article_id = ? AND user_id = ?");
        $stmt->execute([$articleId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['type'] : null;
    }

    public function toggleLike(int $articleId, int $userId, string $type): array
    {
        $existing = $this->getUserLike($articleId, $userId);

        if ($existing === $type) {
            // Annuler le like/dislike
            $this->db->prepare("DELETE FROM article_likes WHERE article_id = ? AND user_id = ?")
                     ->execute([$articleId, $userId]);
            $action = 'removed';
        } elseif ($existing !== null) {
            // Changer de like à dislike ou vice versa
            $this->db->prepare("UPDATE article_likes SET type = ? WHERE article_id = ? AND user_id = ?")
                     ->execute([$type, $articleId, $userId]);
            $action = 'changed';
        } else {
            // Nouveau like/dislike
            $this->db->prepare("INSERT INTO article_likes (article_id, user_id, type) VALUES (?, ?, ?)")
                     ->execute([$articleId, $userId, $type]);
            $action = 'added';
        }

        // Retourner les nouveaux compteurs
        $stmt = $this->db->prepare("
            SELECT
                SUM(type = 'like') AS likes,
                SUM(type = 'dislike') AS dislikes
            FROM article_likes WHERE article_id = ?
        ");
        $stmt->execute([$articleId]);
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
            UPDATE articles
            SET moderation_status = :status,
                moderation_reason = :reason,
                moderated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':status' => $status, ':reason' => $reason, ':id' => $id]);
    }

    public function getPendingModeration(): array
    {
        $stmt = $this->db->query("
            SELECT a.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_name
            FROM articles a
            LEFT JOIN users u ON u.id = a.auteur_id
            WHERE a.moderation_status = 'pending'
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── CRUD ──────────────────────────────────────────────

    public function countAll(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    }

    public function countThisMonth(): int
    {
        return (int) $this->db->query("
            SELECT COUNT(*) FROM articles
            WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
        ")->fetchColumn();
    }

    public function create(array $data): int
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['titre'])));
        $slug = $slug . '-' . time();

        $stmt = $this->db->prepare("
            INSERT INTO articles (titre, slug, contenu, auteur_id, image, categorie, tags, status, moderation_status, created_at, updated_at)
            VALUES (:titre, :slug, :contenu, :auteur_id, :image, :categorie, :tags, :status, 'pending', NOW(), NOW())
        ");
        $stmt->execute([
            ':titre'     => $data['titre'],
            ':slug'      => $slug,
            ':contenu'   => $data['contenu'],
            ':auteur_id' => $data['auteur_id'] ?? null,
            ':image'     => $data['image']     ?? null,
            ':categorie' => $data['categorie'] ?? null,
            ':tags'      => $data['tags']      ?? null,
            ':status'    => $data['status']    ?? 'publié',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $titre, string $contenu, ?int $auteurId): void
    {
        $stmt = $this->db->prepare("
            UPDATE articles
            SET titre = :titre, contenu = :contenu, auteur_id = :auteur_id, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':titre' => $titre, ':contenu' => $contenu, ':auteur_id' => $auteurId, ':id' => $id]);
    }

    public function updateFull(int $id, string $titre, string $contenu, ?int $auteurId,
                               ?string $image, ?string $categorie, ?string $tags, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE articles
            SET titre = :titre, contenu = :contenu, auteur_id = :auteur_id,
                image = :image, categorie = :categorie, tags = :tags,
                status = :status, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':titre'     => $titre,
            ':contenu'   => $contenu,
            ':auteur_id' => $auteurId,
            ':image'     => $image,
            ':categorie' => $categorie,
            ':tags'      => $tags,
            ':status'    => $status,
            ':id'        => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function incrementViews(int $id): void
    {
        $this->db->prepare("UPDATE articles SET vues = vues + 1 WHERE id = ?")->execute([$id]);
    }

    public function getArticlesWithReplyCount(): array
    {
        $stmt = $this->db->query("
            SELECT a.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_name,
                   COUNT(r.id) AS nb_replies
            FROM articles a
            LEFT JOIN users u ON u.id = a.auteur_id
            LEFT JOIN replies r ON r.article_id = a.id
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRepliesByArticle(int $articleId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_name
            FROM replies r
            LEFT JOIN users u ON u.id = r.user_id
            WHERE r.article_id = :id
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([':id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticleWithReplies(int $id): array
    {
        $article = $this->getById($id);
        if (!$article) return [];
        $article['replies'] = $this->getRepliesByArticle($id);
        return $article;
    }
}