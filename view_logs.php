<?php
declare(strict_types=1);

$logsDir = __DIR__ . '/logs';
$errorLogFile = $logsDir . '/php_error.log';

echo "<!DOCTYPE html>
<html>
<head>
    <title>PHP Error Logs - OAuth Debug</title>
    <style>
        body {
            font-family: monospace;
            margin: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            padding: 20px;
            border-radius: 5px;
        }
        h1 {
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .log-entry {
            margin: 10px 0;
            padding: 10px;
            background: #1e1e1e;
            border-left: 3px solid #666;
            border-radius: 3px;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-x: auto;
        }
        .log-entry.error {
            border-left-color: #f44336;
            color: #f44336;
        }
        .log-entry.success {
            border-left-color: #4CAF50;
            color: #4CAF50;
        }
        .log-entry.warning {
            border-left-color: #ff9800;
            color: #ff9800;
        }
        .log-entry.oauth {
            border-left-color: #2196F3;
            color: #2196F3;
        }
        .log-entry.start {
            border-left-color: #9C27B0;
            color: #9C27B0;
            font-weight: bold;
        }
        .refresh-btn {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .refresh-btn:hover {
            background: #45a049;
        }
        .info {
            padding: 10px;
            background: #1a3a1a;
            border-left: 3px solid #4CAF50;
            margin-bottom: 20px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>📋 PHP Error Logs - OAuth Debugging</h1>";

echo "<div class='info'>";
echo "Error Log File: " . htmlspecialchars($errorLogFile) . "<br>";
echo "<button class='refresh-btn' onclick='location.reload()'>🔄 Refresh (Auto in 5 seconds)</button>";
echo "</div>";

echo "<script>setTimeout(function() { location.reload(); }, 5000);</script>";

if (!is_file($errorLogFile) || filesize($errorLogFile) === 0) {
    echo "<div class='log-entry warning'>";
    echo "⏳ No logs yet. Try these steps:<br>";
    echo "1. <a href='index.php?page=login' style='color: #2196F3;'>Go to login page</a><br>";
    echo "2. Click 'Sign in with Google'<br>";
    echo "3. Complete Google authentication<br>";
    echo "4. Refresh this page to see logs";
    echo "</div>";
} else {
    $content = file_get_contents($errorLogFile);
    if (!$content || strlen($content) === 0) {
        echo "<div class='log-entry warning'>";
        echo "⚠️ Log file is empty";
        echo "</div>";
    } else {
        // Get last lines (most recent first)
        $lines = array_filter(explode("\n", $content));
        $lines = array_slice($lines, -200); // Last 200 lines
        
        echo "<p style='color: #888; margin-bottom: 20px;'>Showing last " . count($lines) . " log entries (most recent last):</p>";
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $cssClass = 'log-entry';
            
            if (strpos($line, 'REQUEST START') !== false) {
                $cssClass .= ' start';
            } elseif (strpos($line, '❌') !== false || strpos($line, '[Error') !== false || strpos($line, 'error') !== false) {
                $cssClass .= ' error';
            } elseif (strpos($line, '✅') !== false || strpos($line, 'SUCCESS') !== false) {
                $cssClass .= ' success';
            } elseif (strpos($line, 'OAUTH') !== false) {
                $cssClass .= ' oauth';
            } elseif (strpos($line, 'warning') !== false || strpos($line, '⚠️') !== false) {
                $cssClass .= ' warning';
            }
            
            echo "<div class='" . $cssClass . "'>";
            echo htmlspecialchars($line);
            echo "</div>";
        }
    }
}

echo "</div>";
echo "</body></html>";

