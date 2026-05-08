<?php
declare(strict_types=1);

/**
 * AiProxyController — Proxy sécurisé vers l'API Groq
 *
 * Pourquoi un proxy ?
 * - Le navigateur bloque les appels directs à l'API Groq (CORS)
 * - La clé API ne doit JAMAIS être exposée dans le code JavaScript client
 * - Toutes les requêtes passent par ce controller PHP côté serveur
 *
 * Usage : ?page=aiproxy&action=chat   (POST JSON)
 */
class AiProxyController {

    // ── Clé API Groq ─────────────────────────────────────────────────────
    // Obtenir la clé sur https://console.groq.com/keys
    // L'API est gratuite et illimitée !
    private string $apiKey = '';   // ← remplacer par votre clé Groq

    private string $apiUrl  = 'https://api.groq.com/openai/v1/chat/completions';
    private string $model   = 'llama-3.3-70b-versatile';
    private int    $maxTokens = 1000;

    public function __construct() {
        // Priorité à la variable d'environnement (depuis .env chargé par helpers.php)
        $env = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
        if ($env) {
            $this->apiKey = $env;
        }
    }

    /**
     * Mode démonstration - répond sans API externe
     */
    private function demoMode(string $userMessage): array {
        $userMessage = strtolower($userMessage);
        $specialiteResponses = [
            'coordinateur' => [
                "📋 **Coordinateur événementiel médical** : Ce métier central consiste à orchestrer tous les aspects d'un événement médical, de la planification à l'exécution. Le coordinateur gère les intervenants, les sponsors, la logistique et assure la qualité scientifique des contenus.",
                "🎯 **Coordinateur événementiel médical** : Un rôle polyvalent qui nécessite d'excellentes compétences en organisation, communication et gestion de projet. Le coordinateur est le chef d'orchestre qui fait le lien entre les médecins, les sponsors et l'équipe technique.",
                "📅 **Coordinateur événementiel médical** : Responsable de la réussite globale d'un congrès médical, ce poste demande une parfaite maîtrise du timing, du budget et des relations avec tous les acteurs de l'événement."
            ],
            'coordinator' => [
                "📋 **Coordinateur événementiel médical** : Ce métier central consiste à orchestrer tous les aspects d'un événement médical, de la planification à l'exécution. Le coordinateur gère les intervenants, les sponsors, la logistique et assure la qualité scientifique des contenus.",
                "🎯 **Coordinateur événementiel médical** : Un rôle polyvalent qui nécessite d'excellentes compétences en organisation, communication et gestion de projet. Le coordinateur est le chef d'orchestre qui fait le lien entre les médecins, les sponsors et l'équipe technique.",
                "📅 **Coordinateur événementiel médical** : Responsable de la réussite globale d'un congrès médical, ce poste demande une parfaite maîtrise du timing, du budget et des relations avec tous les acteurs de l'événement."
            ],
            'cardiologie' => [
                "🫀 **Cardiologie** : Dans ce secteur, les métiers clés sont coordinateur de congrès cardiologiques, chargé de communication santé et chef de projet événementiel pour les symposiums cardiovasculaires. Ces postes demandent une bonne connaissance des acteurs hospitaliers, des conférences scientifiques et du parcours patient.",
                "💼 **Cardiologie** : Les événements dans ce domaine nécessitent des professionnels capables de gérer la logistique de formations, l'accueil des médecins cardiologues et la promotion des innovations en cardiologie.",
                "📊 **Cardiologie** : Un métier important est celui de responsable de programme pour des conférences cardio, en lien étroit avec les équipes médicales, sponsors pharmaceutiques et institutions de santé."
            ],
            'medecine generale' => [
                "🩺 **Médecine générale** : Les métiers liés à cette spécialité incluent coordinateur de formations médicales généralistes, responsable d'événements de prévention santé et chef de projet pour des forums sur les soins primaires.",
                "🌿 **Médecine générale** : Ce secteur requiert une excellente capacité de synthèse, une communication simple et claire, et la création d'événements qui rassemblent médecins de famille, infirmiers et acteurs de santé publique.",
                "👨‍⚕️ **Médecine générale** : Un rôle pertinent est celui de responsable de programme pour des journées de sensibilisation à la santé de proximité, aux diagnostics précoces et à la prévention des maladies chroniques."
            ],
            'médecine générale' => [
                "🩺 **Médecine générale** : Les métiers liés à cette spécialité incluent coordinateur de formations médicales généralistes, responsable d'événements de prévention santé et chef de projet pour des forums sur les soins primaires.",
                "🌿 **Médecine générale** : Ce secteur requiert une excellente capacité de synthèse, une communication simple et claire, et la création d'événements qui rassemblent médecins de famille, infirmiers et acteurs de santé publique.",
                "👨‍⚕️ **Médecine générale** : Un rôle pertinent est celui de responsable de programme pour des journées de sensibilisation à la santé de proximité, aux diagnostics précoces et à la prévention des maladies chroniques."
            ],
            'dermatologie' => [
                "🧴 **Dermatologie** : Les métiers associés incluent organisateur de congrès dermatologiques, community manager santé peau et responsable de production de contenus cliniques. La dermatologie attire souvent un public mixe de praticiens et de laboratoires cosmétiques.",
                "💡 **Dermatologie** : Pour cette spécialité, il faut créer des événements pédagogiques autour des traitements cutanés, en faisant le lien entre médecins, marques dermo-cosmétiques et patients.",
                "🎤 **Dermatologie** : Le rôle de modérateur pour des ateliers pratiques et de gestionnaire de stands est très recherché dans les salons de dermatologie et médecine esthétique."
            ],
            'esthétique' => [
                "💄 **Esthétique** : Les métiers ici sont organisateur d'événements beauté, chargé des partenariats avec les cliniques esthétiques, et coordinateur de workshops sur la médecine esthétique.",
                "✨ **Esthétique** : Ce secteur doit combiner sens du design, gestion des intervenants médicaux et compréhension du marketing des soins esthétiques.",
                "📸 **Esthétique** : Un métier pertinent est celui de responsable des démonstrations produits et de l'animation des stands lors de congrès esthétiques."
            ],
            'oncologie' => [
                "🧬 **Oncologie** : Dans ce domaine, les métiers incluent chef de projet événements cliniques, coordinateur de symposiums sur le cancer et responsable des relations avec les centres oncologiques.",
                "🎗 **Oncologie** : L'organisation d'événements oncologiques nécessite une grande rigueur, une sensibilité aux patients et une coordination avec des équipes de recherche médicale.",
                "📚 **Oncologie** : Un poste utile est celui de gestionnaire de contenus pour conférences sur les nouvelles thérapies et les essais cliniques en oncologie."
            ],
            'urgence' => [
                "🚑 **Urgence** : Les métiers liés à l'urgence médicale sont coordinateur de formations aux soins d'urgence, responsable de simulation clinique et organisateur de journées de préparation aux situations critiques.",
                "⚡ **Urgence** : Ce secteur demande une réactivité élevée et une capacité à gérer des événements très techniques avec des intervenants urgentistes et sapeurs-pompiers.",
                "🩺 **Urgence** : Un métier adapté est chef de projet pour des ateliers sur la gestion des crises médicales et l'amélioration des parcours d'accueil des urgences."
            ],
            'neurosciences' => [
                "🧠 **Neurosciences** : Les métiers associés incluent organisateur de colloques sur le cerveau, coordinateur de conférences sur la neurologie et responsable de communication scientifique.",
                "📍 **Neurosciences** : Il faut comprendre les enjeux des maladies neurologiques et savoir animer des échanges entre chercheurs, médecins neurologues et institutions académiques.",
                "🔬 **Neurosciences** : Un rôle important est celui de gestionnaire de programme pour des journées de formation sur les avancées en neurochirurgie et neurosciences cliniques."
            ],
            'pédiatrie' => [
                "👶 **Pédiatrie** : Les métiers dans ce secteur sont organisateur de congrès pédiatriques, responsable de programmes de formation et coordinateur d'ateliers pour parents et professionnels de santé.",
                "🧸 **Pédiatrie** : La gestion d'événements pédiatriques nécessite une approche empathique et une bonne organisation pour des sessions pédagogiques adaptées aux besoins des enfants et des soignants.",
                "🎈 **Pédiatrie** : Un poste clé est celui de chef de projet pour des journées thématiques sur la santé infantile et le développement des nouveaux soins pédiatriques."
            ],
        ];

        foreach ($specialiteResponses as $keyword => $texts) {
            if (strpos($userMessage, $keyword) !== false) {
                return ['content' => [['text' => $texts[array_rand($texts)]]]];
            }
        }

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
       
        $fallback = "❌ Erreur : Cette question ne concerne pas le projet DocTime. Je suis uniquement spécialisé dans les événements médicaux, sponsors et spécialités de la plateforme DocTime.";

        return ['content' => [['text' => $fallback]]];
    }

    /**
     * Récupère le contexte dynamique du projet (événements et sponsors depuis la BD)
     */
    private function getProjectContext(): string {
        $context = "Tu es l'assistant virtuel intelligent de DocTime, une plateforme d'organisation d'événements médicaux en Tunisie. ";
        $context .= "Ton rôle exclusif est d'aider les utilisateurs à trouver des informations pertinentes sur nos événements, nos sponsors, les spécialités médicales, et l'organisation de la plateforme DocTime. ";
        $context .= "RÈGLE STRICTE : Tu dois IMPÉRATIVEMENT refuser de répondre à toute question qui n'est pas directement liée à DocTime, à l'événementiel médical, à nos sponsors, ou au domaine médical/santé en Tunisie. Si l'utilisateur te pose une question hors de ce cadre (informatique générale, politique, cuisine, blagues, etc.), réponds poliment que tu es spécialisé uniquement dans les événements médicaux DocTime et que tu ne peux pas l'aider sur d'autres sujets. ";
        $context .= "Réponds toujours en français, de manière concise, professionnelle et amicale.\n\n";

        try {
            // Charger les événements à venir
            if (file_exists(__DIR__ . '/../repositories/EventRepository.php')) {
                require_once __DIR__ . '/../repositories/EventRepository.php';
                $repo = new EventRepository();
                $evenements = $repo->findUpcoming();

                if (!empty($evenements)) {
                    $context .= "Voici la liste des événements médicaux à venir actuellement disponibles dans notre système :\n";
                    foreach ($evenements as $evt) {
                        $titre = $evt['titre'] ?? $evt->getTitre();
                        $date = $evt['date_debut'] ?? $evt->getDateDebut();
                        $lieu = $evt['lieu'] ?? $evt->getLieu();
                        $specialite = $evt['specialite'] ?? $evt->getSpecialite();
                        $prix = $evt['prix'] ?? $evt->getPrix();
                        $context .= "- \"$titre\" (Spécialité: $specialite) prévu le $date à $lieu. Prix: $prix TND.\n";
                    }
                } else {
                    $context .= "Il n'y a actuellement aucun événement à venir de programmé.\n";
                }
            }

            // Charger les sponsors premium
            if (file_exists(__DIR__ . '/../repositories/SponsorRepository.php')) {
                require_once __DIR__ . '/../repositories/SponsorRepository.php';
                $sponsorRepo = new SponsorRepository();
                $sponsors = $sponsorRepo->findPremium();
               
                if (!empty($sponsors)) {
                    $context .= "\nVoici la liste de nos sponsors premium :\n";
                    foreach ($sponsors as $sponsor) {
                        $nom = $sponsor['nom'] ?? $sponsor->getNom();
                        $niveau = $sponsor['niveau'] ?? $sponsor->getNiveau();
                        $context .= "- " . $nom . " (Niveau: " . $niveau . ")\n";
                    }
                }
            }

        } catch (Throwable $t) {
            $context .= "(Note: impossible de charger les événements et sponsors en direct pour le moment).\n";
        }

        return $context;
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
        if (empty($this->apiKey) || $this->apiKey === 'your-groq-api-key-here') {
            // Mode démonstration : répondre localement sans API externe
            $lastMessage = end($data['messages'])['content'] ?? '';
            $response = $this->demoMode($lastMessage);
            echo json_encode($response);
            exit;
        }

        // Préparer le contexte système dynamique avec les données de la BD
        $contextMessage = $this->getProjectContext();
        $messages = $data['messages'];
       
        if (count($messages) > 0 && $messages[0]['role'] !== 'system') {
            // Insérer au début si pas de message system
            array_unshift($messages, ['role' => 'system', 'content' => $contextMessage]);
        } else if (count($messages) > 0 && $messages[0]['role'] === 'system') {
            // Concaténer si déjà un message system
            $messages[0]['content'] = $contextMessage . "\n\n" . $messages[0]['content'];
        }

        // Construire la requête vers Groq (format OpenAI compatible)
        $payload = [
            'model'      => $this->model,
            'messages'   => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        // Appel cURL vers l'API Groq
        $response = $this->callGroq($payload);

        // Retourner la réponse au client
        echo $response;
        exit;
    }

    /**
     * Appel HTTP vers l'API Groq via cURL
     */
    private function callGroq(array $payload): string {
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
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

        if ($httpCode !== 200) {
            http_response_code($httpCode);
            $errorData = json_decode($result, true);
            $errorMsg = $errorData['error']['message'] ?? 'Erreur Groq (HTTP ' . $httpCode . ')';
            return json_encode(['error' => $errorMsg]);
        }

        // Parser la réponse OpenAI-compatible de Groq
        $groqResponse = json_decode($result, true);
       
        if (!$groqResponse) {
            return json_encode(['error' => 'Réponse invalide de Groq']);
        }

        if (!isset($groqResponse['choices']) || !isset($groqResponse['choices'][0]['message']['content'])) {
            return json_encode(['error' => 'Réponse Groq mal formée']);
        }

        $text = $groqResponse['choices'][0]['message']['content'];
        // Retourner au format Anthropic pour compatibilité
        return json_encode(['content' => [['text' => $text]]]);
    }
}
