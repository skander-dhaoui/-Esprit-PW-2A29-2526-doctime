<?php
if (class_exists('ReviewController')) return;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/Validator.php';
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

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

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
        // ========== NETTOYAGE ==========
        $title   = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $rating  = isset($data['rating']) ? (int)$data['rating'] : 0;

        // ========== VALIDATION AVEC VALIDATOR ==========
        $validator = new Validator();
        $validator
            ->required('title', $title, 'Titre')
            ->minLength('title', $title, 3, 'Titre')
            ->maxLength('title', $title, 100, 'Titre')
            ->required('content', $content, 'Contenu')
            ->minLength('content', $content, 10, 'Contenu')
            ->maxLength('content', $content, 2000, 'Contenu');
        
        $errors = $validator->getErrors();

        // ========== VALIDATIONS PERSONNALISÉES ==========
        if (empty($errors['rating'])) {
            if (empty($rating) || $rating < 1 || $rating > 5) {
                $errors['rating'] = 'La note doit être entre 1 et 5';
            }
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
        $sentiment = Review::analyzeSentimentText($text);

        return [
            'label' => $sentiment['label'],
            'score' => $sentiment['score'],
        ];
    }
}
