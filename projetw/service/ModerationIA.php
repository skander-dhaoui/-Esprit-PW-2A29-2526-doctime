<?php
declare(strict_types=1);

class ModerationIA
{
    private array $motesInterdits = [
        // Anglais
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'cunt', 'dick', 'pussy',
        'motherfucker', 'damn', 'hell', 'ass', 'wtf', 'stfu',
        // Français
        'merde', 'connard', 'connasse', 'salaud', 'salope', 'putain', 'enculé',
        'idiot', 'imbécile', 'crétin', 'abruti', 'con', 'conne', 'nique',
        'bordel', 'foutre', 'bâtard', 'pute', 'grosse', 'nul', 'fils de',
        // Spam
        'spam', 'arnaque', 'cliquez ici', 'gagnez', 'gratuit', 'promotio',
        // Haine
        'haine', 'raciste', 'nazi', 'terroriste', 'violence',
    ];

    public function moderateArticle(string $titre, string $contenu): array
    {
        $texte = strtolower($titre . ' ' . strip_tags($contenu));

        foreach ($this->motesInterdits as $mot) {
            if (strpos($texte, strtolower($mot)) !== false) {
                return [
                    'decision' => 'rejected',
                    'raison'   => 'Article refusé : contenu inapproprié détecté ("' . $mot . '").',
                    'score'    => 100
                ];
            }
        }

        // Essayer API Claude si disponible
        $result = $this->callAPI(
            "Tu es modérateur d'une plateforme médicale professionnelle.\n\nAnalyse cet article :\nTITRE: {$titre}\nCONTENU: " . substr(strip_tags($contenu), 0, 1000) . "\n\nRéponds UNIQUEMENT en JSON valide :\n{\"decision\": \"approved\", \"raison\": \"ok\", \"score\": 0}"
        );

        if ($result) return $result;

        return ['decision' => 'approved', 'raison' => 'Contenu approprié', 'score' => 0];
    }

    public function moderateReply(string $contenu): array
    {
        $texte = strtolower(strip_tags($contenu));

        foreach ($this->motesInterdits as $mot) {
            if (strpos($texte, strtolower($mot)) !== false) {
                return [
                    'decision' => 'rejected',
                    'raison'   => 'Commentaire refusé : contenu inapproprié ("' . $mot . '").',
                    'score'    => 100
                ];
            }
        }

        $result = $this->callAPI(
            "Tu es modérateur. Analyse ce commentaire sur une plateforme médicale.\nCOMMENTAIRE: " . substr($texte, 0, 300) . "\nRéponds UNIQUEMENT en JSON: {\"decision\": \"approved\" ou \"rejected\", \"raison\": \"explication\", \"score\": 0-100}"
        );

        if ($result) return $result;

        return ['decision' => 'approved', 'raison' => 'Contenu approprié', 'score' => 0];
    }

    private function callAPI(string $prompt): ?array
    {
        if (!function_exists('curl_init')) return null;

        try {
            $payload = json_encode([
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 150,
                'messages'   => [['role' => 'user', 'content' => $prompt]]
            ]);

            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'anthropic-version: 2023-06-01',
                ],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) return null;

            $data = json_decode($response, true);
            $text = trim(preg_replace('/```json|```/', '', $data['content'][0]['text'] ?? ''));
            $result = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) return null;

            return [
                'decision' => in_array($result['decision'] ?? '', ['approved', 'rejected']) ? $result['decision'] : 'approved',
                'raison'   => $result['raison'] ?? 'Analyse effectuée',
                'score'    => (int)($result['score'] ?? 50),
            ];
        } catch (Exception $e) {
            return null;
        }
    }
}