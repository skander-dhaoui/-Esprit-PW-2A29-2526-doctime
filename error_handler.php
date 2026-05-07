<?php
/**
 * Global error handling and logging setup
 * This file must be included at the very top of index.php
 */

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

// Configure error logging
$errorLogFile = $logsDir . '/php_error.log';
ini_set('error_log', $errorLogFile);
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('error_reporting', E_ALL);

// Ensure log file is writable
if (!is_file($errorLogFile)) {
    @touch($errorLogFile);
}

// Custom error handler to ensure all errors are logged
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorLogFile = __DIR__ . '/logs/php_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] [$errno] $errstr in $errfile:$errline\n";
    
    // Log to file
    @error_log($message, 3, $errorLogFile);
    
    // Also return false to let PHP handle it normally
    return false;
}, E_ALL);

// Log script execution for debugging
error_log('=== REQUEST START [' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . '] ===');
