@extends('layouts.app')

@section('content')
  <div class="max-w-3xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-xl font-semibold">RTB Bid Comparator</h2>
          <p class="text-sm text-slate-500">Pings all RTB buyers and ranks payouts.</p>
        </div>
        <div class="text-xs text-slate-500">
          Buyers: {{ $buyers->count() }}
        </div>
      </div>

      @if (!empty($error))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 px-4 py-3">{{ $error }}</div>
      @endif

      <form method="post" action="{{ route('rtb.submit') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @forelse ($leadFields as $field)
          @php
            $key = $field->key;
            $label = $field->label ?: strtoupper($field->key);
            $type = $field->type ?: 'text';
            $isRequired = in_array($key, $requiredKeys, true);
            $value = old($key);
          @endphp
          <div class="flex flex-col gap-1">
            <label class="text-sm text-slate-600" for="rtb_{{ $key }}">{{ $label }}</label>
            @if ($type === 'select')
              <select id="rtb_{{ $key }}" name="{{ $key }}" class="rounded-lg border border-slate-300 px-3 py-2" @if($isRequired) required @endif>
                <option value="">Select</option>
                @foreach (($field->options ?? []) as $opt)
                  <option value="{{ $opt }}" @selected($value === $opt)>{{ $opt }}</option>
                @endforeach
              </select>
            @elseif ($type === 'textarea')
              <textarea id="rtb_{{ $key }}" name="{{ $key }}" class="rounded-lg border border-slate-300 px-3 py-2" @if($isRequired) required @endif>{{ $value }}</textarea>
            @else
              <input id="rtb_{{ $key }}" type="{{ $type }}" name="{{ $key }}" value="{{ $value }}" class="rounded-lg border border-slate-300 px-3 py-2" @if($isRequired) required @endif />
            @endif
          </div>
        @empty
          <div class="text-sm text-slate-500">No required fields configured for RTB buyers.</div>
        @endforelse
        <div class="md:col-span-2">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Ping RTB Buyers</button>
        </div>
      </form>
    </div>

    @if (!empty($results))
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">Results</h3>
        <div class="space-y-3">
          @foreach ($results as $index => $result)
            @php
              $accepted = strtolower((string) $result['status']) === 'accepted';
              $isTop = $accepted && $index === 0;
            @endphp
            <div class="rounded-xl border {{ $accepted ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-4">
              <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-800">
                  {{ $result['buyer'] }} — {{ ucfirst($result['status']) }}
                  @if ($isTop)
                    <span class="ml-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Top Bid</span>
                  @endif
                </div>
                <div class="text-sm text-slate-700">Bid: ${{ number_format((float) ($result['bid'] ?? 0), 2) }}</div>
              </div>
              <div class="mt-2 text-sm text-slate-600 grid grid-cols-1 md:grid-cols-3 gap-2">
                <div>Forwarding: {{ $result['forwarding_number'] ?? '—' }}</div>
                <div>Min Duration: {{ $result['min_duration'] ?? '—' }}</div>
                <div>Expires: {{ $result['expires'] ?? '—' }}</div>
              </div>
              @if (!empty($result['duplicate']))
                <div class="mt-2 text-xs text-amber-700">Duplicate detected for this buyer.</div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
@endsection
