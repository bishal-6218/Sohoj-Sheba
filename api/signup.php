<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

function postv(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function normalize_bool($v): int {
    if ($v === null) return 0;
    if ($v === true) return 1;
    $s = strtolower(trim((string)$v));
    return in_array($s, ['1','true','yes','on'], true) ? 1 : 0;
}

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function save_upload(string $field, string $targetDir, array $allowedMime, int $maxBytes): ?string {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) return null;
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $field);
    }
    if (($f['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('File too large for ' . $field);
    }

    $tmp = $f['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid upload for ' . $field);
    }

    $mime = mime_content_type($tmp) ?: '';
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Invalid file type for ' . $field);
    }

    $ext = '';
    $original = (string)($f['name'] ?? '');
    if ($original !== '' && str_contains($original, '.')) {
        $ext = '.' . strtolower(pathinfo($original, PATHINFO_EXTENSION));
    } else {
        $map = [
            'image/jpeg' => '.jpg',
            'image/png'  => '.png',
            'image/gif'  => '.gif',
            'image/webp' => '.webp',
        ];
        $ext = $map[$mime] ?? '';
    }

    ensure_dir($targetDir);
    $name = bin2hex(random_bytes(16)) . $ext;
    $absPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($tmp, $absPath)) {
        throw new RuntimeException('Failed to save upload for ' . $field);
    }

    // store web path relative to project root
    $rel = str_replace('\\', '/', $absPath);
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)));
    if ($root && str_starts_with($rel, $root)) {
        $rel = ltrim(substr($rel, strlen($root)), '/');
    }
    return $rel;
}

// Accept both JSON (login-style) and multipart/form-data (signup.html with files)
$data = null;
if (!empty($_POST)) {
    $data = $_POST;
} else {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $data = $json;
}
if (!is_array($data)) {
    json_response(['success' => false, 'message' => 'Invalid request body'], 400);
}

$name     = trim((string)($data['name'] ?? ''));
$email    = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$role     = (string)($data['role'] ?? 'user');

if ($name === '' || $email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Name, email and password are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address.'], 400);
}

if (strlen($password) < 6) {
    json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
}

if (!in_array($role, ['user', 'worker'], true)) {
    $role = 'user';
}

// Optional extra fields from signup.html
$phone            = trim((string)($data['phone'] ?? ''));
$whatsapp         = trim((string)($data['whatsapp'] ?? ''));
$alternativePhone = trim((string)($data['alternativePhone'] ?? ($data['alternative_phone'] ?? '')));
$country          = trim((string)($data['country'] ?? ''));
$city             = trim((string)($data['city'] ?? ''));
$area             = trim((string)($data['area'] ?? ''));
$postalCode       = trim((string)($data['postalCode'] ?? ($data['postal_code'] ?? '')));
$address          = trim((string)($data['address'] ?? ''));
$dateOfBirth      = trim((string)($data['dateOfBirth'] ?? ($data['date_of_birth'] ?? '')));
$gender           = trim((string)($data['gender'] ?? ''));

$preferredLanguage = trim((string)($data['language'] ?? ($data['preferred_language'] ?? '')));
$referralSource    = trim((string)($data['referralSource'] ?? ($data['referral_source'] ?? '')));
$preferencesText   = trim((string)($data['preferences'] ?? ($data['preferences_text'] ?? '')));
$newsletterOptIn   = normalize_bool($data['newsletter'] ?? ($data['newsletter_opt_in'] ?? 0));
$termsAcceptedAt   = (isset($data['terms']) || isset($data['terms_accepted_at'])) ? date('Y-m-d H:i:s') : null;

// Worker-specific fields
$experience   = trim((string)($data['experience'] ?? ''));
$skills       = trim((string)($data['skills'] ?? ''));
$nidNumber    = trim((string)($data['nidNumber'] ?? ($data['nid_number'] ?? '')));
$tradeLicense = trim((string)($data['tradeLicense'] ?? ($data['trade_license'] ?? '')));

try {
    $pdo = db();

    $pdo->beginTransaction();

    // check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Email is already registered.'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (
            role, name, email, password_hash,
            phone, whatsapp, alternative_phone,
            country, city, area, postal_code, address,
            date_of_birth, gender,
            preferred_language, referral_source, preferences_text, newsletter_opt_in, terms_accepted_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?
        )'
    );
    $stmt->execute([
        $role, $name, $email, $hash,
        $phone !== '' ? $phone : null,
        $whatsapp !== '' ? $whatsapp : null,
        $alternativePhone !== '' ? $alternativePhone : null,
        $country !== '' ? $country : null,
        $city !== '' ? $city : null,
        $area !== '' ? $area : null,
        $postalCode !== '' ? $postalCode : null,
        $address !== '' ? $address : null,
        $dateOfBirth !== '' ? $dateOfBirth : null,
        ($gender !== '' ? $gender : null),
        $preferredLanguage !== '' ? $preferredLanguage : null,
        $referralSource !== '' ? $referralSource : null,
        $preferencesText !== '' ? $preferencesText : null,
        $newsletterOptIn,
        $termsAcceptedAt,
    ]);

    $userId = (int)$pdo->lastInsertId();

    // Save worker extras + uploaded files
    if ($role === 'worker') {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $max = 5 * 1024 * 1024;

        $uploadsBase = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads';
        if ($uploadsBase === false) {
            $uploadsBase = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
        }

        $profilePath = save_upload('profilePhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'profiles', $allowed, $max);
        $nidPath     = save_upload('nidPhoto',     $uploadsBase . DIRECTORY_SEPARATOR . 'nid',      $allowed, $max);

        $stmt = $pdo->prepare(
            'INSERT INTO worker_profiles (user_id, experience, skills, nid_number, trade_license, profile_photo_path, nid_photo_path)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $experience !== '' ? $experience : null,
            $skills !== '' ? $skills : null,
            $nidNumber !== '' ? $nidNumber : null,
            $tradeLicense !== '' ? $tradeLicense : null,
            $profilePath,
            $nidPath,
        ]);

        // Map selected services[] (slug) -> worker_services rows
        $selected = $data['services'] ?? ($data['services[]'] ?? null);
        if (is_array($selected)) {
            $slugs = array_values(array_unique(array_filter(array_map('strval', $selected))));
            if (count($slugs) > 0) {
                $in = implode(',', array_fill(0, count($slugs), '?'));
                $q = $pdo->prepare("SELECT id, slug FROM services WHERE slug IN ($in)");
                $q->execute($slugs);
                $rows = $q->fetchAll();
                if ($rows) {
                    $ins = $pdo->prepare('INSERT IGNORE INTO worker_services (worker_user_id, service_id) VALUES (?, ?)');
                    foreach ($rows as $r) {
                        $ins->execute([$userId, (int)$r['id']]);
                    }
                }
            }
        }
    }

    // Save user profile photo and NID photo (for regular users)
    if ($role === 'user') {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $max = 5 * 1024 * 1024;

        $uploadsBase = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads';
        if ($uploadsBase === false) {
            $uploadsBase = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
        }

        $userProfilePath = save_upload('userProfilePhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'profiles', $allowed, $max);
        $userNidPath     = save_upload('userNidPhoto',     $uploadsBase . DIRECTORY_SEPARATOR . 'nid',      $allowed, $max);

        if ($userProfilePath !== null || $userNidPath !== null) {
            $stmt = $pdo->prepare(
                'UPDATE users SET profile_photo_path = ?, nid_photo_path = ? WHERE id = ?'
            );
            $stmt->execute([
                $userProfilePath,
                $userNidPath,
                $userId,
            ]);
        }
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'user' => [
            'id'    => $userId,
            'name'  => $name,
            'email' => $email,
            'role'  => $role,
        ],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => $e->getMessage() ?: 'Server error while creating account.'], 500);
}

