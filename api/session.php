<?php
session_start();
require_once __DIR__ . '/db.php';

$user = $_SESSION['user'] ?? null;

if (!$user || empty($user['id'])) {
    json_response(['loggedIn' => false]);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, role, name, email, profile_photo_path FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();

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
    if ($row['role'] === 'worker' && empty($out['profile_photo_path'])) {
        $w = $pdo->prepare('SELECT profile_photo_path FROM worker_profiles WHERE user_id = ? LIMIT 1');
        $w->execute([$out['id']]);
        $wp = $w->fetch();
        if ($wp && !empty($wp['profile_photo_path'])) {
            $out['profile_photo_path'] = $wp['profile_photo_path'];
        }
    }

    json_response(['loggedIn' => true, 'user' => $out]);
} catch (Throwable $e) {
    json_response(['loggedIn' => false, 'message' => 'Session check failed'], 500);
}

