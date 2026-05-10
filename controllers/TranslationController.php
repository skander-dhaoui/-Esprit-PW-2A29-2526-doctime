<?php
// controllers/TranslationController.php
// Version Google Translate — sans API payante

require_once __DIR__ . '/../services/TranslationService.php';

class TranslationController
{
    private TranslationService $svc;

    public function __construct()
    {
        $this->svc = new TranslationService();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecte']);
            exit;
        }

        $raw    = file_get_contents('php://input');
        $body   = json_decode($raw, true) ?? [];
        $action = $body['action'] ?? $_GET['action'] ?? 'get_text';
        $type   = $body['type']   ?? $_GET['type']   ?? '';
        $id     = (int)($body['id'] ?? $_GET['id'] ?? 0);
        $lang   = $body['lang']   ?? $_GET['lang']   ?? 'en';
        $field  = $body['field']  ?? $_GET['field']  ?? 'content';

        if (!in_array($type, ['article', 'reply']) || !$id) {
            echo json_encode(['success' => false, 'message' => 'Parametres invalides']);
            exit;
        }

        if ($action === 'get_text') {
            // Verifier cache DB
            $cached = $this->svc->getCached($type, $id, $field, $lang);
            if ($cached !== null) {
                echo json_encode(['success' => true, 'cached' => true, 'translated' => $cached]);
                exit;
            }
            // Retourner texte original — Google Translate JS fera la traduction
            $original = $this->svc->getOriginalText($type, $id, $field);
            if (!$original || trim($original) === '') {
                echo json_encode(['success' => false, 'message' => 'Contenu introuvable']);
                exit;
            }
            echo json_encode(['success' => true, 'cached' => false, 'original' => $original]);
            exit;
        }

        if ($action === 'save') {
            $translated = trim($body['translated'] ?? '');
            if (empty($translated)) {
                echo json_encode(['success' => false, 'message' => 'Traduction vide']);
                exit;
            }
            $this->svc->saveCache($type, $id, $field, $lang, $translated);
            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
        exit;
    }
}