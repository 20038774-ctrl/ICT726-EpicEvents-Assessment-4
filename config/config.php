<?php
declare(strict_types=1);

return [
    'app_name' => 'EpicEvents',
    'base_url' => getenv('APP_URL') ?: 'http://localhost/epicevents',
    'environment' => getenv('APP_ENV') ?: 'production',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'epicevents',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
