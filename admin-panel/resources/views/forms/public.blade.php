<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $buyer->code }} Lead Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900">
    <div class="max-w-3xl mx-auto px-6 py-10">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-semibold">{{ $buyer->code }} — {{ $buyer->name }}</h3>
        <p class="text-sm text-slate-500 mt-1">Fill out the form and submit.</p>
        <form method="post" action="" class="mt-6 space-y-4">
          @csrf
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($fields as $fieldItem)
              @php
                $fieldKey = $fieldItem['key'];
                $isRequired = $fieldItem['required'] ?? false;
                $meta = $leadFields[$fieldKey] ?? null;
              @endphp
              <div>
                <label class="text-sm text-slate-600" for="{{ $fieldKey }}">{{ $meta?->label ?? $fieldKey }}</label>
                @if ($meta?->type === 'select' && is_array($meta?->options))
                  <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" id="{{ $fieldKey }}" name="{{ $fieldKey }}" @if($isRequired) required @endif>
                    @foreach ($meta->options as $value => $text)
                      <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                  </select>
                @else
                  <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" id="{{ $fieldKey }}" name="{{ $fieldKey }}" type="{{ $meta?->type ?? 'text' }}" @if($isRequired) required @endif />
                @endif
              </div>
            @endforeach
          </div>

          <div class="pt-4">
            <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
