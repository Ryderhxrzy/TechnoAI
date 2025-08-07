<?php
// config/env_loader.php

function loadEnvironmentVariables($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (str_starts_with($line, '#') || empty($line)) {
            continue;
        }

        // Only process lines with = sign
        if (str_contains($line, '=')) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || 
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Load environment variables
loadEnvironmentVariables();

// Validate required environment variables
$requiredEnvVars = [
    'FIREBASE_API_KEY',
    'FIREBASE_AUTH_DOMAIN',
    'FIREBASE_PROJECT_ID',
    'FIREBASE_STORAGE_BUCKET',
    'FIREBASE_MESSAGING_SENDER_ID',
    'FIREBASE_APP_ID'
];

$missingVars = [];
foreach ($requiredEnvVars as $var) {
    if (empty($_ENV[$var])) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    error_log("Missing required environment variables: " . implode(', ', $missingVars));
    if (php_sapi_name() !== 'cli') {
        header('HTTP/1.1 500 Internal Server Error');
        die("Server configuration error. Please contact administrator.");
    }
}