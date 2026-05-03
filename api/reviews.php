<?php
/**
 * API Professionelle pour les avis
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(json_encode(['ok' => true]));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Review.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        $response['message'] = 'Méthode non autorisée';
        exit(json_encode($response));
    }

    $action = $_GET['action'] ?? 'store';
    
    // =============== ACTION: GET (récupérer un avis) ===============
    if ($action === 'get') {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            $response['message'] = 'Non authentifié';
            exit(json_encode($response));
        }
        
        $reviewId = (int)($_GET['id'] ?? 0);
        if (!$reviewId) {
            http_response_code(400);
            $response['message'] = 'ID d\'avis invalide';
            exit(json_encode($response));
        }
        
        $reviewModel = new Review();
        $stmt = $reviewModel->getConnection()->prepare('
            SELECT r.id, r.user_id, r.rating, r.content, r.sentiment, r.is_approved, r.has_profanity, r.created_at, r.sentiment_score,
                   u.prenom, u.nom, u.email, u.role
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ');
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$review) {
            http_response_code(404);
            $response['message'] = 'Avis non trouvé';
            exit(json_encode($response));
        }
        
        // Vérifier l'autorisation
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        if (!$isAdmin && $review['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            $response['message'] = 'Non autorisé';
            exit(json_encode($response));
        }
        
        $response['success'] = true;
        $response['review'] = $review;
        exit(json_encode($response));
    }
    
    // =============== ACTION: DELETE (supprimer un avis) ===============
    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $response['message'] = 'Méthode non autorisée';
            exit(json_encode($response));
        }
        
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            $response['message'] = 'Non authentifié';
            exit(json_encode($response));
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $reviewId = (int)($data['id'] ?? 0);
        if (!$reviewId) {
            http_response_code(400);
            $response['message'] = 'ID d\'avis invalide';
            exit(json_encode($response));
        }
        
        $reviewModel = new Review();
        $stmt = $reviewModel->getConnection()->prepare('SELECT user_id FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        
        if (!$review) {
            http_response_code(404);
            $response['message'] = 'Avis non trouvé';
            exit(json_encode($response));
        }
        
        // Vérifier l'autorisation
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        if (!$isAdmin && $review['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            $response['message'] = 'Non autorisé à supprimer cet avis';
            exit(json_encode($response));
        }
        
        // Supprimer les emojis d'abord
        $reviewModel->getConnection()->prepare('DELETE FROM review_emojis WHERE review_id = ?')->execute([$reviewId]);
        
        // Supprimer l'avis
        $stmt = $reviewModel->getConnection()->prepare('DELETE FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        
        $response['success'] = true;
        $response['message'] = 'Avis supprimé avec succès';
        exit(json_encode($response));
    }
    
    // =============== ACTION: UPDATE (mettre à jour un avis) ===============
    if ($action === 'update') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $response['message'] = 'Méthode non autorisée';
            exit(json_encode($response));
        }
        
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            $response['message'] = 'Non authentifié';
            exit(json_encode($response));
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $reviewId = (int)($data['id'] ?? 0);
        if (!$reviewId) {
            http_response_code(400);
            $response['message'] = 'ID d\'avis invalide';
            exit(json_encode($response));
        }
        
        $reviewModel = new Review();
        $stmt = $reviewModel->getConnection()->prepare('SELECT user_id FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        
        if (!$review) {
            http_response_code(404);
            $response['message'] = 'Avis non trouvé';
            exit(json_encode($response));
        }
        
        // Vérifier l'autorisation
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        if (!$isAdmin && $review['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            $response['message'] = 'Non autorisé à modifier cet avis';
            exit(json_encode($response));
        }
        
        // Validation (même que create)
        $content = trim($data['content'] ?? '');
        $rating = (int)($data['rating'] ?? 0);
        $emojis = $data['emojis'] ?? [];
        
        if (!$content || strlen($content) < 10 || strlen($content) > 2000) {
            $response['errors']['content'] = 'Contenu: 10-2000 caractères';
        }
        if ($rating < 1 || $rating > 5) {
            $response['errors']['rating'] = 'Note: entre 1 et 5 étoiles';
        }
        
        if (!empty($response['errors'])) {
            http_response_code(400);
            $response['message'] = 'Erreur de validation';
            exit(json_encode($response));
        }
        
        $sentimentAnalysis = Review::analyzeSentimentText($content, $rating);
        $sentiment = $sentimentAnalysis['label'];
        $sentimentScore = $sentimentAnalysis['score'];
        
        // Mettre à jour l'avis
        $stmt = $reviewModel->getConnection()->prepare(
            'UPDATE reviews SET rating = ?, content = ?, sentiment = ?, sentiment_score = ? WHERE id = ?'
        );
        $stmt->execute([$rating, $content, $sentiment, $sentimentScore, $reviewId]);
        
        // Supprimer les anciens emojis et ajouter les nouveaux
        $reviewModel->getConnection()->prepare('DELETE FROM review_emojis WHERE review_id = ?')->execute([$reviewId]);
        foreach ($emojis as $emoji) {
            if (strlen($emoji) <= 10) {
                $reviewModel->addEmoji($reviewId, $emoji);
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Avis mis à jour avec succès';
        $response['review_id'] = $reviewId;
        exit(json_encode($response));
    }
    
    // =============== ACTION: STORE (créer un nouvel avis) ===============
    if ($action === 'create') {
        // Pour le back-office: créer un avis en tant qu'admin
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $response['message'] = 'Méthode non autorisée';
            exit(json_encode($response));
        }
        
        // Vérifier admin
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            $response['message'] = 'Accès refusé - Admin uniquement';
            exit(json_encode($response));
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Validation
        $userId = (int)($data['user_id'] ?? 0);
        $rating = (int)($data['rating'] ?? 0);
        $content = trim($data['content'] ?? '');
        $isApproved = (int)($data['is_approved'] ?? 0);
        
        if (!$userId || !$rating || !$content) {
            http_response_code(400);
            $response['message'] = 'Données manquantes';
            exit(json_encode($response));
        }
        
        if (strlen($content) < 10 || strlen($content) > 2000) {
            $response['errors']['content'] = 'Contenu: 10-2000 caractères';
        }
        if ($rating < 1 || $rating > 5) {
            $response['errors']['rating'] = 'Note: entre 1 et 5 étoiles';
        }
        
        if (!empty($response['errors'])) {
            http_response_code(400);
            $response['message'] = 'Erreur de validation';
            exit(json_encode($response));
        }
        
        $sentimentAnalysis = Review::analyzeSentimentText($content, $rating);
        $sentiment = $sentimentAnalysis['label'];
        $sentimentScore = $sentimentAnalysis['score'];
        
        // Créer l'avis
        $reviewModel = new Review();
        $reviewData = [
            'user_id' => $userId,
            'rating' => $rating,
            'title' => null,
            'content' => $content,
            'sentiment' => $sentiment,
            'sentiment_score' => $sentimentScore,
            'has_profanity' => 0,
            'is_approved' => $isApproved
        ];
        
        $result = $reviewModel->create($reviewData);
        
        if (!$result['success']) {
            http_response_code(400);
            $response['message'] = 'Erreur lors de la création';
            exit(json_encode($response));
        }
        
        $response['success'] = true;
        $response['review_id'] = $result['review_id'];
        $response['message'] = 'Avis créé avec succès';
        exit(json_encode($response));
    }
    
    // =============== ACTION: APPROVE (approuver un avis) ===============
    if ($action === 'approve') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $response['message'] = 'Méthode non autorisée';
            exit(json_encode($response));
        }
        
        // Vérifier admin
        if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            $response['message'] = 'Accès refusé - Admin uniquement';
            exit(json_encode($response));
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $reviewId = (int)($data['id'] ?? 0);
        if (!$reviewId) {
            http_response_code(400);
            $response['message'] = 'ID invalide';
            exit(json_encode($response));
        }
        
        $reviewModel = new Review();
        $db = $reviewModel->getConnection();
        
        // Vérifier existence
        $stmt = $db->prepare('SELECT id FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            $response['message'] = 'Avis non trouvé';
            exit(json_encode($response));
        }
        
        // Approuver
        $stmt = $db->prepare('UPDATE reviews SET is_approved = 1 WHERE id = ?');
        if ($stmt->execute([$reviewId])) {
            $response['success'] = true;
            $response['message'] = 'Avis approuvé';
            exit(json_encode($response));
        }
        
        http_response_code(500);
        $response['message'] = 'Erreur lors de l\'approbation';
        exit(json_encode($response));
    }
    
    // =============== ACTION: STORE (créer un nouvel avis) ===============
    if ($action !== 'store') {
        http_response_code(400);
        $response['message'] = 'Action invalide';
        exit(json_encode($response));
    }

    // Vérifier connexion
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        $response['message'] = '🔐 Vous devez être connecté pour publier un avis';
        exit(json_encode($response));
    }

    // Parser JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        $response['message'] = 'Données invalides';
        exit(json_encode($response));
    }

    // ============= VALIDATION =============
    $content = trim($data['content'] ?? '');
    $rating = (int)($data['rating'] ?? 0);
    $emojis = $data['emojis'] ?? [];

    if (!$content || strlen($content) < 10 || strlen($content) > 2000) {
        $response['errors']['content'] = 'Contenu: 10-2000 caractères';
    }
    if ($rating < 1 || $rating > 5) {
        $response['errors']['rating'] = 'Note: entre 1 et 5 étoiles';
    }

    if (!empty($response['errors'])) {
        http_response_code(400);
        $response['message'] = 'Veuillez corriger les erreurs';
        exit(json_encode($response));
    }

    // ============= ANALYSE =============
    $badWords = ['merde', 'putain', 'con', 'connard', 'salope', 'bâtard', 'idiot', 'stupide', 'abruti', 'salaud', 'foutre', 'chier', 'gueule', 'enculé', 'débile'];
    // ===== Fonction détection insultes robuste =====
    $hasProfanity = false;
    $text = strtolower($content);
    $words_in_text = preg_split('/[\s\.,!?;:\-0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($badWords as $word) {
        // 1. Détection exacte
        if (strpos($text, $word) !== false) {
            $hasProfanity = true;
            break;
        }
        
        // 2. Distance Levenshtein (détecte les typos proches)
        // putain → putin, puttin, putain, etc.
        foreach ($words_in_text as $w) {
            if (strlen($w) >= 4 && strlen($w) <= 10) {
                $distance = levenshtein($word, $w);
                // Si distance ≤ 1 et ressemble à 75% c'est suspect
                if ($distance <= 1 && similar_text($word, $w, $percent) && $percent >= 75) {
                    $hasProfanity = true;
                    break 2;
                }
            }
        }
        
        // 3. Variantes avec substitutions (a→0, e→3, i→1, o→0)
        $pattern = preg_quote($word, '/');
        $pattern = str_replace('a', '[a0@â]', $pattern);
        $pattern = str_replace('e', '[e3é]', $pattern);
        $pattern = str_replace('i', '[i1!|ï]', $pattern);
        $pattern = str_replace('o', '[o0ô]', $pattern);
        if (preg_match('/' . $pattern . '/i', $text)) {
            $hasProfanity = true;
            break;
        }
    }

    if ($hasProfanity) {
        http_response_code(400);
        $response['message'] = '⛔ Votre avis contient un langage inapproprié et ne peut pas être publié.';
        $response['errors']['content'] = 'Langage offensant non autorisé.';
        exit(json_encode($response));
    }

    $sentimentAnalysis = Review::analyzeSentimentText($content, $rating);
    $sentiment = $sentimentAnalysis['label'];
    $sentimentScore = $sentimentAnalysis['score'];

    // ============= CRÉER L'AVIS =============
    $reviewModel = new Review();
    $reviewData = [
        'user_id' => (int)$_SESSION['user_id'],
        'rating' => $rating,
        'title' => null,
        'content' => $content,
        'sentiment' => $sentiment,
        'sentiment_score' => $sentimentScore,
        'has_profanity' => 0,
        'is_approved' => 1
    ];

    $result = $reviewModel->create($reviewData);

    if (!$result['success']) {
        http_response_code(400);
        $response['message'] = 'Erreur lors de la création';
        exit(json_encode($response));
    }

    // Ajouter emojis
    if (!empty($emojis) && is_array($emojis)) {
        foreach ($emojis as $emoji) {
            if (strlen($emoji) <= 10) {  // Sécurité
                $reviewModel->addEmoji($result['review_id'], $emoji);
            }
        }
    }

    // ============= RÉPONSE SUCCÈS =============
    http_response_code(201);
    $response['success'] = true;
    $response['review_id'] = $result['review_id'];
    $response['message'] = '✅ Avis publié avec succès!';
    $response['requires_moderation'] = false;

    exit(json_encode($response));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
    exit;
}

