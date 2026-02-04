<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
      body { margin: 0; font-family: Arial, sans-serif; background: #f6f7fb; color: #0f172a; }
      .card { max-width: 420px; margin: 12vh auto; }
    </style>
  </head>
  <body>
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="card-title mb-3">Admin Login</h3>
        @if ($errors->any())
          <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('login.submit') }}">
          @csrf
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input id="username" name="username" type="text" class="form-control" value="{{ old('username') }}" required />
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" class="form-control" required />
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </body>
</html>
