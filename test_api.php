<?php
// FICHIER TEST : projetw/test_api.php
// Ouvrir : http://localhost/projetw/test_api.php
// SUPPRIMER apres le test

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h2>Test API Anthropic</h2>';

$apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
$prompt = 'Traduis "bonjour" en anglais. Retourne UNIQUEMENT la traduction.';

echo '<h3>1. Test curl</h3>';
if (!function_exists('curl_init')) {
    echo '<p style="color:red">❌ curl non disponible</p>';
} else {
    echo '<p style="color:green">✅ curl disponible</p>';
    
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 100,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo '<p>Code HTTP : <strong>' . $httpCode . '</strong></p>';
    if ($curlError) {
        echo '<p style="color:red">❌ Erreur curl : ' . htmlspecialchars($curlError) . '</p>';
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? 'vide';
        echo '<p style="color:green">✅ Traduction : <strong>' . htmlspecialchars($text) . '</strong></p>';
    } else {
        echo '<p style="color:red">❌ Reponse : ' . htmlspecialchars(substr($response, 0, 300)) . '</p>';
    }
}

echo '<h3>2. Test file_get_contents</h3>';
if (!ini_get('allow_url_fopen')) {
    echo '<p style="color:red">❌ allow_url_fopen desactive</p>';
} else {
    echo '<p style="color:green">✅ allow_url_fopen active</p>';
    
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 100,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nx-api-key: " . $apiKey . "\r\nanthropic-version: 2023-06-01",
            'content'       => $payload,
            'timeout'       => 30,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);
    
    $response = @file_get_contents('https://api.anthropic.com/v1/messages', false, $context);
    if ($response) {
        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? '';
        if ($text) {
            echo '<p style="color:green">✅ Traduction : <strong>' . htmlspecialchars($text) . '</strong></p>';
        } else {
            echo '<p style="color:red">❌ Reponse : ' . htmlspecialchars(substr($response, 0, 300)) . '</p>';
        }
    } else {
        echo '<p style="color:red">❌ Impossible de contacter l\'API</p>';
    }
}

echo '<h3>3. Config</h3>';
echo '<p>PHP version : ' . PHP_VERSION . '</p>';
echo '<p>curl_version : ' . (function_exists('curl_version') ? curl_version()['version'] : 'N/A') . '</p>';
echo '<p>allow_url_fopen : ' . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . '</p>';
echo '<p>openssl : ' . (extension_loaded('openssl') ? 'charge' : 'non charge') . '</p>';