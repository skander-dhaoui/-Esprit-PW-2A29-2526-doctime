<?php

class Review {
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère la connexion PDO
     */
    public function getConnection() {
        return $this->db;
    }

    /**
     * Crée un nouvel avis
     */
    public function create(array $data): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reviews (user_id, rating, title, content, sentiment, sentiment_score, has_profanity, is_approved)
                VALUES (:user_id, :rating, :title, :content, :sentiment, :sentiment_score, :has_profanity, :is_approved)
            ");

            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':rating' => $data['rating'],
                ':title' => $data['title'],
                ':content' => $data['content'],
                ':sentiment' => $data['sentiment'] ?? null,
                ':sentiment_score' => $data['sentiment_score'] ?? null,
                ':has_profanity' => $data['has_profanity'] ? 1 : 0,
                ':is_approved' => $data['is_approved'] ? 1 : 0
            ]);

            return [
                'success' => true,
                'review_id' => (int) $this->db->lastInsertId(),
                'message' => 'Avis créé avec succès'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère tous les avis approuvés
     */
    public function getApproved(int $limit = 10, int $offset = 0): array {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.prenom, u.nom, u.email
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.is_approved = 1 AND r.deleted_at IS NULL
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les avis en attente de modération
     */
    public function getPending(int $limit = 20): array {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.prenom, u.nom, u.email
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.is_approved = 0 AND r.deleted_at IS NULL
                ORDER BY r.created_at ASC
                LIMIT :limit
            ");

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Approuve un avis
     */
    public function approve(int $reviewId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = :id");
            $stmt->execute([':id' => $reviewId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rejette un avis
     */
    public function reject(int $reviewId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE reviews SET deleted_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $reviewId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère les statistiques des avis
     */
    public function getStats(): array {
        try {
            $total = $this->db->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 1 AND deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $byRating = $this->db->query("
                SELECT rating, COUNT(*) as count FROM reviews 
                WHERE is_approved = 1 AND deleted_at IS NULL
                GROUP BY rating
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $bySentiment = $this->db->query("
                SELECT sentiment, COUNT(*) as count FROM reviews 
                WHERE is_approved = 1 AND deleted_at IS NULL
                GROUP BY sentiment
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $avgRating = $this->db->query("SELECT AVG(rating) as avg FROM reviews WHERE is_approved = 1 AND deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0;

            $sentimentData = [];
            foreach ($bySentiment as $item) {
                $sentimentData[$item['sentiment']] = (int)$item['count'];
            }

            return [
                'total' => (int) $total,
                'average_rating' => round((float) $avgRating, 1),
                'by_rating' => array_combine(
                    array_column($byRating, 'rating'),
                    array_column($byRating, 'count')
                ) ?: [],
                'by_sentiment' => $sentimentData
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'average_rating' => 0, 'by_rating' => [], 'by_sentiment' => []];
        }
    }

    /**
     * Ajoute une emoji à un avis
     */
    public function addEmoji(int $reviewId, string $emoji): bool {
        try {
            $stmt = $this->db->prepare("INSERT INTO review_emojis (review_id, emoji) VALUES (:review_id, :emoji)");
            $stmt->execute([':review_id' => $reviewId, ':emoji' => $emoji]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère les emojis d'un avis
     */
    public function getEmojis(int $reviewId): array {
        try {
            $stmt = $this->db->prepare("SELECT emoji FROM review_emojis WHERE review_id = :review_id ORDER BY created_at");
            $stmt->execute([':review_id' => $reviewId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'emoji');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère le nombre total d'avis
     */
    public function count(bool $approvedOnly = true): int {
        try {
            $query = "SELECT COUNT(*) as count FROM reviews WHERE deleted_at IS NULL";
            if ($approvedOnly) {
                $query .= " AND is_approved = 1";
            }
            $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Récupère un avis par ID
     */
    public function getById(int $reviewId): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.prenom, u.nom
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.id = :id
            ");
            $stmt->execute([':id' => $reviewId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
