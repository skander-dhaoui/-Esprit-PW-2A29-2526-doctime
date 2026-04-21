<?php
require_once __DIR__ . '/../config/database.php';

class Reply {

    // ─── Connexion BDD ────────────────────────────────────────────
    private PDO $db;

    // ─── Attributs (propriétés encapsulées) ──────────────────────
    private ?int    $id_reply     = null;
    private ?int    $id_article   = null;
    private ?int    $user_id      = null;
    private string  $type_reply   = 'text';
    private ?string $contenu_text = null;
    private ?string $emoji        = null;
    private ?string $photo        = null;
    private string  $auteur       = 'Anonyme';
    private ?string $date_reply   = null;

    // ─── Constructeur ─────────────────────────────────────────────
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ═══════════════════════════════════════════════════════════════
    //  GETTERS
    // ═══════════════════════════════════════════════════════════════
    public function getIdReply(): ?int        { return $this->id_reply; }
    public function getIdArticle(): ?int      { return $this->id_article; }
    public function getUserId(): ?int         { return $this->user_id; }
    public function getTypeReply(): string    { return $this->type_reply; }
    public function getContenuText(): ?string { return $this->contenu_text; }
    public function getEmoji(): ?string       { return $this->emoji; }
    public function getPhoto(): ?string       { return $this->photo; }
    public function getAuteur(): string       { return $this->auteur; }
    public function getDateReply(): ?string   { return $this->date_reply; }

    // ═══════════════════════════════════════════════════════════════
    //  SETTERS (retournent $this pour chaining fluide)
    // ═══════════════════════════════════════════════════════════════
    public function setIdReply(?int $v): self      { $this->id_reply     = $v; return $this; }
    public function setIdArticle(?int $v): self    { $this->id_article   = $v; return $this; }
    public function setUserId(?int $v): self       { $this->user_id      = $v; return $this; }
    public function setTypeReply(string $v): self  { $this->type_reply   = $v; return $this; }
    public function setContenuText(?string $v): self { $this->contenu_text = $v; return $this; }
    public function setEmoji(?string $v): self     { $this->emoji        = $v; return $this; }
    public function setPhoto(?string $v): self     { $this->photo        = $v; return $this; }
    public function setAuteur(string $v): self     { $this->auteur       = $v; return $this; }

    // ═══════════════════════════════════════════════════════════════
    //  HYDRATATION — remplit l'objet depuis un tableau (ex: row BDD)
    // ═══════════════════════════════════════════════════════════════
    public function hydrate(array $data): static {
        if (isset($data['id_reply']))     $this->id_reply     = (int)$data['id_reply'];
        if (isset($data['id_article']))   $this->id_article   = (int)$data['id_article'];
        if (isset($data['user_id']))      $this->user_id      = (int)$data['user_id'];
        if (isset($data['type_reply']))   $this->type_reply   = $data['type_reply'];
        if (isset($data['contenu_text'])) $this->contenu_text = $data['contenu_text'];
        if (isset($data['emoji']))        $this->emoji        = $data['emoji'];
        if (isset($data['photo']))        $this->photo        = $data['photo'];
        if (isset($data['auteur']))       $this->auteur       = $data['auteur'];
        if (isset($data['date_reply']))   $this->date_reply   = $data['date_reply'];
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════
    //  CRUD — méthodes persistance
    // ═══════════════════════════════════════════════════════════════

    public function getByArticle(int $articleId): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, r.emoji, r.photo, r.date_reply,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id_article = :article_id ORDER BY r.date_reply ASC"
        );
        $stmt->execute([':article_id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByArticleRecent(int $articleId): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, r.emoji, r.photo, r.date_reply,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id_article = :article_id ORDER BY r.date_reply DESC"
        );
        $stmt->execute([':article_id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.user_id, r.type_reply, r.contenu_text, r.emoji, r.photo, r.date_reply,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN users u ON u.id = r.user_id WHERE r.id_reply = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countByArticle(int $articleId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reply WHERE id_article = :article_id");
        $stmt->execute([':article_id' => $articleId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply, ?int $userId = null): int {
        if ($userId === null && !empty($_SESSION['user_id'])) $userId = (int)$_SESSION['user_id'];
        $stmt = $this->db->prepare(
            "INSERT INTO reply (id_article, user_id, type_reply, contenu_text, emoji, photo, auteur, date_reply)
             VALUES (:article_id, :user_id, :type_reply, :contenu_text, :emoji, :photo, :auteur, NOW())"
        );
        $stmt->execute([':article_id' => $articleId, ':user_id' => $userId, ':type_reply' => $typeReply,
                        ':contenu_text' => $contenuText, ':emoji' => $emoji, ':photo' => $photo, ':auteur' => $auteur ?? 'Anonyme']);
        return (int)$this->db->lastInsertId();
    }

    public function createMixte(int $articleId, ?string $contenuText, ?string $emoji,
                                ?string $imagePath, ?string $auteur, ?int $userId = null): int {
        if ($userId === null && !empty($_SESSION['user_id'])) $userId = (int)$_SESSION['user_id'];
        $type = 'mixte';
        if (!empty($emoji) && empty($contenuText) && empty($imagePath))       $type = 'emoji';
        elseif (!empty($imagePath) && empty($contenuText) && empty($emoji))   $type = 'photo';
        elseif (!empty($contenuText) && empty($emoji) && empty($imagePath))   $type = 'text';
        return $this->create($articleId, $contenuText, $emoji, $imagePath, $auteur, $type, $userId);
    }

    public function update(int $id, int $articleId, ?string $contenuText, ?string $emoji,
                           ?string $photo, ?string $auteur, string $typeReply): bool {
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $stmt = $this->db->prepare(
            "UPDATE reply SET id_article=:article_id, user_id=:user_id, type_reply=:type_reply,
             contenu_text=:contenu_text, emoji=:emoji, photo=:photo, auteur=:auteur WHERE id_reply=:id"
        );
        return $stmt->execute([':article_id' => $articleId, ':user_id' => $userId, ':type_reply' => $typeReply,
                               ':contenu_text' => $contenuText, ':emoji' => $emoji, ':photo' => $photo,
                               ':auteur' => $auteur ?? 'Anonyme', ':id' => $id]);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM reply WHERE id_reply = :id")->execute([':id' => $id]);
    }

    public function deleteByArticle(int $articleId): bool {
        return $this->db->prepare("DELETE FROM reply WHERE id_article = :article_id")->execute([':article_id' => $articleId]);
    }

    public function getLatest(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, r.emoji, r.photo, r.date_reply,
                    a.titre AS article_titre,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN articles a ON r.id_article = a.id LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.date_reply DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(int $limit = 100): array {
        $stmt = $this->db->prepare(
            "SELECT r.id_reply, r.id_article, r.type_reply, r.contenu_text, r.emoji, r.photo, r.date_reply,
                    a.titre AS article_titre,
                    COALESCE(CONCAT(u.nom, ' ', u.prenom), r.auteur, 'Anonyme') AS auteur
             FROM reply r LEFT JOIN articles a ON r.id_article = a.id LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.date_reply DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM reply")->fetchColumn();
    }
}
?>