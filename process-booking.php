<?php
// Backward-compatibility shim — forwards requests to the real handler in /code/
// This allows older pages or bookmarks that post to /process-booking.php to keep working.
require_once __DIR__ . '/code/process-booking.php';
