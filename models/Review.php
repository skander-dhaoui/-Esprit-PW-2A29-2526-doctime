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
     * Analyse le sentiment d'un avis en français.
     */
    public static function analyzeSentimentText(string $content, int $rating = 0): array {
        $text = self::normalizeText($content);

        $positiveWords = [
            'super', 'superbe', 'genial', 'bien', 'bon', 'bonne', 'excellent', 'excellente',
            'excellents', 'excellentes', 'experience', 'parfait', 'parfaite', 'merci',
            'recommande', 'recommander', 'satisfait', 'satisfaite', 'top', 'incroyable',
            'bravo', 'agreable', 'gentil', 'gentille', 'professionnel', 'professionnelle',
            'rapide', 'efficace', 'magnifique', 'formidable', 'sympathique', 'merveilleux',
            'merveilleuse', 'fantastique', 'extraordinaire', 'heureux', 'heureuse', 'content',
            'contente', 'adore', 'aime', 'aimer', 'love', 'amazing', 'great', 'perfect'
        ];

        $negativeWords = [
            'nul', 'nulle', 'mauvais', 'mauvaise', 'pire', 'decu', 'decue', 'horrible',
            'lent', 'lente', 'catastrophe', 'eviter', 'froid', 'froide', 'incompetent',
            'incompetente', 'desagreable', 'cher', 'chere', 'arnaque', 'decevant',
            'decevante', 'mediocre', 'terrible', 'honteux', 'honteuse', 'probleme',
            'triste', 'mecontent', 'mecontente', 'hate', 'awful', 'bad', 'worst'
        ];

        $negations = ['pas', 'ne', 'n', 'jamais', 'aucun', 'aucune', 'sans'];
        $intensifiers = ['tres', 'très', 'vraiment', 'tellement', 'hyper', 'super'];

        $score = 0;
        $words = preg_split('/[\s\.,!?;:\-_\(\)\[\]\"\'\/]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $count = count($words);

        for ($i = 0; $i < $count; $i++) {
            $word = $words[$i];
            $nextWord = $i < $count - 1 ? $words[$i + 1] : '';
            $previousWord = $i > 0 ? $words[$i - 1] : '';
            $weight = in_array($previousWord, $intensifiers, true) ? 3 : 2;

            if (in_array($word, $negations, true) && $nextWord !== '') {
                if (in_array($nextWord, $positiveWords, true)) {
                    $score -= 4;
                    $i++;
                    continue;
                }
                if (in_array($nextWord, $negativeWords, true)) {
                    $score += 2;
                    $i++;
                    continue;
                }
            }

            if (in_array($word, $positiveWords, true)) {
                $score += $weight;
            } elseif (in_array($word, $negativeWords, true)) {
                $score -= $weight;
            }
        }

        if (abs($score) < 2) {
            if ($rating >= 4) {
                $score += 2;
            } elseif ($rating > 0 && $rating <= 2) {
                $score -= 2;
            }
        }

        $label = $score >= 2 ? 'positive' : ($score <= -2 ? 'negative' : 'neutral');

        return [
            'label' => $label,
            'score' => max(-1, min(1, round($score / 10, 2))),
            'raw_score' => $score,
        ];
    }

    private static function normalizeText(string $text): string {
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

        return strtr($text, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'œ' => 'oe', 'æ' => 'ae',
        ]);
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
