<?php
// Top-level Admin redirect — keeps old /Admin/* links working and points to the real admin folder.
// Accessing `/project/Admin/` will be redirected to `/project/code/Admin/login.php`.
header('Location: /project/code/Admin/login.php', true, 302);
exit;

// (If headers are disabled, show a clickable fallback link.)
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Redirecting…</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;padding:24px;">
  <p>If you are not redirected automatically, <a href="/project/code/Admin/login.php">click here to go to Admin login</a>.</p>
</body>
</html>