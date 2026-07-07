<?php

return [
    'main' => [
        'host' => getenv('HJ_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('HJ_DB_PORT') ?: '3306',
        'database' => getenv('HJ_DB_DATABASE') ?: 'huajian_main',
        'username' => getenv('HJ_DB_USERNAME') ?: 'root',
        'password' => getenv('HJ_DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
];

