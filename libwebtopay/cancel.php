<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Cancelled</title>
  <style>
    body { font-family: Arial, sans-serif; background: #fff7ec; color: #7a3b00; }
    .wrapper { max-width: 720px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h1 { margin-bottom: 12px; }
    p { margin-bottom: 10px; line-height: 1.6; }
    a { color: #7a3b00; font-weight: bold; }
  </style>
</head>
<body>
  <div class="wrapper">
    <h1>Payment cancelled</h1>
    <p>Your application details were received, but the payment was not completed.</p>
    <p>You can try again or contact us at <a href="mailto:info@specialist.college">info@specialist.college</a>.</p>
    <p><a href="https://www.specialist.college/">Return to Specialist Technical College</a></p>
  </div>
  <script>
    sessionStorage.removeItem('stcPendingApplication');
  </script>
</body>
</html>
