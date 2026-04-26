<?php
declare(strict_types=1);

/**
 * AiProxyController — Proxy sécurisé vers l'API Anthropic
 *
 * Pourquoi un proxy ?
 * - Le navigateur bloque les appels directs à api.anthropic.com (CORS)
 * - La clé API ne doit JAMAIS être exposée dans le code JavaScript client
 * - Toutes les requêtes passent par ce controller PHP côté serveur
 *
 * Usage : ?controller=aiproxy&action=chat   (POST JSON)
 */
class AiProxyController {

    // ── Clé API Anthropic ─────────────────────────────────────────────────────
    // Option 1 : variable d'environnement (recommandée en production)
    //   Dans Apache : SetEnv ANTHROPIC_API_KEY sk-ant-...
    //   Dans .env   : ANTHROPIC_API_KEY=sk-ant-...
    // Option 2 : valeur directe ici (acceptable en développement local)
    private string $apiKey = '';   // ← remplacer par votre clé sk-ant-...

    private string $apiUrl  = 'https://api.anthropic.com/v1/messages';
    private string $model   = 'claude-sonnet-4-20250514';
    private int    $maxTokens = 1000;

    public function __construct() {
        // Priorité à la variable d'environnement
        $env = getenv('ANTHROPIC_API_KEY');
        if ($env) {
            $this->apiKey = $env;
        }
    }

    /**
     * Point d'entrée principal — appelé par le routeur
     */
    public function chat(): void {
        // Forcer JSON en sortie
        header('Content-Type: application/json; charset=utf-8');
        // Autoriser uniquement les requêtes de la même origine
        header('X-Content-Type-Options: nosniff');

        // Vérifier méthode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }

        // Vérifier la clé API
        if (empty($this->apiKey)) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Clé API manquante. Ajoutez ANTHROPIC_API_KEY dans AiProxyController.php ou en variable d\'environnement.'
            ]);
            exit;
        }

        // Lire le corps JSON de la requête
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data || !isset($data['messages'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Corps de requête invalide']);
            exit;
        }

        // Construire la requête vers Anthropic
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages'   => $data['messages'],
        ];

        // Prompt système optionnel
        if (!empty($data['system'])) {
            $payload['system'] = $data['system'];
        }

        // Appel cURL vers l'API Anthropic
        $response = $this->callAnthropic($payload);

        // Retourner la réponse au client
        echo $response;
        exit;
    }

    /**
     * Appel HTTP vers l'API Anthropic via cURL
     */
    private function callAnthropic(array $payload): string {
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            http_response_code(502);
            return json_encode(['error' => 'Erreur réseau : ' . $curlErr]);
        }

        // Transmettre le code HTTP de l'API Anthropic
        http_response_code($httpCode);
        return $result ?: json_encode(['error' => 'Réponse vide de l\'API']);
    }
}
