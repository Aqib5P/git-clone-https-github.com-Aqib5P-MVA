<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900">
    <div class="max-w-md mx-auto mt-24 bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
      <h3 class="text-xl font-semibold mb-4">Admin Login</h3>
      @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-3 py-2">
          {{ $errors->first() }}
        </div>
      @endif
      <form method="post" action="{{ route('login.submit') }}" class="space-y-4">
        @csrf
        <div>
          <label for="username" class="text-sm text-slate-600">Username</label>
          <input id="username" name="username" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('username') }}" required />
        </div>
        <div>
          <label for="password" class="text-sm text-slate-600">Password</label>
          <input id="password" name="password" type="password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Login</button>
      </form>
    </div>
  </body>
</html>
