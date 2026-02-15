<?php
// Only use this script when setting up the app (delete it or protect it afterwards).
require_once __DIR__ . '/../config.php';   // corrected path
$conn = getDBConnection();

$full_name = 'Admin User';
$email = 'admin@meroride.com';
$password_plain = 'admin123'; // change this after first login
$pw_hash = password_hash($password_plain, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, 'admin')");
$stmt->bind_param("sss", $full_name, $email, $pw_hash);
if ($stmt->execute()) {
    echo "Admin created: $email\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}
$stmt->close();
$conn->close();
?>