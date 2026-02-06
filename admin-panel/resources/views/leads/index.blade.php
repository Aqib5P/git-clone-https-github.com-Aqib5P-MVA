@extends('layouts.app')

@section('content')
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Lead Filters</h3>
    <form method="get" action="{{ route('leads.index') }}">
      <div class="grid grid-cols-1 md:grid-cols-7 gap-4 items-end">
        <div>
          <label for="start_date" class="text-sm text-slate-600">Start</label>
          <input id="start_date" name="start_date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $filters['start_date'] }}" />
        </div>
        <div>
          <label for="end_date" class="text-sm text-slate-600">End</label>
          <input id="end_date" name="end_date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $filters['end_date'] }}" />
        </div>
        <div>
          <label for="buyer" class="text-sm text-slate-600">Buyer</label>
          <select id="buyer" name="buyer" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All</option>
            @foreach ($buyers as $buyer)
              <option value="{{ $buyer->id }}" @selected((string) $filters['buyer'] === (string) $buyer->id)>
                {{ $buyer->code }} - {{ $buyer->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="status" class="text-sm text-slate-600">Status</label>
          <select id="status" name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All</option>
            <option value="accepted" @selected($filters['status'] === 'accepted')>Accepted</option>
            <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
            <option value="unknown" @selected($filters['status'] === 'unknown')>Unknown</option>
          </select>
        </div>
        <div>
          <label for="scope" class="text-sm text-slate-600">Scope</label>
          <select id="scope" name="scope" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All</option>
            <option value="unified" @selected($filters['scope'] === 'unified')>Unified</option>
            <option value="rtb" @selected($filters['scope'] === 'rtb')>RTB</option>
            <option value="single" @selected($filters['scope'] === 'single')>Single</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label for="search" class="text-sm text-slate-600">Search</label>
          <input id="search" name="search" type="text" placeholder="Phone, email, name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $filters['search'] }}" />
        </div>
        <div class="md:col-span-6">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Apply Filters</button>
        </div>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">Lead Submissions</h3>
      <div class="text-xs text-slate-500">Total: {{ $attempts->total() }}</div>
    </div>

    <div class="space-y-4">
      @forelse ($attempts as $attempt)
          @php
            $status = $attempt->status ?? 'unknown';
            $badgeClass = $status === 'accepted'
              ? 'bg-emerald-100 text-emerald-700'
              : ($status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600');
            $responseJson = is_array($attempt->response_json) ? $attempt->response_json : null;
            $rawResponse = $attempt->response_raw ?: ($responseJson ? json_encode($responseJson, JSON_PRETTY_PRINT) : '');
            $message = '';
            if ($responseJson) {
              $message = $responseJson['message'] ?? $responseJson['msg'] ?? $responseJson['error'] ?? '';
              if (is_array($message)) {
                $message = implode(', ', array_map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v, $message));
              }
              if ($message === '' && !empty($responseJson['errors'])) {
                if (is_array($responseJson['errors'])) {
                  $message = implode(' | ', array_map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v, $responseJson['errors']));
                } else {
                  $message = (string) $responseJson['errors'];
                }
              }
            }
          @endphp
        <div class="rounded-2xl border border-slate-200 p-4">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
              <div class="text-sm text-slate-500">{{ $attempt->created_at }}</div>
              <div class="text-lg font-semibold text-slate-900">
                {{ $attempt->lead->first_name ?? '' }} {{ $attempt->lead->last_name ?? '' }}
              </div>
              <div class="text-sm text-slate-600">{{ $attempt->lead->phone ?? '—' }} · {{ $attempt->lead->email ?? '—' }}</div>
              <div class="text-xs text-slate-500 mt-1">
                Buyer: {{ $attempt->buyer?->code ?? $attempt->endpoint ?? '—' }}
              </div>
              @if ($attempt->is_duplicate)
                <div class="mt-2 inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-xs">Duplicate</div>
              @endif
            </div>
            <div class="text-right">
              <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ ucfirst($status) }}</span>
              <div class="mt-2 text-sm text-slate-600">Payout: ${{ number_format((float) ($attempt->payout ?? 0), 2) }}</div>
              <div class="text-sm text-slate-600">Bid: ${{ number_format((float) ($attempt->bid_amount ?? 0), 2) }}</div>
              <div class="text-sm text-slate-600">Forwarding: {{ $attempt->forwarding_number ?? '—' }}</div>
              <div class="text-sm text-slate-600">HTTP: {{ $attempt->http_status ?? '—' }}</div>
            </div>
          </div>
          <div class="mt-3 text-sm text-slate-600">
            @if ($message !== '')
              <div><span class="font-semibold text-slate-700">Message:</span> {{ $message }}</div>
            @else
              <div><span class="font-semibold text-slate-700">Message:</span> —</div>
            @endif
          </div>
          @if ($rawResponse)
            <details class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
              <summary class="cursor-pointer text-sm text-slate-700">Raw Response</summary>
              <pre class="mt-2 text-xs text-slate-600 whitespace-pre-wrap">{{ $rawResponse }}</pre>
            </details>
          @endif
        </div>
      @empty
        <div class="text-slate-500">No lead submissions found for the selected filters.</div>
      @endforelse
    </div>

    <div class="mt-6">
      {{ $attempts->links() }}
    </div>
  </div>
@endsection
