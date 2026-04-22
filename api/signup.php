<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

function normalize_bool($v): int
{
    if ($v === null) {
        return 0;
    }
    if ($v === true) {
        return 1;
    }
    $s = strtolower(trim((string)$v));
    return in_array($s, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function project_root_path(): string
{
    $root = realpath(dirname(__DIR__));
    return $root !== false ? $root : dirname(__DIR__);
}

function uploads_base_dir(): string
{
    return project_root_path() . DIRECTORY_SEPARATOR . 'uploads';
}

function read_body_or_post(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function run_stmt(mysqli $conn, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed');
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed');
    }
    return $stmt;
}

function fetch_one_assoc(mysqli_stmt $stmt): ?array
{
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    return $row ?: null;
}

function fetch_all_assoc(mysqli_stmt $stmt): array
{
    $result = $stmt->get_result();
    return $result ? ($result->fetch_all(MYSQLI_ASSOC) ?: []) : [];
}

function save_upload(string $field, string $targetDir, array $allowedMime, int $maxBytes): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
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
    $root = str_replace('\\', '/', project_root_path());
    if ($root && str_starts_with($rel, $root)) {
        $rel = ltrim(substr($rel, strlen($root)), '/');
    }
    return $rel;
}

// Accept both JSON (login-style) and multipart/form-data (signup.html with files)
$data = read_body_or_post();
if (!$data) {
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
    global $conn;

    $conn->begin_transaction();

    // check duplicate email
    $stmt = run_stmt($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    if (fetch_one_assoc($stmt)) {
        $conn->rollback();
        json_response(['success' => false, 'message' => 'Email is already registered.'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    run_stmt(
        $conn,
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
        )',
        'sssssssssssssssssis',
        [
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
        ]
    );

    $userId = (int)$conn->insert_id;

    // Save worker extras + uploaded files
    if ($role === 'worker') {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max = 5 * 1024 * 1024;
        $uploadsBase = uploads_base_dir();

        $profilePath = save_upload('profilePhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'profiles', $allowed, $max);
        $nidPath     = save_upload('nidPhoto',     $uploadsBase . DIRECTORY_SEPARATOR . 'nid',      $allowed, $max);

        run_stmt(
            $conn,
            'INSERT INTO worker_profiles (user_id, experience, skills, nid_number, trade_license, profile_photo_path, nid_photo_path)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            'issssss',
            [
                $userId,
                $experience !== '' ? $experience : null,
                $skills !== '' ? $skills : null,
                $nidNumber !== '' ? $nidNumber : null,
                $tradeLicense !== '' ? $tradeLicense : null,
                $profilePath,
                $nidPath,
            ]
        );

        // Mirror paths on `users` (schema: sohoj_sheba.sql)
        run_stmt($conn, 'UPDATE users SET profile_photo_path = ?, nid_photo_path = ? WHERE id = ?', 'ssi', [$profilePath, $nidPath, $userId]);

        // Map selected services[] (slug) -> worker_services rows
        $selected = $data['services'] ?? ($data['services[]'] ?? null);
        if (is_array($selected)) {
            $slugs = array_values(array_unique(array_filter(array_map('strval', $selected))));
            if (count($slugs) > 0) {
                $in = implode(',', array_fill(0, count($slugs), '?'));
                $q = run_stmt($conn, "SELECT id, slug FROM services WHERE slug IN ($in)", str_repeat('s', count($slugs)), $slugs);
                $rows = fetch_all_assoc($q);
                if ($rows) {
                    $ins = $conn->prepare('INSERT IGNORE INTO worker_services (worker_user_id, service_id) VALUES (?, ?)');
                    if (!$ins) {
                        throw new RuntimeException('Prepare failed');
                    }
                    foreach ($rows as $r) {
                        $serviceId = (int)$r['id'];
                        $ins->bind_param('ii', $userId, $serviceId);
                        if (!$ins->execute()) {
                            throw new RuntimeException('Execute failed');
                        }
                    }
                }
            }
        }
    }

    // Save user profile photo and NID photo (for regular users)
    if ($role === 'user') {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max = 5 * 1024 * 1024;
        $uploadsBase = uploads_base_dir();

        $userProfilePath = save_upload('userProfilePhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'profiles', $allowed, $max)
            ?? save_upload('profilePhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'profiles', $allowed, $max);
        $userNidPath = save_upload('userNidPhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'nid', $allowed, $max)
            ?? save_upload('nidPhoto', $uploadsBase . DIRECTORY_SEPARATOR . 'nid', $allowed, $max);

        if ($userProfilePath !== null || $userNidPath !== null) {
            run_stmt(
                $conn,
                'UPDATE users SET profile_photo_path = COALESCE(?, profile_photo_path), nid_photo_path = COALESCE(?, nid_photo_path) WHERE id = ?',
                'ssi',
                [
                $userProfilePath,
                $userNidPath,
                $userId,
                ]
            );
        }
    }

    $conn->commit();

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
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    json_response(['success' => false, 'message' => $e->getMessage() ?: 'Server error while creating account.'], 500);
}

