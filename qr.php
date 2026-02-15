<?php
// Minimal QR-only page used by the scanner button
// Prefers project QR image (code/image/qrimage.jpg) when available
$qrPath = file_exists(__DIR__ . '/code/image/qrimage.jpg') ? '/project/code/image/qrimage.jpg' : '/project/qrimage.png';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>eSewa QR</title>
  <style>
    html,body{height:100%;margin:0;background:#fafafa;font-family:Inter,system-ui,Arial,sans-serif}
    .wrap{min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 18px 60px rgba(2,6,23,0.08);display:flex;flex-direction:column;align-items:center;gap:14px}
    .qr{width:320px;height:320px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:#fff;overflow:hidden}
    .caption{color:#444;font-size:14px}
    @media (max-width:480px){.qr{width:220px;height:220px}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card" role="main">
      <div class="qr">
        <img src="<?php echo $qrPath; ?>" alt="eSewa QR" style="width:100%;height:100%;object-fit:contain;display:block">
      </div>
      <div class="caption">Scan this QR with your eSewa app to pay</div>
    </div>
  </div>
</body>
</html>