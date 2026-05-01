<?php
/**
 * API pour les avis - Gestion des reviews
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ReviewController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'store';

try {
    $reviewCtrl = new ReviewController();

    if ($method === 'POST') {
        $reviewCtrl->store();
    } elseif ($method === 'PUT') {
        if ($action === 'approve') {
            $reviewCtrl->approve();
        } elseif ($action === 'reject') {
            $reviewCtrl->reject();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

