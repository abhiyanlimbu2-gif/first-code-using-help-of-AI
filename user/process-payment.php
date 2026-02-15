<?php
require_once __DIR__ . '/../code/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: packages.php");
    exit;
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$method = isset($_POST['method']) ? $_POST['method'] : '';

if ($booking_id <= 0 || empty($method)) {
    header("Location: packages.php");
    exit;
}

$conn = getDBConnection();

// Verify booking exists
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: packages.php");
    exit;
}

// Handle file upload (eSewa only)
$screenshot_path = null;
if ($method === 'esewa' && isset($_FILES['payment_screenshot'])) {
    $file = $_FILES['payment_screenshot'];
    
    // Check if file was uploaded without errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo "Error: Only JPG and PNG files are allowed.";
            exit;
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            http_response_code(400);
            echo "Error: File size should not exceed 5MB.";
            exit;
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/payment-screenshots/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $method . '_' . $booking_id . '_' . time() . '.' . $file_extension;
        $screenshot_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $screenshot_path)) {
            http_response_code(500);
            echo "Error: Failed to upload file.";
            exit;
        }
        
        // Store relative path for database
        $screenshot_path = 'uploads/payment-screenshots/' . $new_filename;
    }
}

// Update booking payment status
// If user uploaded a screenshot (eSewa flow) keep the booking in PENDING state so admin can verify; otherwise mark as paid.
$transaction_id = strtoupper($method) . '_' . time() . rand(1000, 9999);
$payment_status = $screenshot_path ? 'pending' : 'paid';

// Prepare update query with fallbacks in case some columns are not present in the DB
try {
    if ($screenshot_path) {
        // store screenshot but keep status = pending for manual verification by admin
        $stmt = $conn->prepare("UPDATE bookings SET payment_status=?, payment_method=?, transaction_id=?, payment_screenshot=? WHERE booking_id=?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $payment_status, $method, $transaction_id, $screenshot_path, $booking_id);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception($conn->error ?: 'Prepare failed');
        }
    } else {
        // Immediate/automatic methods (no screenshot) remain marked as paid
        $stmt = $conn->prepare("UPDATE bookings SET payment_status=?, payment_method=?, transaction_id=? WHERE booking_id=?");
        if ($stmt) {
            $stmt->bind_param("sssi", $payment_status, $method, $transaction_id, $booking_id);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception($conn->error ?: 'Prepare failed');
        }
    }
} catch (Throwable $e) {
    // Log the error for diagnosis
    $storageDir = __DIR__ . '/storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0755, true);
    }
    $logFile = $storageDir . '/payment_update_error.log';
    file_put_contents($logFile, "[".date('c')."] Payment update failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);

    // Try progressively simpler UPDATEs to avoid fatal errors if columns are missing
    try {
        $stmt = $conn->prepare("UPDATE bookings SET payment_status=?, payment_method=? WHERE booking_id=?");
        if ($stmt) {
            $stmt->bind_param("ssi", $payment_status, $method, $booking_id);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception($conn->error ?: 'Prepare failed');
        }
    } catch (Throwable $e2) {
        // Final safe fallback: only update payment_status
        try {
            $stmt = $conn->prepare("UPDATE bookings SET payment_status=? WHERE booking_id=?");
            if ($stmt) {
                $stmt->bind_param("si", $payment_status, $booking_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Nothing we can do — log and continue so the script doesn't crash
                file_put_contents($logFile, "[".date('c')."] Final fallback failed: " . ($conn->error ?: 'unknown') . PHP_EOL, FILE_APPEND);
            }
        } catch (Throwable $e3) {
            file_put_contents($logFile, "[".date('c')."] Final fallback exception: " . $e3->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
}

$conn->close();

// Redirect to confirmation page
header("Location: bookingconfirm.php?booking_id={$booking_id}");
exit;
?>