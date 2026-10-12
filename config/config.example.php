<?php
/**
 * Application Configuration (Example / Template)
 * Copy this file to `config.php` and configure your credentials.
 */

// Environment settings
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Multi-Agent AI Hub');
    define('APP_VERSION', '1.0.0');
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
    define('APP_DEBUG', true);

    // Base paths
    define('ROOT_PATH', dirname(__DIR__));
    define('CONFIG_PATH', ROOT_PATH . '/config');
    define('SRC_PATH', ROOT_PATH . '/src');
    define('VIEWS_PATH', ROOT_PATH . '/views');
    define('ASSETS_PATH', ROOT_PATH . '/assets');
    define('DATABASE_PATH', ROOT_PATH . '/database');

    // Database configuration (Defaulting to MySQL)
    define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql'); // 'mysql' or 'sqlite'
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'multi_agent_hub');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_SQLITE_PATH', DATABASE_PATH . '/app.sqlite');

    // Secret Key for encrypting user API keys (AES-256-GCM)
    // Replace with a secure random 32-byte key in production
    define('APP_ENCRYPTION_KEY', getenv('APP_ENCRYPTION_KEY') ?: 'base64:' . base64_encode('change-this-to-a-secure-32-byte-key!'));
}

// Supported AI Providers (Models are dynamically stored in the database)
return [
    'providers' => [
        'gemini' => [
            'name' => 'Google Gemini',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/'
        ],
        'openai' => [
            'name' => 'OpenAI',
            'endpoint' => 'https://api.openai.com/v1/chat/completions'
        ],
        'anthropic' => [
            'name' => 'Anthropic Claude',
            'endpoint' => 'https://api.anthropic.com/v1/messages'
        ],
        'groq' => [
            'name' => 'Groq (Ultra-fast)',
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions'
        ]
    ]
];
