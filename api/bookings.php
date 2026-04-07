<?php
session_start();
require_once __DIR__ . '/db.php';

$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    json_response(['success' => false, 'message' => 'Not authenticated'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

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

try {
    $pdo = db();
    $uid = (int)$sessionUser['id'];
    $role = (string)$sessionUser['role'];

    if ($method === 'GET') {
        $scope = (string)($_GET['scope'] ?? '');

        if ($scope === 'user') {
            if ($role !== 'user') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $stmt = $pdo->prepare(
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
                 LIMIT 200"
            );
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['display_status'] = map_display_status($r);
            }
            json_response(['success' => true, 'bookings' => $rows]);
        }

        if ($scope === 'worker_pending') {
            if ($role !== 'worker') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $stmt = $pdo->prepare(
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
                 LIMIT 200"
            );
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['display_status'] = map_display_status($r);
            }
            json_response(['success' => true, 'bookings' => $rows]);
        }

        if ($scope === 'worker_my') {
            if ($role !== 'worker') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $stmt = $pdo->prepare(
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
                 LIMIT 200"
            );
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['display_status'] = map_display_status($r);
            }
            json_response(['success' => true, 'bookings' => $rows]);
        }

        if ($scope === 'worker_completed') {
            if ($role !== 'worker') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $stmt = $pdo->prepare(
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
                 LIMIT 200"
            );
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['display_status'] = map_display_status($r);
            }
            json_response(['success' => true, 'bookings' => $rows]);
        }

        json_response(['success' => false, 'message' => 'Invalid scope'], 400);
    }

    if ($method === 'POST') {
        $data = read_json_body();
        if (!$data) {
            $data = $_POST;
        }
        $action = (string)($data['action'] ?? '');

        if ($action === 'create') {
            if ($role !== 'user') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

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

            $svc = $pdo->prepare('SELECT id, base_price FROM services WHERE slug = ? AND is_active = 1 LIMIT 1');
            $svc->execute([$serviceSlug]);
            $svcRow = $svc->fetch();
            if (!$svcRow) {
                json_response(['success' => false, 'message' => 'Service not found'], 404);
            }
            $serviceId = (int)$svcRow['id'];
            $basePrice = (int)$svcRow['base_price'];

            // Ensure this worker offers this service
            $chk = $pdo->prepare(
                "SELECT 1
                 FROM worker_services ws
                 WHERE ws.worker_user_id = ? AND ws.service_id = ?
                 LIMIT 1"
            );
            $chk->execute([$workerId, $serviceId]);
            if (!$chk->fetchColumn()) {
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

            $pdo->beginTransaction();
            $code = booking_code();
            $ins = $pdo->prepare(
                "INSERT INTO bookings (booking_code, user_id, service_id, worker_user_id, status, scheduled_at, address_text, notes, price)
                 VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?)"
            );
            $ins->execute([
                $code,
                $uid,
                $serviceId,
                $workerId,
                $dt,
                $addressText,
                $notes !== '' ? $notes : null,
                $basePrice > 0 ? $basePrice : null,
            ]);
            $bookingId = (int)$pdo->lastInsertId();

            $hist = $pdo->prepare(
                "INSERT INTO booking_status_history (booking_id, from_status, to_status, changed_by_user_id, note)
                 VALUES (?, NULL, 'pending', ?, 'Created by user')"
            );
            $hist->execute([$bookingId, $uid]);

            $pdo->commit();

            json_response(['success' => true, 'booking_id' => $bookingId, 'booking_code' => $code]);
        }

        if ($action === 'worker_decide') {
            if ($role !== 'worker') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $bookingId = (int)($data['booking_id'] ?? 0);
            $decision = (string)($data['decision'] ?? '');
            if ($bookingId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid booking'], 400);
            }
            if (!in_array($decision, ['accept', 'deny'], true)) {
                json_response(['success' => false, 'message' => 'Invalid decision'], 400);
            }

            $pdo->beginTransaction();

            $cur = $pdo->prepare('SELECT id, status FROM bookings WHERE id = ? AND worker_user_id = ? LIMIT 1 FOR UPDATE');
            $cur->execute([$bookingId, $uid]);
            $row = $cur->fetch();
            if (!$row) {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'Booking not found'], 404);
            }

            $from = (string)$row['status'];
            if ($from !== 'pending') {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'This request is no longer pending'], 400);
            }

            $to = ($decision === 'accept') ? 'accepted' : 'cancelled';
            $note = ($decision === 'accept') ? 'Accepted by worker' : 'Denied by worker';

            $upd = $pdo->prepare('UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$to, $bookingId]);

            $hist = $pdo->prepare(
                "INSERT INTO booking_status_history (booking_id, from_status, to_status, changed_by_user_id, note)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $hist->execute([$bookingId, $from, $to, $uid, $note]);

            $pdo->commit();

            json_response(['success' => true, 'status' => $to]);
        }

        if ($action === 'worker_complete') {
            if ($role !== 'worker') {
                json_response(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $bookingId = (int)($data['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid booking'], 400);
            }

            $pdo->beginTransaction();

            $cur = $pdo->prepare('SELECT id, status FROM bookings WHERE id = ? AND worker_user_id = ? LIMIT 1 FOR UPDATE');
            $cur->execute([$bookingId, $uid]);
            $row = $cur->fetch();
            if (!$row) {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'Booking not found'], 404);
            }

            $from = (string)$row['status'];
            if (!in_array($from, ['accepted', 'in_progress'], true)) {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'Only accepted jobs can be marked completed'], 400);
            }

            $to = 'completed';
            $upd = $pdo->prepare('UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$to, $bookingId]);

            $hist = $pdo->prepare(
                "INSERT INTO booking_status_history (booking_id, from_status, to_status, changed_by_user_id, note)
                 VALUES (?, ?, ?, ?, 'Completed by worker')"
            );
            $hist->execute([$bookingId, $from, $to, $uid]);

            $inc = $pdo->prepare('UPDATE worker_profiles SET jobs_completed = jobs_completed + 1 WHERE user_id = ?');
            $inc->execute([$uid]);

            $pdo->commit();

            json_response(['success' => true, 'status' => $to]);
        }

        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

    json_response(['success' => false, 'message' => 'Invalid method'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Server error'], 500);
}

