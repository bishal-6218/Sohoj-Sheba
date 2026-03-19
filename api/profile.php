<?php
session_start();
require_once __DIR__ . '/db.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

// ── Route ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    handleGet($sessionUser);
} elseif ($method === 'POST') {
    handlePost($sessionUser);
} else {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}


// ══════════════════════════════════════════════════════════════════════════════
// GET — fetch full profile
// ══════════════════════════════════════════════════════════════════════════════
function handleGet(array $user): void {
    try {
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT id, role, name, email, phone, whatsapp, alternative_phone,
                    country, city, area, postal_code, address,
                    date_of_birth, gender,
                    preferred_language, referral_source, preferences_text,
                    newsletter_opt_in, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

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
        ];

        // Worker extras
        if ($row['role'] === 'worker') {
            $wStmt = $pdo->prepare(
                'SELECT experience, skills, nid_number, trade_license,
                        profile_photo_path, rating_avg, jobs_completed
                 FROM worker_profiles WHERE user_id = ? LIMIT 1'
            );
            $wStmt->execute([$user['id']]);
            $wp = $wStmt->fetch();

            if ($wp) {
                $profile['experience']         = $wp['experience'];
                $profile['skills']             = $wp['skills'];
                $profile['nid_number']         = $wp['nid_number'];
                $profile['trade_license']      = $wp['trade_license'];
                $profile['profile_photo_path'] = $wp['profile_photo_path'];
                $profile['rating_avg']         = (float)$wp['rating_avg'];
                $profile['jobs_completed']     = (int)$wp['jobs_completed'];
            }

            // Services this worker offers — return both name (display) and slug (for checkboxes)
            $sStmt = $pdo->prepare(
                'SELECT s.name, s.slug FROM services s
                 INNER JOIN worker_services ws ON ws.service_id = s.id
                 WHERE ws.worker_user_id = ?'
            );
            $sStmt->execute([$user['id']]);
            $svcRows = $sStmt->fetchAll();
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

    $pv     = fn(string $key) => trim((string)($_POST[$key] ?? ''));
    $orNull = fn(string $v)   => $v !== '' ? $v : null;

    try {
        $pdo    = db();
        $userId = (int)$sessionUser['id'];
        $role   = $sessionUser['role'];

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
            if (!$d || $d->format('Y-m-d') !== $dob) $dob = '';
        }

        $pdo->beginTransaction();

        // ── Update users table ────────────────────────────────────────────────
        $pdo->prepare(
            'UPDATE users SET
                name=?, phone=?, whatsapp=?, alternative_phone=?,
                country=?, city=?, area=?, postal_code=?, address=?,
                date_of_birth=?, gender=?,
                preferred_language=?, referral_source=?, preferences_text=?,
                updated_at=NOW()
             WHERE id=?'
        )->execute([
            $name,
            $orNull($phone), $orNull($whatsapp), $orNull($altPhone),
            $orNull($country), $orNull($city), $orNull($area),
            $orNull($postal), $orNull($address),
            $orNull($dob), $orNull($gender),
            $orNull($language), $orNull($referral), $orNull($prefs),
            $userId,
        ]);

        // ── Worker-only updates ───────────────────────────────────────────────
        if ($role === 'worker') {
            $experience   = $pv('experience');
            $skills       = $pv('skills');
            $nidNumber    = $pv('nidNumber');
            $tradeLicense = $pv('tradeLicense');

            // Handle profile photo upload
            $profilePath = saveUpload('profilePhoto');

            if ($profilePath !== null) {
                // New photo — include it in the update
                $pdo->prepare(
                    'UPDATE worker_profiles SET
                        experience=?, skills=?, nid_number=?, trade_license=?,
                        profile_photo_path=?, updated_at=NOW()
                     WHERE user_id=?'
                )->execute([
                    $orNull($experience), $orNull($skills),
                    $orNull($nidNumber),  $orNull($tradeLicense),
                    $profilePath, $userId,
                ]);
            } else {
                // No new photo — leave existing photo untouched
                $pdo->prepare(
                    'UPDATE worker_profiles SET
                        experience=?, skills=?, nid_number=?, trade_license=?,
                        updated_at=NOW()
                     WHERE user_id=?'
                )->execute([
                    $orNull($experience), $orNull($skills),
                    $orNull($nidNumber),  $orNull($tradeLicense),
                    $userId,
                ]);
            }

            // Re-sync worker_services from submitted checkboxes
            $selected = $_POST['services'] ?? [];
            if (!is_array($selected)) $selected = [];
            $slugs = array_values(array_unique(array_filter(array_map('strval', $selected))));

            $pdo->prepare('DELETE FROM worker_services WHERE worker_user_id=?')->execute([$userId]);

            if (count($slugs) > 0) {
                $in      = implode(',', array_fill(0, count($slugs), '?'));
                $svcStmt = $pdo->prepare("SELECT id FROM services WHERE slug IN ($in)");
                $svcStmt->execute($slugs);
                $insStmt = $pdo->prepare('INSERT IGNORE INTO worker_services (worker_user_id, service_id) VALUES (?,?)');
                foreach ($svcStmt->fetchAll() as $r) {
                    $insStmt->execute([$userId, (int)$r['id']]);
                }
            }
        }

        $pdo->commit();

        // Keep session name in sync so sidebar/topbar shows updated name immediately
        $_SESSION['user']['name'] = $name;

        json_response(['success' => true, 'message' => 'Profile updated successfully.']);

    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        json_response(['success' => false, 'message' => $e->getMessage() ?: 'Server error while updating profile.'], 500);
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// Helper — validate and save a file upload, return web-relative path or null
// ══════════════════════════════════════════════════════════════════════════════
function saveUpload(string $field): ?string {
    if (!isset($_FILES[$field])) return null;
    $f = $_FILES[$field];

    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK)     throw new RuntimeException("Upload error for $field");
    if ($f['size']  > 5 * 1024 * 1024)    throw new RuntimeException("File too large for $field (max 5 MB)");
    if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException("Invalid upload for $field");

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime    = mime_content_type($f['tmp_name']) ?: '';
    if (!in_array($mime, $allowed, true))  throw new RuntimeException("Invalid file type for $field");

    $extMap = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif', 'image/webp' => '.webp'];
    $ext    = $extMap[$mime] ?? '.jpg';

    $targetDir = (realpath(dirname(__DIR__)) ?: dirname(__DIR__))
                 . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';

    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $filename = bin2hex(random_bytes(16)) . $ext;
    $abs      = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($f['tmp_name'], $abs)) throw new RuntimeException("Failed to save $field");

    // Return path relative to project root for browser use
    $rel  = str_replace('\\', '/', $abs);
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    if (str_starts_with($rel, $root)) $rel = ltrim(substr($rel, strlen($root)), '/');
    return $rel;
}