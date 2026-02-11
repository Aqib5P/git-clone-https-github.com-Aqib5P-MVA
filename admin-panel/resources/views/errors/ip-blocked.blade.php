<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>IP Not Allowed</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900">
    <div class="max-w-lg mx-auto mt-24 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
      <h2 class="text-xl font-semibold mb-2">Access blocked</h2>
      <p class="text-sm text-slate-600">Your IP (<span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $ip }}</span>) is not on the allowlist.</p>
      <p class="text-sm text-slate-600 mt-2">If you are the admin, open <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">/ip/validate</span> with your credentials.</p>
    </div>
  </body>
</html>
