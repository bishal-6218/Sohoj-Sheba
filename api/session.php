<?php
session_start();
require_once __DIR__ . '/db.php';

$user = $_SESSION['user'] ?? null;

if (!$user || empty($user['id'])) {
    json_response(['loggedIn' => false]);
}

function worker_photo_fallback(mysqli $conn, int $userId, ?string $currentPhoto): ?string
{
    if ($currentPhoto !== null && trim($currentPhoto) !== '') {
        return $currentPhoto;
    }
    $stmt = $conn->prepare('SELECT profile_photo_path FROM worker_profiles WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        return $currentPhoto;
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        return $currentPhoto;
    }
    $res = $stmt->get_result();
    $wp = $res ? $res->fetch_assoc() : null;
    return ($wp && !empty($wp['profile_photo_path'])) ? $wp['profile_photo_path'] : $currentPhoto;
}

try {
    global $conn;
    $stmt = $conn->prepare(
        'SELECT id, role, name, email, profile_photo_path FROM users WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        throw new RuntimeException('Prepare failed');
    }
    $userId = (int)$user['id'];
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed');
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    if (!$row) {
        json_response(['loggedIn' => false]);
    }

    $out = [
        'id'                 => (int)$row['id'],
        'name'               => $row['name'],
        'email'              => $row['email'],
        'role'               => $row['role'],
        'profile_photo_path' => $row['profile_photo_path'],
    ];

    // Workers may still have photo only in worker_profiles (older rows)
    if ($row['role'] === 'worker') {
        $out['profile_photo_path'] = worker_photo_fallback($conn, (int)$out['id'], $out['profile_photo_path']);
    }

    json_response(['loggedIn' => true, 'user' => $out]);
} catch (Throwable $e) {
    json_response(['loggedIn' => false, 'message' => 'Session check failed'], 500);
}

