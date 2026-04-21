<?php
session_start();
require_once __DIR__ . '/db.php';

$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

function require_role(string $currentRole, string $requiredRole): void
{
    if ($currentRole !== $requiredRole) {
        json_response(['success' => false, 'message' => 'Forbidden'], 403);
    }
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function booking_code(): string
{
    return strtoupper(bin2hex(random_bytes(6))); // 12 chars
}

function map_display_status(array $row): string
{
    $status = (string)($row['status'] ?? '');
    $note = strtolower((string)($row['last_note'] ?? ''));
    if ($status === 'cancelled' && str_contains($note, 'denied')) {
        return 'denied';
    }
    return $status;
}

function add_display_status(array $rows): array
{
    foreach ($rows as &$row) {
        $row['display_status'] = map_display_status($row);
    }
    unset($row);
    return $rows;
}

function json_post_or_body(): array
{
    $data = read_json_body();
    if (!$data) {
        $data = $_POST;
    }
    return is_array($data) ? $data : [];
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

try {
    global $conn;
    $userId = (int)$sessionUser['id'];
    $role = (string)$sessionUser['role'];

    if ($method === 'GET') {
        $scope = (string)($_GET['scope'] ?? '');

        if ($scope === 'user') {
            require_role($role, 'user');

            $stmt = run_stmt(
                $conn,
                "SELECT 
                    b.id, b.booking_code, b.status, b.scheduled_at, b.address_text, b.notes, b.created_at, b.price,
                    s.name AS service_name, s.slug AS service_slug,
                    w.name AS worker_name,
                    (SELECT note FROM booking_status_history h WHERE h.booking_id = b.id ORDER BY h.id DESC LIMIT 1) AS last_note
                 FROM bookings b
                 INNER JOIN services s ON s.id = b.service_id
                 LEFT JOIN users w ON w.id = b.worker_user_id
                 WHERE b.user_id = ?
                 ORDER BY b.id DESC
                 LIMIT 200",
                'i',
                [$userId]
            );
            $rows = add_display_status(fetch_all_assoc($stmt));
            json_response(['success' => true, 'bookings' => $rows]);
        }

        if ($scope === 'worker_pending') {
            require_role($role, 'worker');

            $stmt = run_stmt(
                $conn,
                "SELECT 
                    b.id, b.booking_code, b.status, b.scheduled_at, b.address_text, b.notes, b.created_at, b.price,
                    s.name AS service_name, s.slug AS service_slug,
                    u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
                    (SELECT note FROM booking_status_history h WHERE h.booking_id = b.id ORDER BY h.id DESC LIMIT 1) AS last_note
                 FROM bookings b
                 INNER JOIN services s ON s.id = b.service_id
                 INNER JOIN users u ON u.id = b.user_id
                 WHERE b.worker_user_id = ? AND b.status = 'pending'
                 ORDER BY b.id DESC
                 LIMIT 200",
                'i',
                [$userId]
            );
            $rows = add_display_status(fetch_all_assoc($stmt));
            json_response(['success' => true, 'bookings' => $rows]);
        }

        if ($scope === 'worker_my') {
            require_role($role, 'worker');

            $stmt = run_stmt(
                $conn,
                "SELECT 
                    b.id, b.booking_code, b.status, b.scheduled_at, b.address_text, b.notes, b.created_at, b.price,
                    s.name AS service_name, s.slug AS service_slug,
                    u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
                    (SELECT note FROM booking_status_history h WHERE h.booking_id = b.id ORDER BY h.id DESC LIMIT 1) AS last_note
                 FROM bookings b
                 INNER JOIN services s ON s.id = b.service_id
                 INNER JOIN users u ON u.id = b.user_id
                 WHERE b.worker_user_id = ? AND b.status IN ('accepted','in_progress')
                 ORDER BY b.id DESC
                 LIMIT 200",
                'i',
                [$userId]
            );
            $rows = add_display_status(fetch_all_assoc($stmt));
            json_response(['success' => true, 'bookings' => $rows]);
        }

        if ($scope === 'worker_completed') {
            require_role($role, 'worker');

            $stmt = run_stmt(
                $conn,
                "SELECT 
                    b.id, b.booking_code, b.status, b.scheduled_at, b.address_text, b.notes, b.created_at, b.price,
                    s.name AS service_name, s.slug AS service_slug,
                    u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
                    (SELECT note FROM booking_status_history h WHERE h.booking_id = b.id ORDER BY h.id DESC LIMIT 1) AS last_note
                 FROM bookings b
                 INNER JOIN services s ON s.id = b.service_id
                 INNER JOIN users u ON u.id = b.user_id
                 WHERE b.worker_user_id = ? AND b.status = 'completed'
                 ORDER BY b.id DESC
                 LIMIT 200",
                'i',
                [$userId]
            );
            $rows = add_display_status(fetch_all_assoc($stmt));
            json_response(['success' => true, 'bookings' => $rows]);
        }

        json_response(['success' => false, 'message' => 'Invalid scope'], 400);
    }

    if ($method === 'POST') {
        $data = json_post_or_body();
        $action = (string)($data['action'] ?? '');

        if ($action === 'create') {
            require_role($role, 'user');

            $serviceSlug = trim((string)($data['service'] ?? ''));
            $workerId = (int)($data['worker_user_id'] ?? 0);
            $scheduledAt = trim((string)($data['scheduled_at'] ?? ''));
            $addressText = trim((string)($data['address_text'] ?? ''));
            $notes = trim((string)($data['notes'] ?? ''));

            if ($serviceSlug === '' || !preg_match('/^[a-z0-9-]{2,64}$/', $serviceSlug)) {
                json_response(['success' => false, 'message' => 'Invalid service'], 400);
            }
            if ($workerId <= 0) {
                json_response(['success' => false, 'message' => 'Please select a professional'], 400);
            }
            if ($addressText === '') {
                json_response(['success' => false, 'message' => 'Address is required'], 400);
            }

            $svc = run_stmt($conn, 'SELECT id, base_price FROM services WHERE slug = ? AND is_active = 1 LIMIT 1', 's', [$serviceSlug]);
            $svcRow = fetch_one_assoc($svc);
            if (!$svcRow) {
                json_response(['success' => false, 'message' => 'Service not found'], 404);
            }
            $serviceId = (int)$svcRow['id'];
            $basePrice = (int)$svcRow['base_price'];

            // Ensure this worker offers this service
            $chk = run_stmt(
                $conn,
                "SELECT 1
                 FROM worker_services ws
                 WHERE ws.worker_user_id = ? AND ws.service_id = ?
                 LIMIT 1",
                'ii',
                [$workerId, $serviceId]
            );
            if (!fetch_one_assoc($chk)) {
                json_response(['success' => false, 'message' => 'Selected worker does not offer this service'], 400);
            }

            $dt = null;
            if ($scheduledAt !== '' && $scheduledAt !== 'null') {
                // datetime-local gives "YYYY-MM-DDTHH:MM"
                $scheduledAt = str_replace('T', ' ', $scheduledAt);
                $d = DateTime::createFromFormat('Y-m-d H:i', $scheduledAt);
                if ($d) {
                    $dt = $d->format('Y-m-d H:i:00');
                }
            }

            $conn->begin_transaction();
            $code = booking_code();
            run_stmt(
                $conn,
                "INSERT INTO bookings (booking_code, user_id, service_id, worker_user_id, status, scheduled_at, address_text, notes, price)
                 VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?)",
                'siiisssi',
                [
                    $code,
                    $userId,
                    $serviceId,
                    $workerId,
                    $dt,
                    $addressText,
                    $notes !== '' ? $notes : null,
                    $basePrice > 0 ? $basePrice : null,
                ]
            );
            $bookingId = (int)$conn->insert_id;

            run_stmt(
                $conn,
                "INSERT INTO booking_status_history (booking_id, from_status, to_status, changed_by_user_id, note)
                 VALUES (?, NULL, 'pending', ?, 'Created by user')",
                'ii',
                [$bookingId, $userId]
            );
            $conn->commit();

            json_response(['success' => true, 'booking_id' => $bookingId, 'booking_code' => $code]);
        }

        if ($action === 'worker_decide') {
            require_role($role, 'worker');

            $bookingId = (int)($data['booking_id'] ?? 0);
            $decision = (string)($data['decision'] ?? '');
            if ($bookingId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid booking'], 400);
            }
            if (!in_array($decision, ['accept', 'deny'], true)) {
                json_response(['success' => false, 'message' => 'Invalid decision'], 400);
            }

            $conn->begin_transaction();

            $cur = run_stmt($conn, 'SELECT id, status FROM bookings WHERE id = ? AND worker_user_id = ? LIMIT 1 FOR UPDATE', 'ii', [$bookingId, $userId]);
            $row = fetch_one_assoc($cur);
            if (!$row) {
                $conn->rollback();
                json_response(['success' => false, 'message' => 'Booking not found'], 404);
            }

            $from = (string)$row['status'];
            if ($from !== 'pending') {
                $conn->rollback();
                json_response(['success' => false, 'message' => 'This request is no longer pending'], 400);
            }

            $to = ($decision === 'accept') ? 'accepted' : 'cancelled';
            $note = ($decision === 'accept') ? 'Accepted by worker' : 'Denied by worker';

            run_stmt($conn, 'UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?', 'si', [$to, $bookingId]);
            run_stmt(
                $conn,
                "INSERT INTO booking_status_history (booking_id, from_status, to_status, changed_by_user_id, note)
                 VALUES (?, ?, ?, ?, ?)",
                'issis',
                [$bookingId, $from, $to, $userId, $note]
            );
            $conn->commit();

            json_response(['success' => true, 'status' => $to]);
        }

        if ($action === 'worker_complete') {
            require_role($role, 'worker');

            $bookingId = (int)($data['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid booking'], 400);
            }

            $conn->begin_transaction();

            $cur = run_stmt($conn, 'SELECT id, status FROM bookings WHERE id = ? AND worker_user_id = ? LIMIT 1 FOR UPDATE', 'ii', [$bookingId, $userId]);
            $row = fetch_one_assoc($cur);
            if (!$row) {
                $conn->rollback();
                json_response(['success' => false, 'message' => 'Booking not found'], 404);
            }

            $from = (string)$row['status'];
            if (!in_array($from, ['accepted', 'in_progress'], true)) {
                $conn->rollback();
                json_response(['success' => false, 'message' => 'Only accepted jobs can be marked completed'], 400);
            }

            $to = 'completed';
            run_stmt($conn, 'UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?', 'si', [$to, $bookingId]);
            run_stmt(
                $conn,
                "INSERT INTO booking_status_history (booking_id, from_status, to_status, changed_by_user_id, note)
                 VALUES (?, ?, ?, ?, 'Completed by worker')",
                'issi',
                [$bookingId, $from, $to, $userId]
            );
            run_stmt($conn, 'UPDATE worker_profiles SET jobs_completed = jobs_completed + 1 WHERE user_id = ?', 'i', [$userId]);
            $conn->commit();

            json_response(['success' => true, 'status' => $to]);
        }

        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

    json_response(['success' => false, 'message' => 'Invalid method'], 405);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        // Ignore rollback error if no transaction is active.
        @$conn->rollback();
    }
    json_response(['success' => false, 'message' => 'Server error'], 500);
}

