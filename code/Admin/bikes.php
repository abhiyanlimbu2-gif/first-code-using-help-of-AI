<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getDBConnection();
$error = '';
$success = '';
$debug = []; // For debugging

// Check for success parameter from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Bike added successfully!";
}

// Ensure image folder exists (project root image/)
$imageDir = __DIR__ . '/../image/';
if (!is_dir($imageDir)) {
    if (!@mkdir($imageDir, 0755, true)) {
        $error = "Could not create image directory. Check permissions.";
        $debug[] = "Failed to create directory: $imageDir";
    } else {
        $debug[] = "Created image directory: $imageDir";
    }
} else {
    $debug[] = "Image directory exists: $imageDir";
    // Check if writable
    if (!is_writable($imageDir)) {
        $error = "Image directory is not writable. Please check permissions.";
        $debug[] = "Directory not writable: $imageDir";
    }
}

// Ensure bike_type column exists
$check = $conn->query("SHOW COLUMNS FROM `bikes` LIKE 'bike_type'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE `bikes` ADD COLUMN bike_type VARCHAR(50) DEFAULT 'Other'");
}

// Handle DELETE (also remove image file)
if (isset($_GET['delete'])) {
    $bike_id = intval($_GET['delete']);
    // Get image filename
    $stmtImg = $conn->prepare("SELECT bike_image FROM bikes WHERE bike_id = ? LIMIT 1");
    $stmtImg->bind_param("i", $bike_id);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    $rowImg = $resImg->fetch_assoc();
    $stmtImg->close();

    if ($rowImg && !empty($rowImg['bike_image'])) {
        $file = $imageDir . basename($rowImg['bike_image']);
        if (file_exists($file)) {
            @unlink($file);
            $debug[] = "Deleted image file: $file";
        }
    }

    $stmt = $conn->prepare("DELETE FROM bikes WHERE bike_id = ?");
    $stmt->bind_param("i", $bike_id);
    if ($stmt->execute()) {
        $success = "Bike deleted successfully";
    } else {
        $error = "Error deleting bike: " . $stmt->error;
    }
    $stmt->close();
}

// Handle ADD/EDIT with image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $debug[] = "POST request received";
    $debug[] = "POST data: " . print_r($_POST, true);
    $debug[] = "FILES data: " . print_r($_FILES, true);

    // If this is a confirmation POST, merge previously saved post data (so admin doesn't need to re-upload files)
    if (!empty($_POST['confirm_cancel_paid']) && !empty($_SESSION['pending_paid_cancel'])) {
        $pending = $_SESSION['pending_paid_cancel'];
        if (isset($pending['bike_id']) && isset($_POST['bike_id']) && intval($_POST['bike_id']) === intval($pending['bike_id'])) {
            foreach ($pending['post_data'] as $k => $v) {
                if (!isset($_POST[$k])) $_POST[$k] = $v;
            }
            $debug[] = "Merged pending post data from session for bike_id {$pending['bike_id']}";
        } else {
            $debug[] = "Pending session exists but bike_id mismatch";
        }
    }
    
    // Check if bike_type column exists NOW
    $checkColumn = $conn->query("SHOW COLUMNS FROM `bikes` LIKE 'bike_type'");
    $debug[] = "bike_type column exists: " . ($checkColumn->num_rows > 0 ? 'YES' : 'NO');
    
    $bike_name = sanitize($_POST['bike_name'] ?? '');
    $bike_model = sanitize($_POST['bike_model'] ?? '');
    $bike_type = sanitize($_POST['bike_type'] ?? 'Other');
    $engine_capacity = sanitize($_POST['engine_capacity'] ?? '');
    $mileage = sanitize($_POST['mileage'] ?? '');
    $fuel_tank_capacity = sanitize($_POST['fuel_tank_capacity'] ?? '');
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $availability_status = $_POST['availability_status'] ?? 'available';

    // File upload handling
    $uploadedFileName = null;
    if (isset($_FILES['bike_image']) && $_FILES['bike_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['bike_image'];
        $debug[] = "File upload attempted. Error code: " . $f['error'];
        
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
            ];
            $error = "Error uploading image: " . ($errorMessages[$f['error']] ?? "Unknown error");
            $debug[] = $error;
        } else {
            // Validate size (2MB max)
            $debug[] = "File size: " . $f['size'] . " bytes";
            if ($f['size'] > 2 * 1024 * 1024) {
                $error = "Image must be 2MB or smaller.";
                $debug[] = $error;
            } else {
                // Validate MIME/type & extension
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($f['tmp_name']);
                $debug[] = "Detected MIME type: $mime";
                
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp'
                ];
                
                if (!isset($allowed[$mime])) {
                    $error = "Unsupported image type ($mime). Allowed: JPG, PNG, GIF, WEBP.";
                    $debug[] = $error;
                } else {
                    $ext = $allowed[$mime];
                    $newFileName = time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;
                    $target = $imageDir . $newFileName;
                    $debug[] = "Target path: $target";
                    
                    if (!move_uploaded_file($f['tmp_name'], $target)) {
                        $error = "Failed to save uploaded image to: $target";
                        $debug[] = $error;
                    } else {
                        $uploadedFileName = $newFileName;
                        $debug[] = "Image saved successfully: $newFileName";
                        // Set proper permissions
                        @chmod($target, 0644);
                    }
                }
            }
        }
    } else {
        $debug[] = "No file uploaded or file input empty";
    }

    // Validation
    if (empty($bike_name)) {
        if (!$error) $error = "Bike name is required";
        $debug[] = "Validation failed: empty bike name";
    } elseif ($price_per_day <= 0) {
        if (!$error) $error = "Valid price is required (must be greater than 0)";
        $debug[] = "Validation failed: invalid price ($price_per_day)";
    } else {
        $debug[] = "Validation passed";
        
        if (isset($_POST['bike_id']) && !empty($_POST['bike_id'])) {
            // UPDATE
            $bike_id = intval($_POST['bike_id']);
            $debug[] = "UPDATE operation for bike_id: $bike_id";

            // If admin is changing status from non-available to available, check for paid bookings
            $stmtCur = $conn->prepare("SELECT availability_status FROM bikes WHERE bike_id = ? LIMIT 1");
            $stmtCur->bind_param("i", $bike_id);
            $stmtCur->execute();
            $curA = $stmtCur->get_result()->fetch_assoc();
            $stmtCur->close();

            if ($curA && $curA['availability_status'] !== 'available' && $availability_status === 'available') {
                // Find future paid bookings that would be affected
                $stmtPaid = $conn->prepare("SELECT booking_id, start_date, end_date, customer_name FROM bookings WHERE bike_id = ? AND booking_status NOT IN ('cancelled','completed') AND payment_status='paid' AND start_date >= CURDATE()");
                $stmtPaid->bind_param("i", $bike_id);
                $stmtPaid->execute();
                $resPaid = $stmtPaid->get_result();
                $paidBookings = $resPaid->fetch_all(MYSQLI_ASSOC);
                $stmtPaid->close();

                if (count($paidBookings) > 0 && empty($_POST['confirm_cancel_paid'])) {
                    // Save intended post data (except files) so it can be restored after confirmation
                    $_SESSION['pending_paid_cancel'] = [
                        'bike_id' => $bike_id,
                        'bookings' => $paidBookings,
                        'post_data' => $_POST,
                        'availability_status' => $availability_status
                    ];
                    $debug[] = "Admin attempted to set bike_id {$bike_id} to available but " . count($paidBookings) . " paid booking(s) would be cancelled. Redirecting to confirmation page.";
                    header("Location: bikes.php?confirm_paid=1&edit={$bike_id}");
                    exit();
                }
            }

            // If a new image was uploaded, remove old image after fetching its name
            if ($uploadedFileName) {
                $stmtOld = $conn->prepare("SELECT bike_image FROM bikes WHERE bike_id = ? LIMIT 1");
                $stmtOld->bind_param("i", $bike_id);
                $stmtOld->execute();
                $resOld = $stmtOld->get_result();
                $rowOld = $resOld->fetch_assoc();
                $stmtOld->close();
                if ($rowOld && !empty($rowOld['bike_image'])) {
                    $oldFile = $imageDir . basename($rowOld['bike_image']);
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                        $debug[] = "Deleted old image: $oldFile";
                    }
                }

                $stmt = $conn->prepare("UPDATE bikes SET bike_name=?, bike_model=?, bike_type=?, engine_capacity=?, mileage=?, fuel_tank_capacity=?, price_per_day=?, description=?, availability_status=?, bike_image=? WHERE bike_id=?");
                $stmt->bind_param("ssssssdsssi", $bike_name, $bike_model, $bike_type, $engine_capacity, $mileage, $fuel_tank_capacity, $price_per_day, $description, $availability_status, $uploadedFileName, $bike_id);
            } else {
                $stmt = $conn->prepare("UPDATE bikes SET bike_name=?, bike_model=?, bike_type=?, engine_capacity=?, mileage=?, fuel_tank_capacity=?, price_per_day=?, description=?, availability_status=? WHERE bike_id=?");
                $stmt->bind_param("ssssssdssi", $bike_name, $bike_model, $bike_type, $engine_capacity, $mileage, $fuel_tank_capacity, $price_per_day, $description, $availability_status, $bike_id);
            }

        } else {
            // INSERT (use uploaded filename if available)
            $debug[] = "INSERT operation";
            $imgToStore = $uploadedFileName; // Can be null if no image uploaded
            
            $stmt = $conn->prepare("INSERT INTO bikes (bike_name, bike_model, bike_type, engine_capacity, mileage, fuel_tank_capacity, price_per_day, description, availability_status, bike_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdsss", $bike_name, $bike_model, $bike_type, $engine_capacity, $mileage, $fuel_tank_capacity, $price_per_day, $description, $availability_status, $imgToStore);
            
            $debug[] = "SQL prepared with values:";
            $debug[] = "bike_name: $bike_name";
            $debug[] = "bike_model: $bike_model";
            $debug[] = "bike_type: $bike_type";
            $debug[] = "engine_capacity: $engine_capacity";
            $debug[] = "mileage: $mileage";
            $debug[] = "fuel_tank_capacity: $fuel_tank_capacity";
            $debug[] = "price_per_day: $price_per_day";
            $debug[] = "description: $description";
            $debug[] = "availability_status: $availability_status";
            $debug[] = "bike_image: " . ($imgToStore ?? 'NULL');
        }

        if ($stmt->execute()) {
            $success = isset($bike_id) ? "Bike updated successfully" : "Bike added successfully (ID: " . $conn->insert_id . ")";
            $debug[] = "Database operation successful";

            // If this was an UPDATE and admin set availability to 'available', cancel any conflicting future bookings
            if (isset($bike_id) && isset($availability_status) && $availability_status === 'available') {
                $debug[] = "Admin set bike_id {$bike_id} availability to 'available' - checking for conflicting bookings";

                // If admin confirmed, cancel paid bookings too
                $cancelAll = !empty($_POST['confirm_cancel_paid']);
                if ($cancelAll) {
                    $debug[] = "Admin confirmed cancelling paid bookings as well.";
                    $stmtCancel = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE bike_id = ? AND booking_status NOT IN ('cancelled','completed') AND start_date >= CURDATE()");
                } else {
                    $stmtCancel = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE bike_id = ? AND booking_status NOT IN ('cancelled','completed') AND payment_status != 'paid' AND start_date >= CURDATE()");
                }

                if ($stmtCancel) {
                    $stmtCancel->bind_param("i", $bike_id);
                    if ($stmtCancel->execute()) {
                        $cancelled = $stmtCancel->affected_rows;
                        $debug[] = "Cancelled {$cancelled} future booking(s) for bike_id {$bike_id} because availability set to available.";
                        if ($cancelled > 0) {
                            // Append to success message so admin sees the effect
                            $success .= " {$cancelled} conflicting booking(s) were cancelled.";
                        }
                        // Clear pending confirmation if any
                        if (!empty($_SESSION['pending_paid_cancel']) && intval($_SESSION['pending_paid_cancel']['bike_id']) === $bike_id) {
                            unset($_SESSION['pending_paid_cancel']);
                        }
                    } else {
                        $debug[] = "Failed to cancel conflicting bookings: " . $stmtCancel->error;
                    }
                    $stmtCancel->close();
                } else {
                    $debug[] = "Failed to prepare cancel statement: " . $conn->error;
                }
            }

            // Redirect to refresh the page and show the new bike
            if (!isset($bike_id)) {
                // Only redirect on INSERT, not UPDATE
                header("Location: bikes.php?success=1");
                exit();
            }
        } else {
            $error = "Error saving bike: " . $stmt->error;
            $debug[] = "Database error: " . $stmt->error;
            // Cleanup uploaded file on DB failure
            if ($uploadedFileName && file_exists($imageDir . $uploadedFileName)) {
                @unlink($imageDir . $uploadedFileName);
                $debug[] = "Cleaned up uploaded file due to DB error";
            }
        }
        $stmt->close();
    }
}

// Get bike for editing
$edit_bike = null;
if (isset($_GET['edit'])) {
    $bike_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM bikes WHERE bike_id = ?");
    $stmt->bind_param("i", $bike_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_bike = $result->fetch_assoc();
    $stmt->close();
}

// Cancel pending confirmation and return to edit
if (isset($_GET['cancel_confirm']) && !empty($_SESSION['pending_paid_cancel'])) {
    $pendingBike = intval($_SESSION['pending_paid_cancel']['bike_id'] ?? 0);
    unset($_SESSION['pending_paid_cancel']);
    header("Location: bikes.php?edit={$pendingBike}");
    exit();
} 

// Get all bikes
$bikes = $conn->query("SELECT * FROM bikes ORDER BY bike_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bikes - Admin</title>
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      /* Thumbnails: fixed size and centered for even rows */
      .thumb { display:block; width:80px; height:50px; object-fit:cover; object-position:center; border-radius:6px; margin:0 auto; vertical-align:middle; }
      .form-note { font-size:0.9rem;color:#666;margin-top:6px; }
      .debug-info { background:#f0f0f0; border:1px solid #ccc; padding:10px; margin:10px 0; font-family:monospace; font-size:12px; max-height:300px; overflow-y:auto; }
      .debug-info h3 { margin-top:0; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Mero<span>Ride</span></h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="bikes.php" class="nav-item active">
                    <i class="fas fa-motorcycle"></i>
                    <span>Manage Bikes</span>
                </a>
                <a href="adminbooking.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Manage Bikes</h1>
            </header>

            <!-- Messages -->
            <?php if ($success): ?>
                <div class="alert success" style="margin:10px 0;padding:10px;border-radius:6px;background:#e6ffed;color:#0a662a;border:1px solid #c8f2d1;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error" style="margin:10px 0;padding:10px;border-radius:6px;background:#ffe6e6;color:#b30000;border:1px solid #f5c8c8;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>



            <?php if (isset($_GET['confirm_paid']) && $_GET['confirm_paid'] == 1 && !empty($_SESSION['pending_paid_cancel']) && intval($_SESSION['pending_paid_cancel']['bike_id']) === intval($_GET['edit'] ?? $_SESSION['pending_paid_cancel']['bike_id'])): ?>
                <?php $pending = $_SESSION['pending_paid_cancel']; ?>
                <div class="alert" style="background:#fff3cd;border:1px solid #ffeeba;color:#856404;padding:12px;border-radius:6px;margin:10px 0;">
                    <strong>Warning:</strong> There are <strong><?php echo count($pending['bookings']); ?></strong> <em>paid</em> booking(s) for this bike starting in the future. If you proceed, these bookings will be cancelled.
                    <div style="margin-top:8px;">
                        <ul style="margin:0;padding-left:20px;">
                            <?php foreach ($pending['bookings'] as $pb): ?>
                                <li><?php echo htmlspecialchars($pb['customer_name'] . ' — ' . $pb['start_date'] . ' to ' . $pb['end_date'] . ' (Booking ID: ' . $pb['booking_id'] . ')'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div style="margin-top:10px;">
                        <form method="POST" action="">
                            <input type="hidden" name="bike_id" value="<?php echo intval($pending['bike_id']); ?>">
                            <input type="hidden" name="confirm_cancel_paid" value="1">
                            <button type="submit" class="btn-submit" style="background:#c82333;padding:8px 12px;border-radius:4px;color:#fff;border:none;">Confirm and Cancel Paid Bookings</button>
                            <a href="bikes.php?cancel_confirm=1&edit=<?php echo intval($pending['bike_id']); ?>" style="margin-left:10px;">Cancel</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

      

            <!-- Add/Edit Form -->
            <div class="form-container">
                <h2><?php echo $edit_bike ? 'Edit Bike' : 'Add New Bike'; ?></h2>
                <form method="POST" action="" id="bikeForm" enctype="multipart/form-data">
                    <?php if ($edit_bike): ?>
                        <input type="hidden" name="bike_id" value="<?php echo $edit_bike['bike_id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Bike Name *</label>
                        <input type="text" name="bike_name" required value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['bike_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Bike Model</label>
                        <input type="text" name="bike_model" value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['bike_model']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Bike Type</label>
                        <select name="bike_type">
                            <?php
                            $types = ['scooter','adventure bikes','Electric','classic bikes','casual bike','Other'];
                            $selected = $edit_bike ? $edit_bike['bike_type'] : 'Other';
                            foreach ($types as $t) {
                                $sel = ($selected == $t) ? 'selected' : '';
                                echo "<option value=\"{$t}\" {$sel}>$t</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="bike_image" accept="image/*" id="bikeImageInput">
                        <?php if ($edit_bike && !empty($edit_bike['bike_image'])): ?>
                            <div class="form-note">Current image:</div>
                            <img src="<?php echo '../image/' . htmlspecialchars($edit_bike['bike_image']); ?>" class="thumb" alt="Current image">
                        <?php endif; ?>
                        <div class="form-note">Max 2MB. JPG/PNG/GIF/WEBP allowed. Leave empty to keep current image.</div>
                    </div>

                    <div class="form-group">
                        <label>Engine Capacity (cc)</label>
                        <input type="text" name="engine_capacity" value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['engine_capacity']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Mileage (km/l)</label>
                        <input type="text" name="mileage" value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['mileage']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Fuel Tank Capacity (liters)</label>
                        <input type="text" name="fuel_tank_capacity" value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['fuel_tank_capacity']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Price Per Day (रु) *</label>
                        <input type="number" name="price_per_day" step="0.01" required value="<?php echo $edit_bike ? $edit_bike['price_per_day'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Availability Status</label>
                        <select name="availability_status">
                            <option value="available" <?php echo ($edit_bike && $edit_bike['availability_status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="rented" <?php echo ($edit_bike && $edit_bike['availability_status'] == 'rented') ? 'selected' : ''; ?>>Rented</option>
                            <option value="maintenance" <?php echo ($edit_bike && $edit_bike['availability_status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                        <div class="form-note">Note: Setting a bike to <strong>Available</strong> will automatically cancel future, unpaid bookings that conflict with its schedule so the bike can be rebooked.</div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo $edit_bike ? htmlspecialchars($edit_bike['description']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <?php echo $edit_bike ? 'Update Bike' : 'Add Bike'; ?>
                    </button>
                    <?php if ($edit_bike): ?>
                        <a href="bikes.php" class="btn-submit" style="background: #666; margin-left: 10px; text-decoration: none; display: inline-block;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bikes List -->
            <div class="recent-section" style="margin-top: 40px;">
                <h2>All Bikes</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Model</th>
                                <th>Type</th>
                                <th>Engine</th>
                                <th>Price/Day</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($bikes && $bikes->num_rows > 0):
                                while ($bike = $bikes->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>#<?php echo $bike['bike_id']; ?></td>
                                <td>
                                    <?php
                                        // Only show an <img> if the image file actually exists on disk.
                                        $imgfile = '';
                                        if (!empty($bike['bike_image'])) {
                                            $candidate = $imageDir . basename($bike['bike_image']);
                                            if (file_exists($candidate)) {
                                                $imgfile = '../image/' . rawurlencode(basename($bike['bike_image']));
                                            }
                                        }
                                    ?>
                                    <?php if ($imgfile): ?>
                                    <img src="<?php echo $imgfile; ?>" alt="bike image" class="thumb">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($bike['bike_name']); ?></td>
                                <td><?php echo htmlspecialchars($bike['bike_model']); ?></td>
                                <td><?php echo htmlspecialchars($bike['bike_type'] ?? 'Other'); ?></td>
                                <td><?php echo htmlspecialchars($bike['engine_capacity']); ?></td>
                                <td>रु <?php echo number_format($bike['price_per_day'], 2); ?></td>
                                <td><span class="status-badge <?php echo $bike['availability_status']; ?>"><?php echo ucfirst($bike['availability_status']); ?></span></td>
                                <td>
                                    <a href="bikes.php?edit=<?php echo $bike['bike_id']; ?>" class="btn-edit">Edit</a>
                                    <a href="bikes.php?delete=<?php echo $bike['bike_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this bike?')">Delete</a>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No bikes found. Add your first bike above!</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Form validation
        document.getElementById('bikeForm').addEventListener('submit', function(e) {
            const bikeName = document.querySelector('input[name="bike_name"]').value.trim();
            const price = document.querySelector('input[name="price_per_day"]').value;

            if (!bikeName) {
                e.preventDefault();
                alert('Bike name is required');
                return false;
            }

            if (!price || parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return false;
            }
        });

        // File input preview (optional enhancement)
        document.getElementById('bikeImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image must be 2MB or smaller');
                    this.value = '';
                    return;
                }
                
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WEBP)');
                    this.value = '';
                    return;
                }
                
                console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>