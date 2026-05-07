<?php
declare(strict_types=1);

namespace App\Repositories;

use \PDO;
use \Database;

class ArticleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM articles ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function countAll(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    }

    public function countThisMonth(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM articles WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO articles (titre, contenu, auteur_id, categorie, status, tags, image, created_at, updated_at) 
                VALUES (:titre, :contenu, :auteur_id, :categorie, :status, :tags, :image, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titre'     => $data['titre'] ?? '',
            ':contenu'   => $data['contenu'] ?? '',
            ':auteur_id' => $data['auteur_id'] ?? null,
            ':categorie' => $data['categorie'] ?? null,
            ':status'    => $data['status'] ?? 'brouillon',
            ':tags'      => $data['tags'] ?? null,
            ':image'     => $data['image'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $titre, string $contenu, ?int $auteurId): bool
    {
        $sql = "UPDATE articles SET titre = :titre, contenu = :contenu, auteur_id = :auteur_id, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':titre'     => $titre,
            ':contenu'   => $contenu,
            ':auteur_id' => $auteurId,
            ':id'        => $id,
        ]);
    }

    public function updateFull(int $id, string $titre, string $contenu, ?int $auteurId, ?string $image, ?string $categorie, ?string $tags, string $status): bool
    {
        $sql = "UPDATE articles SET titre = :titre, contenu = :contenu, auteur_id = :auteur_id, image = :image, categorie = :categorie, tags = :tags, status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
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
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE articles SET vues = vues + 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function getRepliesByArticle(int $articleId): array
    {
        $sql = "SELECT r.*, u.nom, u.prenom 
                FROM replies r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.article_id = :id 
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticlesWithReplyCount(): array
    {
        $sql = "SELECT a.*, COUNT(r.id) as nb_replies 
                FROM articles a 
                LEFT JOIN replies r ON a.id = r.article_id 
                GROUP BY a.id 
                ORDER BY a.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function advancedSearch(array $filters): array
    {
        $sql = "SELECT a.*, u.nom as auteur_nom, u.prenom as auteur_prenom 
                FROM articles a 
                LEFT JOIN users u ON a.auteur_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (a.titre LIKE :q OR a.contenu LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['categorie'])) {
            $sql .= " AND a.categorie = :cat";
            $params[':cat'] = $filters['categorie'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        }

        $tri = $filters['tri'] ?? 'created_at';
        $ordre = $filters['ordre'] ?? 'DESC';
        $sql .= " ORDER BY a.$tri $ordre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add other missing methods used by AdminController
    public function getCategories(): array {
        return $this->db->query("SELECT DISTINCT categorie FROM articles WHERE categorie IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAuteurs(): array {
        return $this->db->query("SELECT DISTINCT u.id, u.nom, u.prenom FROM articles a JOIN users u ON a.auteur_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatusDistribution(): array {
        return $this->db->query("SELECT status, COUNT(*) as count FROM articles GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryDistribution(): array {
        return $this->db->query("SELECT categorie, COUNT(*) as count FROM articles GROUP BY categorie")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticleWithReplies(int $id): ?array
    {
        $article = $this->getById($id);
        if (!$article) return null;
        $article['replies'] = $this->getRepliesByArticle($id);
        return $article;
    }

    public function getTopByViews(int $limit = 5): array {
        return $this->db->query("SELECT * FROM articles ORDER BY vues DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopByComments(int $limit = 5): array {
        return $this->db->query("SELECT a.*, COUNT(r.id) as comment_count FROM articles a LEFT JOIN replies r ON a.id = r.article_id GROUP BY a.id ORDER BY comment_count DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyTrend(int $months = 6): array {
        return $this->db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM articles GROUP BY month ORDER BY month DESC LIMIT $months")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalViews(): int {
        return (int) $this->db->query("SELECT SUM(vues) FROM articles")->fetchColumn();
    }

    public function getTotalLikes(): int {
        return (int) $this->db->query("SELECT SUM(likes) FROM articles")->fetchColumn();
    }
}
