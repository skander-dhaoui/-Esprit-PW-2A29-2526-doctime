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
            [
                'keywords' => ['métier', 'metier', 'profession', 'travail'], 
                'texts' => [
                    "🎯 **Métiers dans l'événementiel médical en Tunisie :**\n\n• **Chef de projet événementiel médical** - Organise conférences et formations\n• **Community manager santé** - Gestion des réseaux sociaux médicaux\n• **Graphiste médical** - Création de supports visuels pour événements\n• **Photographe médical** - Documentation des événements\n\nEn Tunisie, ces métiers sont en croissance avec le développement du tourisme médical! 🇹🇳",
                    "👨‍⚕️ Le secteur médical offre de nombreux métiers : coordinateur d'événements, responsable des partenariats (sponsors), chargé de communication santé. C'est un domaine très dynamique !",
                    "💼 Vous cherchez une profession dans l'événementiel médical ? Les profils les plus recherchés sont les chefs de projets capables de gérer la logistique de grands congrès médicaux."
                ]
            ],
            [
                'keywords' => ['carrière', 'carriere', 'evolution', 'avenir'], 
                'texts' => [
                    "📈 **Plan de carrière pour expert en événements médicaux :**\n\n1. **Débutant** : Assistant événementiel (6-12 mois)\n2. **Junior** : Coordinateur de projets (1-3 ans)\n3. **Senior** : Chef de projet médical (3-5 ans)\n4. **Expert** : Directeur des événements médicaux (5+ ans)\n\n💡 Conseils : Formez-vous en gestion de projet et apprenez le vocabulaire médical!",
                    "🚀 Votre carrière peut évoluer rapidement dans ce domaine : commencez par l'assistance logistique, et montez en compétence pour diriger vos propres symposiums internationaux.",
                    "🎓 L'avenir dans l'événementiel médical est prometteur. Avec de l'expérience, vous pouvez même fonder votre propre agence spécialisée dans le tourisme médical et les congrès scientifiques."
                ]
            ],
            [
                'keywords' => ['tunisie', 'tunis', 'sfax', 'pays'], 
                'texts' => [
                    "🇹🇳 **Événements médicaux en Tunisie :**\n\nLa Tunisie est reconnue pour :\n• Le tourisme médical de qualité\n• Les conférences médicales internationales\n• Les formations continues pour professionnels de santé\n\nLes opportunités sont nombreuses dans les villes de Tunis, Sfax et Hammamet!",
                    "🌍 La Tunisie se positionne comme un hub régional pour la santé. Les congrès s'y multiplient grâce à l'expertise de nos médecins et à des infrastructures hôtelières de qualité.",
                    "📍 Tunis, Sfax et Sousse accueillent chaque année des dizaines de symposiums médicaux internationaux, attirant des experts du monde entier."
                ]
            ],
            [
                'keywords' => ['compétence', 'competence', 'qualité', 'qualite', 'skill'], 
                'texts' => [
                    "💡 **Compétences clés pour réussir :**\n\n• Gestion de projet (planning, budget, équipes)\n• Communication et relations publiques\n• Connaissance du secteur médical/santé\n• Maîtrise des outils numériques\n• Français et anglais professionnels\n\nCes compétences sont très demandées en Tunisie!",
                    "🧠 Pour exceller, il vous faudra une excellente capacité d'organisation, un bon relationnel, et une bonne résistance au stress. La maîtrise des termes médicaux de base est un vrai plus !",
                    "🛠 Savoir gérer un budget, négocier avec les hôtels et communiquer avec des professionnels de santé sont les trois compétences fondamentales dans ce domaine."
                ]
            ],
            [
                'keywords' => ['événement', 'evenement', 'congrès', 'congres', 'symposium'], 
                'texts' => [
                    "📅 **Nos Événements :**\n\nNous organisons divers événements médicaux allant de la cardiologie à la médecine esthétique. Vous pouvez consulter notre catalogue complet depuis l'onglet 'Événements' pour voir les dates, lieux et tarifs !",
                    "🎟 Pour voir tous les événements disponibles, veuillez naviguer vers la page 'Événements'. Vous pourrez y filtrer par spécialité et même réserver votre place en ligne.",
                    "🗓 Vous cherchez le prochain congrès ? De la dermatologie aux urgences, nous couvrons toutes les spécialités. N'hésitez pas à regarder notre planning sur le site."
                ]
            ],
            [
                'keywords' => ['sponsor', 'partenaire', 'financement'], 
                'texts' => [
                    "🤝 **Sponsors & Partenaires :**\n\nNos événements sont soutenus par de nombreux acteurs clés du domaine médical et pharmaceutique. Pour devenir sponsor, veuillez contacter l'administration.",
                    "🏢 Les laboratoires pharmaceutiques et les fournisseurs de matériel médical sont nos principaux partenaires. Ils disposent souvent de stands dédiés lors de nos événements.",
                    "💰 Sans nos généreux sponsors, ces événements n'auraient pas la même envergure ! La liste de nos partenaires officiels est disponible dans la section 'Sponsors'."
                ]
            ],
            [
                'keywords' => ['bonjour', 'salut', 'coucou', 'hello'], 
                'texts' => [
                    "👋 Bonjour ! Je suis l'assistant IA de DocTime.\n\nJe suis actuellement en **mode démonstration**. Comment puis-je vous aider aujourd'hui ? (Essayez de me parler d'événements, de sponsors, ou de la Tunisie !)",
                    "Salut ! Prêt à vous aider à organiser ou à trouver votre prochain événement médical. Que cherchez-vous ?",
                    "Coucou ! Posez-moi des questions sur les métiers de l'événementiel, nos sponsors ou la façon de participer à un congrès."
                ]
            ],
        ];
        
        foreach ($responses as $item) {
            foreach ($item['keywords'] as $keyword) {
                if (strpos($userMessage, $keyword) !== false) {
                    $randomText = $item['texts'][array_rand($item['texts'])];
                    return ['content' => [['text' => $randomText]]];
                }
            }
        }
        
        $fallback = [
            "🤖 **Note :** Je suis actuellement en mode démonstration car aucune clé API d'intelligence artificielle n'a été configurée.\n\nJe ne peux répondre qu'à certains mots-clés comme : métiers, carrière, Tunisie, événements, sponsors.\n\n*Pour une discussion naturelle, l'administrateur doit configurer une clé API Anthropic.*",
            "Désolé, je suis en mode démo restreint 😅. Essayez de me poser une question avec l'un de ces mots : événement, tunisie, sponsor, carrière.",
            "Je n'ai pas de vraie intelligence artificielle connectée pour le moment ! 🔌 Je ne réponds qu'à des questions spécifiques. Dites 'bonjour' ou demandez-moi les événements en cours."
        ];
        
        return ['content' => [['text' => $fallback[array_rand($fallback)]]]];
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

        // Lire le corps JSON de la requête (toujours AVANT toute logique)
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data || !isset($data['messages'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Corps de requête invalide']);
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
