<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $buyer->code }} Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  </head>
  <body>
    <div class="container py-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title">Submission Result</h3>
          @php
            $status = $parsed['status'] ?? 'unknown';
          @endphp
          <p>Status: <strong>{{ ucfirst($status) }}</strong></p>
          @if (!empty($parsed['forwarding_number']))
            <p>Forwarding: <strong>{{ $parsed['forwarding_number'] }}</strong></p>
          @endif
          @if (!empty($parsed['payout']))
            <p>Payout: <strong>{{ $parsed['payout'] }}</strong></p>
          @endif
          <a class="btn btn-primary" href="{{ url('/f/' . $buyer->public_token) }}">Submit Another</a>
        </div>
      </div>
    </div>
  </body>
</html>
