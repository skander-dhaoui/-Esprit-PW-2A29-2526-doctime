<?php
if (class_exists('ReviewController')) return;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Review.php';

class ReviewController {

    private $review;

    public function __construct() {
        $this->review = new Review();
    }

    /**
     * Récupère les avis approuvés pour l'accueil
     */
    public function index() {
        $page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $reviews = $this->review->getApproved($perPage, $offset);
        $totalCount = $this->review->count(true);
        $totalPages = ceil($totalCount / $perPage);

        // Enrichir avec emojis
        foreach ($reviews as &$review) {
            $review['emojis'] = $this->review->getEmojis($review['id']);
        }

        $stats = $this->review->getStats();

        return [
            'reviews' => $reviews,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $totalCount
            ]
        ];
    }

    /**
     * Crée un nouvel avis (avec API)
     */
    public function store() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $errors = $this->validateReviewData($data);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Filtrage des insultes
        $hasProfanity = $this->filterProfanity($data['content']);
        
        // Analyse de sentiment
        $sentiment = $this->analyzeSentiment($data['content']);

        // Approuvé seulement si pas de profanités
        $isApproved = !$hasProfanity;

        $reviewData = [
            'user_id' => $_SESSION['user_id'],
            'rating' => (int)$data['rating'],
            'title' => trim($data['title']),
            'content' => trim($data['content']),
            'sentiment' => $sentiment['label'],
            'sentiment_score' => $sentiment['score'],
            'has_profanity' => $hasProfanity,
            'is_approved' => $isApproved ? 1 : 0
        ];

        $result = $this->review->create($reviewData);

        if ($result['success']) {
            // Ajouter les emojis s'il y en a
            if (!empty($data['emojis']) && is_array($data['emojis'])) {
                foreach ($data['emojis'] as $emoji) {
                    $this->review->addEmoji($result['review_id'], $emoji);
                }
            }

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => $isApproved ? 'Avis publié!' : 'Avis créé, en attente de modération',
                'review_id' => $result['review_id'],
                'requires_moderation' => !$isApproved
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }

        exit;
    }

    /**
     * Admin: Approuve un avis
     */
    public function approve() {
        header('Content-Type: application/json');

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }

        $reviewId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($this->review->approve($reviewId)) {
            echo json_encode(['success' => true, 'message' => 'Avis approuvé']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur']);
        }

        exit;
    }

    /**
     * Admin: Rejette un avis
     */
    public function reject() {
        header('Content-Type: application/json');

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }

        $reviewId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($this->review->reject($reviewId)) {
            echo json_encode(['success' => true, 'message' => 'Avis rejeté']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur']);
        }

        exit;
    }

    /**
     * Valide les données de l'avis
     */
    private function validateReviewData(array $data): array {
        $errors = [];

        if (empty($data['content'])) {
            $errors['content'] = 'Le contenu est requis';
        } elseif (strlen($data['content']) < 10) {
            $errors['content'] = 'L\'avis doit contenir au moins 10 caractères';
        } elseif (strlen($data['content']) > 2000) {
            $errors['content'] = 'L\'avis ne peut pas dépasser 2000 caractères';
        }

        if (empty($data['title'])) {
            $errors['title'] = 'Le titre est requis';
        } elseif (strlen($data['title']) < 3 || strlen($data['title']) > 100) {
            $errors['title'] = 'Le titre doit avoir entre 3 et 100 caractères';
        }

        if (empty($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            $errors['rating'] = 'La note doit être entre 1 et 5';
        }

        return $errors;
    }

    /**
     * Filtre les insultes et contenu inapproprié
     */
    private function filterProfanity(string $text): bool {
        // Liste noire de mots inappropriés
        $profanities = [
            // Français
            'connard', 'salaud', 'débile', 'con', 'nul', 'pourri', 'merde',
            'putain', 'enculé', 'fdp', 'fumier', 'pourrave', 'pédé',
            // Anglais courants
            'fuck', 'shit', 'asshole', 'bastard', 'bitch', 'hell',
            // Autres
            'trou du cul', 'enfoiré', 'foutaise'
        ];

        $textLower = strtolower($text);
        
        foreach ($profanities as $word) {
            if (strpos($textLower, $word) !== false) {
                return true;
            }
        }

        // Détection de spam avec caractères répétés
        if (preg_match('/(.)\1{4,}/', $text)) {
            return true;
        }

        return false;
    }

    /**
     * Analyse le sentiment du texte (positif, neutre, négatif)
     */
    private function analyzeSentiment(string $text): array {
        $textLower = strtolower($text);

        // Mots positifs
        $positive = [
            'bon', 'bonne', 'excellent', 'extraordinaire', 'fantastique',
            'merveilleux', 'super', 'génial', 'formidable', 'parfait',
            'love', 'love', 'wonderful', 'amazing', 'great', 'perfect',
            'très bien', 'j\'aime', 'content', 'heureux', 'satisfait'
        ];

        // Mots négatifs
        $negative = [
            'mauvais', 'horrible', 'nul', 'débile', 'pourri', 'décevant',
            'mauvaise', 'catastrophe', 'décalogue', 'problème',
            'hate', 'terrible', 'awful', 'bad', 'disappointed', 'worst',
            'très mal', 'pas content', 'mécontent', 'triste'
        ];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positive as $word) {
            $positiveCount += substr_count($textLower, $word);
        }

        foreach ($negative as $word) {
            $negativeCount += substr_count($textLower, $word);
        }

        // Calcul du score
        $score = 0;
        if ($positiveCount + $negativeCount > 0) {
            $score = ($positiveCount - $negativeCount) / ($positiveCount + $negativeCount);
        }

        // Déterminer le label
        if ($score > 0.2) {
            $label = 'positive';
        } elseif ($score < -0.2) {
            $label = 'negative';
        } else {
            $label = 'neutral';
        }

        return [
            'label' => $label,
            'score' => round($score, 2)
        ];
    }
}
