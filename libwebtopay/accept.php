<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Submitted</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f3f6fb; color: #0c3f74; }
    .wrapper { max-width: 720px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    h1 { margin-bottom: 12px; }
    p { margin-bottom: 10px; line-height: 1.6; }
    a { color: #0c3f74; font-weight: bold; }
  </style>
</head>
<body>
  <div class="wrapper">
    <h1>Thank you for your application</h1>
    <p id="submission-status">Your payment has been received (or is being processed). We're now finalising your application.</p>
    <p><a href="https://www.specialist.college/">Return to Specialist Technical College</a></p>
  </div>
  <script>
    (function() {
      const PENDING_KEY = 'stcPendingApplication';
      const statusEl = document.getElementById('submission-status');
      const params = new URLSearchParams(window.location.search || '');
      const alreadySubmitted = params.get('submitted') === '1';

      if (alreadySubmitted) {
        if (statusEl) {
          statusEl.textContent = 'Your payment and application were submitted successfully. Our admissions team will contact you shortly.';
        }
        sessionStorage.removeItem(PENDING_KEY);
        return;
      }

      const raw = sessionStorage.getItem(PENDING_KEY);

      if (!raw) {
        if (statusEl) {
          statusEl.textContent = 'We could not locate your application details to send to admissions. Please return to the application form and resubmit after completing payment.';
        }
        return;
      }

      let payload;

      try {
        payload = JSON.parse(raw);
      } catch (error) {
        sessionStorage.removeItem(PENDING_KEY);
        if (statusEl) {
          statusEl.textContent = 'We could not read your saved application details. Please resubmit the form.';
        }
        return;
      }

      const form = document.createElement('form');
      form.method = payload.method || 'POST';
      form.action = payload.action || 'https://formsubmit.co/info@specialist.college';

      const fields = Array.isArray(payload.fields) ? payload.fields : [];

      fields.forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      });

      const hasNext = fields.some(([key]) => key === '_next');
      if (!hasNext) {
        const nextInput = document.createElement('input');
        nextInput.type = 'hidden';
        nextInput.name = '_next';
        nextInput.value = payload.next || (window.location.origin + window.location.pathname + '?submitted=1');
        form.appendChild(nextInput);
      }

      sessionStorage.removeItem(PENDING_KEY);

      document.body.appendChild(form);

      if (statusEl) {
        statusEl.textContent = 'Sending your application details to admissions…';
      }

      form.submit();
    })();
  </script>
</body>
</html>
