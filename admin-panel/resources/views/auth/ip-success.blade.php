<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>IP Validated</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="5;url={{ url('/login') }}">
  </head>
  <body class="bg-slate-50 text-slate-900">
    <div class="max-w-md mx-auto mt-24 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
      <h3 class="text-xl font-semibold mb-2">IP Validated</h3>
      <p class="text-sm text-slate-600">Your IP <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $ip }}</span> has been added.</p>
      <p class="text-sm text-slate-600 mt-2">Redirecting to login in 5 seconds...</p>
      <a class="inline-block mt-4 text-blue-600 hover:text-blue-800" href="{{ url('/login') }}">Go to login</a>
    </div>
  </body>
</html>
