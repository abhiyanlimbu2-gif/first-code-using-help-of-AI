<?php
// Save this as test_bike_insert.php in your admin folder
require_once __DIR__ . '/../config.php';
requireLogin(); requireAdmin();

echo "<h2>Database Connection Test</h2>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
try {
    $conn = getDBConnection();
    if ($conn) {
        echo "✓ Database connected successfully!<br>";
        echo "Connection type: " . get_class($conn) . "<br>";
    } else {
        echo "✗ Database connection failed!<br>";
        die();
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Check if bikes table exists
echo "<h3>Test 2: Check bikes table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'bikes'");
if ($result->num_rows > 0) {
    echo "✓ Table 'bikes' exists<br>";
} else {
    echo "✗ Table 'bikes' does NOT exist!<br>";
    die();
}

// Test 3: Check table structure
echo "<h3>Test 3: Table Structure</h3>";
$columns = $conn->query("DESCRIBE bikes");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($col = $columns->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table><br>";

// Test 4: Check if sanitize function exists
echo "<h3>Test 4: Check sanitize() function</h3>";
if (function_exists('sanitize')) {
    echo "✓ sanitize() function exists<br>";
    $test = sanitize("Test <script>alert('xss')</script>");
    echo "Test output: " . htmlspecialchars($test) . "<br>";
} else {
    echo "✗ sanitize() function NOT found!<br>";
    echo "Creating temporary sanitize function...<br>";
    function sanitize($str) {
        global $conn;
        return $conn->real_escape_string(trim($str));
    }
}

// Test 5: Try a simple INSERT
echo "<h3>Test 5: Test INSERT Query</h3>";
$test_name = "Test Bike " . time();
$test_price = 1500.00;
$test_status = 'available';

$stmt = $conn->prepare("INSERT INTO bikes (bike_name, price_per_day, availability_status) VALUES (?, ?, ?)");
if (!$stmt) {
    echo "✗ Prepare failed: " . $conn->error . "<br>";
} else {
    $stmt->bind_param("sds", $test_name, $test_price, $test_status);
    
    if ($stmt->execute()) {
        $insert_id = $conn->insert_id;
        echo "✓ INSERT successful! New bike_id: $insert_id<br>";
        
        // Verify it was inserted
        $check = $conn->query("SELECT * FROM bikes WHERE bike_id = $insert_id");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            echo "✓ Verified in database:<br>";
            echo "<pre>" . print_r($row, true) . "</pre>";
            
            // Clean up test data
            $conn->query("DELETE FROM bikes WHERE bike_id = $insert_id");
            echo "<br>✓ Test data cleaned up<br>";
        }
    } else {
        echo "✗ INSERT failed: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

// Test 6: Check current bikes count
echo "<h3>Test 6: Current Bikes Count</h3>";
$count = $conn->query("SELECT COUNT(*) as total FROM bikes");
$row = $count->fetch_assoc();
echo "Total bikes in database: " . $row['total'] . "<br>";

// Test 7: Check image directory
echo "<h3>Test 7: Image Directory Check</h3>";
$imageDir = __DIR__ . '/../image/';
echo "Image directory path: $imageDir<br>";
if (is_dir($imageDir)) {
    echo "✓ Directory exists<br>";
    if (is_writable($imageDir)) {
        echo "✓ Directory is writable<br>";
        
        // Try to create a test file
        $testFile = $imageDir . 'test_' . time() . '.txt';
        if (file_put_contents($testFile, 'test')) {
            echo "✓ Successfully wrote test file<br>";
            unlink($testFile);
            echo "✓ Successfully deleted test file<br>";
        } else {
            echo "✗ Could not write test file<br>";
        }
    } else {
        echo "✗ Directory is NOT writable<br>";
        echo "Current permissions: " . substr(sprintf('%o', fileperms($imageDir)), -4) . "<br>";
    }
} else {
    echo "✗ Directory does NOT exist<br>";
}

// Test 8: Check PHP settings
echo "<h3>Test 8: PHP Upload Settings</h3>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    h3 { color: #555; margin-top: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th { background: #007bff; color: white; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>