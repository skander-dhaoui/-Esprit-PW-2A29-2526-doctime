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
        // Priorité à la variable d'environnement (depuis .env chargé par helpers.php)
        $env = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY');
        if ($env) {
            $this->apiKey = $env;
        }
    }

    /**
     * Mode démonstration - répond sans API externe
     */
    private function demoMode(string $userMessage): array {
        $userMessage = strtolower($userMessage);
        
        $responses = [
            'métier' => "🎯 **Métiers dans l'événementiel médical en Tunisie :**\n\n• **Chef de projet événementiel médical** - Organise conférences et formations\n• **Community manager santé** - Gestion des réseaux sociaux médicaux\n• **Graphiste médical** - Création de supports visuels pour événements\n• **Photographe médical** - Documentation des événements\n\nEn Tunisie, ces métiers sont en croissance avec le développement du tourisme médical! 🇹🇳",
            
            'carrière' => "📈 **Plan de carrière pour expert en événements médicaux :**\n\n1. **Débutant** : Assistant événementiel (6-12 mois)\n2. **Junior** : Coordinateur de projets (1-3 ans)\n3. **Senior** : Chef de projet médical (3-5 ans)\n4. **Expert** : Directeur des événements médicaux (5+ ans)\n\n💡 Conseils : Formez-vous en gestion de projet et apprenez le vocabulaire médical!",
            
            'tunisie' => "🇹🇳 **Événements médicaux en Tunisie :**\n\nLa Tunisie est reconnue pour :\n• Le tourisme médical de qualité\n• Les conférences médicales internationales\n• Les formations continues pour professionnels de santé\n\nLes opportunités sont nombreuses dans les villes de Tunis, Sfax et Hammamet!",
            
            'compétence' => "💡 **Compétences clés pour réussir :**\n\n• Gestion de projet (planning, budget, équipes)\n• Communication et relations publiques\n• Connaissance du secteur médical/santé\n• Maîtrise des outils numériques\n• Français et anglais professionnels\n\nCes compétences sont très demandées en Tunisie!",
        ];
        
        foreach ($responses as $keyword => $response) {
            if (strpos($userMessage, $keyword) !== false) {
                return ['content' => [['text' => $response]]];
            }
        }
        
        return ['content' => [['text' => "🤖 Je suis en mode démonstration. Pour accéder à toutes mes capacités IA, veuillez configurer une clé API Anthropic dans le fichier .env\n\nPosez-moi des questions sur : les métiers, la carrière, ou la Tunisie! 🇹🇳"]]];
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

        // Vérifier la clé API - sinon utiliser le mode démonstration
        if (empty($this->apiKey) || $this->apiKey === 'sk-ant-api03-placeholder-replace-me') {
            // Mode démonstration : répondre localement sans API externe
            $lastMessage = end($data['messages'])['content'] ?? '';
            $response = $this->demoMode($lastMessage);
            echo json_encode($response);
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
