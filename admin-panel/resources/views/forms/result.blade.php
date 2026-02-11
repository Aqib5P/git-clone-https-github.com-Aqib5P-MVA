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
        <h3 class="text-xl font-semibold mb-4">Submission Result</h3>
        @php
          $status = $parsed['status'] ?? 'unknown';
          $body = $parsed['body_json'] ?? [];
          $message = '';
          if (is_array($body)) {
            $message = $body['message'] ?? $body['msg'] ?? $body['error'] ?? '';
            if ($message === '' && !empty($body['errors'])) {
              if (is_array($body['errors'])) {
                $message = implode(' | ', array_map(fn ($v) => is_array($v) ? implode(', ', $v) : $v, $body['errors']));
              } else {
                $message = (string) $body['errors'];
              }
            }
          }
        @endphp
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Status</div>
          <div class="text-lg font-semibold">{{ ucfirst($status) }}</div>
          @if (!empty($parsed['forwarding_number']))
            <div class="mt-2 text-sm text-slate-600">Forwarding: <strong>{{ $parsed['forwarding_number'] }}</strong></div>
          @endif
          @if (!empty($parsed['duration']))
            <div class="text-sm text-slate-600">Duration: <strong>{{ $parsed['duration'] }}</strong></div>
          @endif
          @if (!empty($parsed['ping_id']))
            <div class="text-sm text-slate-600">Ping ID: <strong>{{ $parsed['ping_id'] }}</strong></div>
          @endif
          @if ($message)
            <div class="mt-2 text-sm text-slate-600">Message: <strong>{{ $message }}</strong></div>
          @endif
        </div>
        <details class="mt-4 rounded-lg border border-slate-200 bg-white/60 p-3">
          <summary class="cursor-pointer text-sm text-slate-700">Raw Response</summary>
          <pre class="mt-2 text-xs text-slate-600 whitespace-pre-wrap">{{ $parsed['body_raw'] ?? '' }}</pre>
        </details>
        <a class="inline-block mt-4 rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700" href="{{ url('/f/' . $buyer->public_token) }}">Submit Another</a>
      </div>
    </div>
  </body>
</html>
