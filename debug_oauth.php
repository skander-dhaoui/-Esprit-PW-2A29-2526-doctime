<?php
declare(strict_types=1);

// ============================================================================
// OAuth Flow Debugging Script
// ============================================================================

session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Google OAuth Flow Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 20px; }
        .section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #2196F3;
            background: #e3f2fd;
            border-radius: 3px;
        }
        code {
            background: #eee;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
            display: block;
            margin: 10px 0;
            padding: 10px;
            overflow-x: auto;
        }
        .log {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin: 10px 0;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Google OAuth Flow Debug</h1>";

// ============================================================================
// 1. Check if we're in callback
// ============================================================================

if (!empty($_GET['code'])) {
    echo "<h2>📥 OAuth Callback Received</h2>";
    echo "<div class='section'>";
    echo "<strong>Authorization Code:</strong>";
    echo "<code>" . htmlspecialchars($_GET['code']) . "</code>";
    echo "<strong>State Parameter:</strong>";
    echo "<code>" . htmlspecialchars($_GET['state'] ?? '(missing)') . "</code>";
    echo "</div>";

    // Try to exchange code for token
    echo "<h2>🔄 Attempting Token Exchange</h2>";
    
    require_once __DIR__ . '/config/social_auth.php';
    require_once __DIR__ . '/config/database.php';

    $provider = 'google';
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    $config = SocialAuthConfig::get($provider);
    
    if (!$config) {
        echo "<div class='section' style='border-left-color: #f44336; background: #ffebee;'>";
        echo "❌ Failed to load Google config";
        echo "</div>";
    } else {
        // Build callback URL
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host . '/valorys_Copie/';
        $callbackUrl = $baseUrl . 'index.php?page=debug_oauth&provider=google';

        echo "<div class='section'>";
        echo "<strong>Callback URL being used:</strong>";
        echo "<code>" . htmlspecialchars($callbackUrl) . "</code>";
        echo "<strong>Token URL:</strong>";
        echo "<code>" . htmlspecialchars($config['token_url']) . "</code>";
        echo "</div>";

        // Prepare token request
        $tokenPayload = [
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $callbackUrl,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ];

        echo "<div class='section'>";
        echo "<strong>Token Request Payload:</strong>";
        echo "<code>" . htmlspecialchars(json_encode($tokenPayload, JSON_PRETTY_PRINT)) . "</code>";
        echo "</div>";

        // Send token request
        echo "<h2>🌐 HTTP Request to Google</h2>";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $config['token_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($tokenPayload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: DocTime-Debug/1.0',
            ],
            CURLOPT_VERBOSE        => true,
        ]);

        // Capture verbose output
        $verboseHandle = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verboseHandle);

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        rewind($verboseHandle);
        $verboseOutput = stream_get_contents($verboseHandle);
        fclose($verboseHandle);

        echo "<div class='section'>";
        echo "<strong>HTTP Status Code: </strong>";
        if ($httpCode < 300) {
            echo "<span style='color: green;'>✅ " . $httpCode . "</span>";
        } elseif ($httpCode < 400) {
            echo "<span style='color: orange;'>⚠️ " . $httpCode . "</span>";
        } else {
            echo "<span style='color: red;'>❌ " . $httpCode . "</span>";
        }
        echo "</div>";

        if ($curlError) {
            echo "<div class='section' style='border-left-color: #f44336; background: #ffebee;'>";
            echo "<strong>❌ cURL Error:</strong>";
            echo "<code>" . htmlspecialchars($curlError) . "</code>";
            echo "</div>";
        }

        echo "<div class='section'>";
        echo "<strong>Response Body:</strong>";
        echo "<div class='log'>" . htmlspecialchars($responseBody) . "</div>";
        echo "</div>";

        if (!empty($verboseOutput)) {
            echo "<div class='section'>";
            echo "<strong>Verbose cURL Output:</strong>";
            echo "<div class='log'>" . htmlspecialchars($verboseOutput) . "</div>";
            echo "</div>";
        }

        // Parse response
        if ($responseBody) {
            $decoded = json_decode($responseBody, true);
            
            echo "<div class='section'>";
            echo "<strong>Parsed Response:</strong>";
            echo "<code>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</code>";
            echo "</div>";

            if (isset($decoded['error'])) {
                echo "<div class='section' style='border-left-color: #f44336; background: #ffebee;'>";
                echo "<strong>❌ OAuth Error:</strong>";
                echo "<br><strong>Error Code:</strong> " . htmlspecialchars($decoded['error']);
                echo "<br><strong>Error Description:</strong> " . htmlspecialchars($decoded['error_description'] ?? '(none)');
                echo "<br><br><strong>💡 Possible Causes:</strong>";
                echo "<ul>";
                
                switch ($decoded['error']) {
                    case 'invalid_grant':
                        echo "<li><strong>Authorization code is invalid or expired</strong>";
                        echo "<ul><li>The code was issued more than 10 minutes ago</li>";
                        echo "<li>The code has already been exchanged</li>";
                        echo "<li>The code doesn't belong to this client</li></ul></li>";
                        break;
                    case 'invalid_client':
                        echo "<li><strong>Client ID or Secret is wrong</strong>";
                        echo "<ul><li>Check GOOGLE_CLIENT_ID in your environment</li>";
                        echo "<li>Check GOOGLE_CLIENT_SECRET in your environment</li></ul></li>";
                        break;
                    case 'redirect_uri_mismatch':
                        echo "<li><strong>Redirect URI doesn't match</strong>";
                        echo "<ul>";
                        echo "<li>Expected: " . htmlspecialchars($callbackUrl) . "</li>";
                        echo "<li>You must register this exact URL in Google Cloud Console</li>";
                        echo "</ul></li>";
                        break;
                    case 'access_denied':
                        echo "<li><strong>User denied access</strong></li>";
                        break;
                    default:
                        echo "<li>Unknown error. Check Google OAuth documentation.</li>";
                }
                
                echo "</ul>";
                echo "</div>";
            } elseif (isset($decoded['access_token'])) {
                echo "<div class='section' style='border-left-color: #4CAF50; background: #e8f5e9;'>";
                echo "✅ <strong>Successfully obtained access token!</strong>";
                echo "<br><strong>Token Type:</strong> " . htmlspecialchars($decoded['token_type'] ?? 'N/A');
                echo "<br><strong>Expires In:</strong> " . htmlspecialchars($decoded['expires_in'] ?? 'N/A') . " seconds";
                echo "</div>";

                // Try to fetch user info
                if (!empty($decoded['access_token'])) {
                    echo "<h2>👤 Fetching User Profile</h2>";
                    
                    $userCh = curl_init();
                    curl_setopt_array($userCh, [
                        CURLOPT_URL            => $config['user_url'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . $decoded['access_token'],
                        ],
                    ]);

                    $userResponse = curl_exec($userCh);
                    $userHttpCode = (int) curl_getinfo($userCh, CURLINFO_HTTP_CODE);
                    curl_close($userCh);

                    echo "<div class='section'>";
                    echo "<strong>HTTP Status: " . $userHttpCode . "</strong>";
                    echo "<div class='log'>" . htmlspecialchars($userResponse) . "</div>";
                    echo "</div>";

                    $userDecoded = json_decode($userResponse, true);
                    if (is_array($userDecoded)) {
                        echo "<div class='section'>";
                        echo "<strong>User Profile:</strong>";
                        echo "<code>" . htmlspecialchars(json_encode($userDecoded, JSON_PRETTY_PRINT)) . "</code>";
                        echo "</div>";
                    }
                }
            }
        }
    }

} else {
    // Show how to start the flow
    echo "<h2>🚀 Start OAuth Flow</h2>";
    
    echo "<div class='section'>";
    echo "Click the button below to initiate Google OAuth login:";
    echo "</div>";

    echo "<form method='GET' style='margin: 20px 0;'>";
    echo "<input type='hidden' name='action' value='startSocialLogin'>";
    echo "<input type='hidden' name='provider' value='google'>";
    echo "<button type='submit' class='button'>🔐 Start Google Login</button>";
    echo "</form>";

    // Check PHP error logs
    echo "<h2>📋 PHP Error Log (Last 50 lines)</h2>";
    
    $phpIni = php_ini_loaded_file();
    $logFile = ini_get('error_log');
    
    echo "<div class='section'>";
    echo "<strong>PHP Config File:</strong> " . htmlspecialchars($phpIni) . "<br>";
    echo "<strong>Error Log File:</strong> " . htmlspecialchars($logFile ?? '(not set)');
    echo "</div>";

    if ($logFile && is_file($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $lines = array_slice($lines, -50); // Last 50 lines
        
        echo "<div class='section'>";
        echo "<strong>Last 50 log entries:</strong>";
        echo "<div class='log'>";
        foreach ($lines as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</div>";
        echo "</div>";
    }
}

echo "</div></body></html>";
