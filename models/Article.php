<?php
require_once __DIR__ . '/../config/Database.php';

class Article {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }

    /**
     * Récupère tous les articles avec le nombre de commentaires
     */
    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT a.*, COUNT(r.id_reply) AS nb_replies
             FROM article a
             LEFT JOIN reply r ON r.id_article = a.id_article
             GROUP BY a.id_article
             ORDER BY a.date_creation DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Récupère un article par son ID
     */
    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM article WHERE id_article = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Compte le nombre total d'articles
     */
    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM article");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Compte le nombre d'articles créés ce mois-ci
     */
    public function countThisMonth(): int {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM article
             WHERE MONTH(date_creation) = MONTH(NOW()) 
             AND YEAR(date_creation) = YEAR(NOW())"
        );
        return (int)$stmt->fetchColumn();
    }

    /**
     * Crée un nouvel article
     */
    public function create(string $titre, string $contenu, ?string $auteur): int {
        $stmt = $this->db->prepare(
            "INSERT INTO article (titre, contenu, auteur, date_creation) 
             VALUES (:titre, :contenu, :auteur, NOW())"
        );
        $stmt->execute([
            ':titre'   => $titre,
            ':contenu' => $contenu,
            ':auteur'  => $auteur
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Met à jour un article
     */
    public function update(int $id, string $titre, string $contenu, ?string $auteur): bool {
        $stmt = $this->db->prepare(
            "UPDATE article SET titre = :titre, contenu = :contenu, auteur = :auteur 
             WHERE id_article = :id"
        );
        return $stmt->execute([
            ':titre'   => $titre,
            ':contenu' => $contenu,
            ':auteur'  => $auteur,
            ':id'      => $id
        ]);
    }

    /**
     * Supprime un article et tous ses commentaires associés
     */
    public function delete(int $id): bool {
        try {
            // Démarrer une transaction
            $this->db->beginTransaction();
            
            // Supprimer les commentaires associés
            $stmt1 = $this->db->prepare("DELETE FROM reply WHERE id_article = :id");
            $stmt1->execute([':id' => $id]);
            
            // Supprimer l'article
            $stmt2 = $this->db->prepare("DELETE FROM article WHERE id_article = :id");
            $result = $stmt2->execute([':id' => $id]);
            
            // Valider la transaction
            $this->db->commit();
            
            return $result;
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $this->db->rollBack();
            error_log('Erreur Article::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les derniers articles (pour le FrontOffice)
     */
    public function getLatest(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, COUNT(r.id_reply) AS nb_replies
             FROM article a
             LEFT JOIN reply r ON r.id_article = a.id_article
             GROUP BY a.id_article
             ORDER BY a.date_creation DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Recherche des articles par mot-clé
     */
    public function search(string $keyword): array {
        $keyword = '%' . $keyword . '%';
        $stmt = $this->db->prepare(
            "SELECT a.*, COUNT(r.id_reply) AS nb_replies
             FROM article a
             LEFT JOIN reply r ON r.id_article = a.id_article
             WHERE a.titre LIKE :keyword OR a.contenu LIKE :keyword
             GROUP BY a.id_article
             ORDER BY a.date_creation DESC"
        );
        $stmt->execute([':keyword' => $keyword]);
        return $stmt->fetchAll();
    }
}
?>