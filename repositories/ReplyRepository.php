<?php

class ReplyRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByArticle($articleId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, u.prenom as auteur_prenom, u.nom as auteur_nom 
            FROM replies r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.article_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById($id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM replies WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO replies (article_id, user_id, type_reply, contenu_text, emoji, photo, auteur, date_reply)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['article_id'],
            $data['user_id'],
            $data['type_reply'] ?? 'text',
            $data['contenu_text'] ?? null,
            $data['emoji'] ?? null,
            $data['photo'] ?? null,
            $data['auteur'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update($id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE replies SET 
                type_reply = ?, 
                contenu_text = ?, 
                emoji = ?, 
                photo = ?, 
                auteur = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['type_reply'] ?? 'text',
            $data['contenu_text'] ?? null,
            $data['emoji'] ?? null,
            $data['photo'] ?? null,
            $data['auteur'] ?? null,
            $id
        ]);
    }

    public function delete($id): bool {
        $stmt = $this->db->prepare("DELETE FROM replies WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function all(): array {
        $stmt = $this->db->prepare("SELECT * FROM replies ORDER BY date_reply DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
