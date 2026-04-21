<?php
session_start();
require_once __DIR__ . '/db.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

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

// ── Route ─────────────────────────────────────────────────────────────────────
switch ($method) {
    case 'GET':
        handleGet($sessionUser);
        break;
    case 'POST':
        handlePost($sessionUser);
        break;
    default:
        json_response(['success' => false, 'message' => 'Invalid method'], 405);
}


// ══════════════════════════════════════════════════════════════════════════════
// GET — fetch full profile
// ══════════════════════════════════════════════════════════════════════════════
function handleGet(array $user): void
{
    try {
        global $conn;
        $userId = (int)$user['id'];

        $stmt = run_stmt(
            $conn,
            'SELECT id, role, name, email, phone, whatsapp, alternative_phone,
                    country, city, area, postal_code, address,
                    date_of_birth, gender,
                    preferred_language, referral_source, preferences_text,
                    newsletter_opt_in, created_at,
                    profile_photo_path, nid_photo_path
             FROM users WHERE id = ? LIMIT 1',
            'i',
            [$userId]
        );
        $row = fetch_one_assoc($stmt);

        if (!$row) {
            json_response(['success' => false, 'message' => 'User not found'], 404);
        }

        $profile = [
            'id'                 => (int)$row['id'],
            'role'               => $row['role'],
            'name'               => $row['name'],
            'email'              => $row['email'],
            'phone'              => $row['phone'],
            'whatsapp'           => $row['whatsapp'],
            'alternative_phone'  => $row['alternative_phone'],
            'country'            => $row['country'],
            'city'               => $row['city'],
            'area'               => $row['area'],
            'postal_code'        => $row['postal_code'],
            'address'            => $row['address'],
            'date_of_birth'      => $row['date_of_birth'],
            'gender'             => $row['gender'],
            'preferred_language' => $row['preferred_language'],
            'referral_source'    => $row['referral_source'],
            'preferences_text'   => $row['preferences_text'],
            'newsletter_opt_in'  => (bool)$row['newsletter_opt_in'],
            'member_since'       => $row['created_at'],
            'profile_photo_path' => $row['profile_photo_path'],
            'nid_photo_path'     => $row['nid_photo_path'],
        ];

        // Worker extras
        if ($row['role'] === 'worker') {
            $wStmt = run_stmt(
                $conn,
                'SELECT experience, skills, nid_number, trade_license,
                        profile_photo_path, nid_photo_path, rating_avg, jobs_completed
                 FROM worker_profiles WHERE user_id = ? LIMIT 1',
                'i',
                [$userId]
            );
            $wp = fetch_one_assoc($wStmt);

            if ($wp) {
                $profile['experience']         = $wp['experience'];
                $profile['skills']             = $wp['skills'];
                $profile['nid_number']         = $wp['nid_number'];
                $profile['trade_license']      = $wp['trade_license'];
                $profile['rating_avg']         = (float)$wp['rating_avg'];
                $profile['jobs_completed']     = (int)$wp['jobs_completed'];
                // Prefer users.* paths; fallback to worker_profiles for legacy rows
                if (empty($profile['profile_photo_path']) && !empty($wp['profile_photo_path'])) {
                    $profile['profile_photo_path'] = $wp['profile_photo_path'];
                }
                if (empty($profile['nid_photo_path']) && !empty($wp['nid_photo_path'])) {
                    $profile['nid_photo_path'] = $wp['nid_photo_path'];
                }
            }

            // Services this worker offers — return both name (display) and slug (for checkboxes)
            $sStmt = run_stmt(
                $conn,
                'SELECT s.name, s.slug FROM services s
                 INNER JOIN worker_services ws ON ws.service_id = s.id
                 WHERE ws.worker_user_id = ?',
                'i',
                [$userId]
            );
            $svcRows = fetch_all_assoc($sStmt);
            $profile['services']      = array_column($svcRows, 'name');
            $profile['service_slugs'] = array_column($svcRows, 'slug');
        }

        json_response(['success' => true, 'profile' => $profile]);

    } catch (Throwable $e) {
        json_response(['success' => false, 'message' => 'Server error loading profile.'], 500);
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// POST — update profile
// ══════════════════════════════════════════════════════════════════════════════
function handlePost(array $sessionUser): void {
    $rawJson = file_get_contents('php://input');
    $json    = json_decode($rawJson, true);
    $input   = is_array($json) ? $json : [];

    $pv = fn(string $key) => trim((string)($input[$key] ?? $_POST[$key] ?? ''));
    $orNull = fn(string $v) => $v !== '' ? $v : null;

    try {
        global $conn;
        $userId = (int)$sessionUser['id'];
        $role   = $sessionUser['role'];
        $action = strtolower($pv('action'));

        // ── Password update route (JSON) ────────────────────────────────────
        if ($action === 'change_password') {
            $currentPassword = $pv('currentPassword');
            $newPassword     = $pv('newPassword');

            if ($currentPassword === '') {
                json_response(['success' => false, 'message' => 'Current password is required.'], 400);
            }
            if (strlen($newPassword) < 6) {
                json_response(['success' => false, 'message' => 'New password must be at least 6 characters.'], 400);
            }

            $stmt = run_stmt($conn, 'SELECT password_hash FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
            $row = fetch_one_assoc($stmt);
            if (!$row) {
                json_response(['success' => false, 'message' => 'User not found.'], 404);
            }

            if (!password_verify($currentPassword, (string)$row['password_hash'])) {
                json_response(['success' => false, 'message' => 'Current password is incorrect.'], 401);
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            run_stmt($conn, 'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', 'si', [$newHash, $userId]);

            json_response(['success' => true, 'message' => 'Password changed successfully.']);
        }

        // ── Common fields ─────────────────────────────────────────────────────
        $name     = $pv('name');
        $dob      = $pv('dateOfBirth');
        $gender   = $pv('gender');
        $phone    = $pv('phone');
        $whatsapp = $pv('whatsapp');
        $altPhone = $pv('alternativePhone');
        $postal   = $pv('postalCode');
        $country  = $pv('country');
        $city     = $pv('city');
        $area     = $pv('area');
        $address  = $pv('address');
        $language = $pv('language');
        $referral = $pv('referralSource');
        $prefs    = $pv('preferences');

        if ($name === '') {
            json_response(['success' => false, 'message' => 'Name cannot be empty.'], 400);
        }

        // Validate gender against DB ENUM
        if (!in_array($gender, ['male', 'female', 'other', 'prefer-not-to-say'], true)) {
            $gender = '';
        }

        // Validate date of birth format
        if ($dob !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob) {
                $dob = '';
            }
        }

        $conn->begin_transaction();

        // ── Update users table ────────────────────────────────────────────────
        run_stmt(
            $conn,
            'UPDATE users SET
                name=?, phone=?, whatsapp=?, alternative_phone=?,
                country=?, city=?, area=?, postal_code=?, address=?,
                date_of_birth=?, gender=?,
                preferred_language=?, referral_source=?, preferences_text=?,
                updated_at=NOW()
             WHERE id=?',
            'ssssssssssssssi',
            [
            $name,
            $orNull($phone), $orNull($whatsapp), $orNull($altPhone),
            $orNull($country), $orNull($city), $orNull($area),
            $orNull($postal), $orNull($address),
            $orNull($dob), $orNull($gender),
            $orNull($language), $orNull($referral), $orNull($prefs),
            $userId,
            ]
        );

        // ── User: profile / NID photo uploads ───────────────────────────────────
        if ($role === 'user') {
            $userProfilePath = saveUpload('profilePhoto', 'profiles');
            $userNidPath     = saveUpload('userNidPhoto', 'nid') ?? saveUpload('nidPhoto', 'nid');

            if ($userProfilePath !== null || $userNidPath !== null) {
                $cur = run_stmt($conn, 'SELECT profile_photo_path, nid_photo_path FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
                $existing = fetch_one_assoc($cur) ?: ['profile_photo_path' => null, 'nid_photo_path' => null];
                $finalProfile = $userProfilePath ?? $existing['profile_photo_path'];
                $finalNid     = $userNidPath ?? $existing['nid_photo_path'];
                run_stmt($conn, 'UPDATE users SET profile_photo_path = ?, nid_photo_path = ?, updated_at = NOW() WHERE id = ?', 'ssi', [$finalProfile, $finalNid, $userId]);
            }
        }

        // ── Worker-only updates ───────────────────────────────────────────────
        if ($role === 'worker') {
            $experience   = $pv('experience');
            $skills       = $pv('skills');
            $nidNumber    = $pv('nidNumber');
            $tradeLicense = $pv('tradeLicense');

            // Handle profile photo upload
            $profilePath = saveUpload('profilePhoto', 'profiles');

            $wpSql = 'UPDATE worker_profiles SET
                        experience=?, skills=?, nid_number=?, trade_license=?,';
            $wpParams = [
                $orNull($experience), $orNull($skills),
                $orNull($nidNumber), $orNull($tradeLicense),
            ];

            // New photo — include it in the update
            if ($profilePath !== null) {
                $wpSql .= ' profile_photo_path=?,';
                $wpParams[] = $profilePath;
                run_stmt($conn, 'UPDATE users SET profile_photo_path = ? WHERE id = ?', 'si', [$profilePath, $userId]);
            }

            $wpSql .= ' updated_at=NOW() WHERE user_id=?';
            $wpParams[] = $userId;
            $types = str_repeat('s', count($wpParams) - 1) . 'i';
            run_stmt($conn, $wpSql, $types, $wpParams);

            // Re-sync worker_services from submitted checkboxes
            $selected = $_POST['services'] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }
            $slugs = array_values(array_unique(array_filter(array_map('strval', $selected))));

            run_stmt($conn, 'DELETE FROM worker_services WHERE worker_user_id=?', 'i', [$userId]);

            if (count($slugs) > 0) {
                $in      = implode(',', array_fill(0, count($slugs), '?'));
                $svcStmt = run_stmt($conn, "SELECT id FROM services WHERE slug IN ($in)", str_repeat('s', count($slugs)), $slugs);
                $insStmt = $conn->prepare('INSERT IGNORE INTO worker_services (worker_user_id, service_id) VALUES (?,?)');
                if (!$insStmt) {
                    throw new RuntimeException('Prepare failed');
                }
                foreach (fetch_all_assoc($svcStmt) as $r) {
                    $sid = (int)$r['id'];
                    $insStmt->bind_param('ii', $userId, $sid);
                    if (!$insStmt->execute()) {
                        throw new RuntimeException('Execute failed');
                    }
                }
            }
        }

        $conn->commit();

        // Keep session name in sync so sidebar/topbar shows updated name immediately
        $_SESSION['user']['name'] = $name;

        $photoStmt = run_stmt($conn, 'SELECT profile_photo_path FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
        $photoRow = fetch_one_assoc($photoStmt);
        $sessionPhoto = $photoRow['profile_photo_path'] ?? null;
        $_SESSION['user']['profile_photo_path'] = $sessionPhoto;

        json_response([
            'success'              => true,
            'message'              => 'Profile updated successfully.',
            'profile_photo_path'   => $sessionPhoto,
        ]);

    } catch (Throwable $e) {
        if (isset($conn) && $conn instanceof mysqli) {
            @$conn->rollback();
        }
        json_response(['success' => false, 'message' => $e->getMessage() ?: 'Server error while updating profile.'], 500);
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// Helper — validate and save a file upload, return web-relative path or null
// ══════════════════════════════════════════════════════════════════════════════
function saveUpload(string $field, string $subdir = 'profiles'): ?string
{
    if (!isset($_FILES[$field])) {
        return null;
    }
    $f = $_FILES[$field];

    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload error for $field");
    }
    if ($f['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException("File too large for $field (max 5 MB)");
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException("Invalid upload for $field");
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime    = mime_content_type($f['tmp_name']) ?: '';
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException("Invalid file type for $field");
    }

    $extMap = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif', 'image/webp' => '.webp'];
    $ext    = $extMap[$mime] ?? '.jpg';

    $subdir = preg_replace('/[^a-z0-9_-]/i', '', $subdir) ?: 'profiles';
    $targetDir = (realpath(dirname(__DIR__)) ?: dirname(__DIR__))
                 . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subdir;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $filename = bin2hex(random_bytes(16)) . $ext;
    $abs      = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($f['tmp_name'], $abs)) {
        throw new RuntimeException("Failed to save $field");
    }

    // Return path relative to project root for browser use
    $rel  = str_replace('\\', '/', $abs);
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    if (str_starts_with($rel, $root)) {
        $rel = ltrim(substr($rel, strlen($root)), '/');
    }
    return $rel;
}
