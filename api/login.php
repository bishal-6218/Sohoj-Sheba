<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    json_response(['success' => false, 'message' => 'Invalid JSON body'], 400);
}

$email    = trim($data['email'] ?? '');
$password = (string)($data['password'] ?? '');
$role     = $data['role'] ?? null;

if ($email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Email and password are required.'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, role, name, email, password_hash, profile_photo_path FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    // Optional: ensure selected role matches stored role
    if ($role && in_array($role, ['user', 'worker'], true) && $user['role'] !== $role) {
        json_response(['success' => false, 'message' => 'Selected account type does not match this email.'], 403);
    }

    $photo = $user['profile_photo_path'] ?? null;
    if ($user['role'] === 'worker' && ($photo === null || $photo === '')) {
        $w = $pdo->prepare('SELECT profile_photo_path FROM worker_profiles WHERE user_id = ? LIMIT 1');
        $w->execute([(int)$user['id']]);
        $wp = $w->fetch();
        if ($wp && !empty($wp['profile_photo_path'])) {
            $photo = $wp['profile_photo_path'];
        }
    }

    $_SESSION['user'] = [
        'id'                 => (int)$user['id'],
        'name'               => $user['name'],
        'email'              => $user['email'],
        'role'               => $user['role'],
        'profile_photo_path' => $photo,
    ];

    json_response([
        'success' => true,
        'user'    => $_SESSION['user'],
    ]);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Server error while logging in.'], 500);
}

