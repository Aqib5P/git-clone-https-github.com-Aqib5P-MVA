<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login</title>
    <style>
      body { margin: 0; font-family: Arial, sans-serif; background: #f6f7fb; color: #0f172a; }
      .card { max-width: 420px; margin: 12vh auto; background: #fff; border: 1px solid #d9dfeb; border-radius: 12px; padding: 20px; }
      label { display: block; font-size: 12px; color: #64748b; margin-bottom: 4px; }
      input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #d9dfeb; margin-bottom: 12px; }
      button { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #d9dfeb; background: rgba(37,99,235,0.12); cursor: pointer; }
      .error { color: #dc2626; margin-bottom: 12px; font-size: 13px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Admin Login</h2>
      @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
      @endif
      <form method="post" action="{{ route('login.submit') }}">
        @csrf
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="{{ old('username') }}" required />
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required />
        <button type="submit">Login</button>
      </form>
    </div>
  </body>
</html>
