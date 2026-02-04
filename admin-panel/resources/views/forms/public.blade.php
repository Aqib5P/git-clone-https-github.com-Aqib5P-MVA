<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $buyer->code }} Lead Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
      body { background: #f6f7fb; }
      .card { border-radius: 16px; }
    </style>
  </head>
  <body>
    <div class="container py-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title">{{ $buyer->code }} — {{ $buyer->name }}</h3>
          <p class="text-muted">Fill out the form and submit.</p>
          <form method="post" action="">
            @csrf
            <div class="row g-3">
              @foreach ($fields as $fieldKey)
                @php
                  $meta = $leadFields[$fieldKey] ?? null;
                @endphp
                <div class="col-md-6">
                  <label class="form-label" for="{{ $fieldKey }}">{{ $meta?->label ?? $fieldKey }}</label>
                  @if ($meta?->type === 'select' && is_array($meta?->options))
                    <select class="form-select" id="{{ $fieldKey }}" name="{{ $fieldKey }}" required>
                      @foreach ($meta->options as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                      @endforeach
                    </select>
                  @else
                    <input class="form-control" id="{{ $fieldKey }}" name="{{ $fieldKey }}" type="{{ $meta?->type ?? 'text' }}" required />
                  @endif
                </div>
              @endforeach
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
