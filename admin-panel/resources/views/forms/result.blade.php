<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $buyer->code }} Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900">
    <div class="max-w-xl mx-auto px-6 py-12">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-semibold mb-2">Submission Result</h3>
        @php
          $status = $parsed['status'] ?? 'unknown';
        @endphp
        <p class="text-sm text-slate-600">Status: <strong>{{ ucfirst($status) }}</strong></p>
        @if (!empty($parsed['forwarding_number']))
          <p class="text-sm text-slate-600">Forwarding: <strong>{{ $parsed['forwarding_number'] }}</strong></p>
        @endif
        <a class="inline-block mt-4 rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700" href="{{ url('/f/' . $buyer->public_token) }}">Submit Another</a>
      </div>
    </div>
  </body>
</html>
