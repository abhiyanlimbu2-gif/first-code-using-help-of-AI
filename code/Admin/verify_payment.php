<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
action:
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
if ($booking_id <= 0 || !in_array($action, ['approve','reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$conn = getDBConnection();

// Ensure booking exists
$stmt = $conn->prepare("SELECT booking_id, payment_status FROM bookings WHERE booking_id = ? LIMIT 1");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bk) {
    echo json_encode(['success' => false, 'error' => 'Booking not found']);
    exit;
}

if ($action === 'approve') {
    $newStatus = 'paid';
} else {
    $newStatus = 'rejected';
}

// Update payment_status
$stmt = $conn->prepare("UPDATE bookings SET payment_status = ? WHERE booking_id = ?");
if (! $stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('si', $newStatus, $booking_id);
$ok = $stmt->execute();
$stmt->close();

if (! $ok) {
    echo json_encode(['success' => false, 'error' => 'DB update failed']);
    exit;
}

// If request is AJAX, return JSON; otherwise redirect back to admin bookings
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['success' => true, 'booking_id' => $booking_id, 'payment_status' => $newStatus]);
    exit;
}

header('Location: adminbooking.php');
exit;