<?php
session_start();
require_once __DIR__ . '/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

$service = trim((string)($_GET['service'] ?? ''));
if ($service === '' || !preg_match('/^[a-z0-9-]{2,64}$/', $service)) {
    json_response(['success' => false, 'message' => 'Invalid service'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare(
        "SELECT 
            u.id,
            u.name,
            COALESCE(u.profile_photo_path, wp.profile_photo_path) AS profile_photo_path,
            wp.rating_avg,
            wp.jobs_completed,
            u.city,
            u.area,
            wp.experience,
            wp.skills
         FROM services s
         INNER JOIN worker_services ws ON ws.service_id = s.id
         INNER JOIN users u ON u.id = ws.worker_user_id AND u.role = 'worker' AND u.status = 'active'
         LEFT JOIN worker_profiles wp ON wp.user_id = u.id
         WHERE s.slug = ? AND s.is_active = 1
         ORDER BY wp.rating_avg DESC, wp.jobs_completed DESC, u.name ASC"
    );
    $stmt->execute([$service]);
    $rows = $stmt->fetchAll();

    json_response(['success' => true, 'workers' => $rows]);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Failed to load professionals.'], 500);
}

