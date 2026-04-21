<?php
require_once __DIR__ . '/config.php';

// Global mysqli connection used by all API files.
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Stop immediately if connection fails.
if ($conn->connect_error) {
    json_response(['success' => false, 'message' => 'Database connection failed.'], 500);
}

// Ensure UTF-8 for Bangla/Unicode text.
$conn->set_charset('utf8mb4');

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

