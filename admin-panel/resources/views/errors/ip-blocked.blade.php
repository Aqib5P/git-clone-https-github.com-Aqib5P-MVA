<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>IP Not Allowed</title>
    <style>
      body { font-family: Arial, sans-serif; background: #f6f7fb; color: #0f172a; }
      .card { max-width: 520px; margin: 12vh auto; background: #fff; border: 1px solid #d9dfeb; border-radius: 12px; padding: 20px; }
      code { background: #eef2ff; padding: 2px 6px; border-radius: 6px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Access blocked</h2>
      <p>Your IP (<code>{{ $ip }}</code>) is not on the allowlist.</p>
      <p>If you are the admin, open <code>/ip/validate</code> with your credentials to add this IP.</p>
    </div>
  </body>
</html>
