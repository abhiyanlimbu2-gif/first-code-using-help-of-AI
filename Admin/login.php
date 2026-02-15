<?php
// Redirect legacy /Admin/login.php to the real admin login under code/Admin
header('Location: /project/code/Admin/login.php', true, 302);
exit;

?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Redirecting…</title></head><body style="font-family:Arial,Helvetica,sans-serif;padding:24px;">
<p>If you are not redirected automatically, <a href="/project/code/Admin/login.php">click here to open Admin login</a>.</p>
</body></html>