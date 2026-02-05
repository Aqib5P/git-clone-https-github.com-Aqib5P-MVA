@extends('layouts.app')

@section('content')
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Filters</h3>
    <form method="get" action="{{ route('dashboard') }}">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
          <label for="start_date" class="text-sm text-slate-600">Start</label>
          <input id="start_date" name="start_date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $filters['start_date'] }}" />
        </div>
        <div>
          <label for="end_date" class="text-sm text-slate-600">End</label>
          <input id="end_date" name="end_date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $filters['end_date'] }}" />
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
          <label for="buyer" class="text-sm text-slate-600">Buyer</label>
          <select id="buyer" name="buyer" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All</option>
            @foreach ($buyers as $buyer)
              <option value="{{ $buyer->code }}" @selected($filters['buyer'] === $buyer->code)>{{ $buyer->code }} - {{ $buyer->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Apply</button>
        </div>
      </div>
    </form>
  </div>

  @php
    $accepted = $statusCounts['accepted'] ?? 0;
    $rejected = $statusCounts['rejected'] ?? 0;
    $unknown = $statusCounts['unknown'] ?? 0;
    $total = $accepted + $rejected + $unknown;
  @endphp

  <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
      <div class="text-sm text-emerald-600">Accepted</div>
      <div class="text-2xl font-semibold">{{ $accepted }}</div>
    </div>
    <div class="rounded-2xl bg-red-50 border border-red-100 p-4">
      <div class="text-sm text-red-600">Rejected</div>
      <div class="text-2xl font-semibold">{{ $rejected }}</div>
    </div>
    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
      <div class="text-sm text-slate-600">Unknown</div>
      <div class="text-2xl font-semibold">{{ $unknown }}</div>
    </div>
    <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
      <div class="text-sm text-blue-600">Total</div>
      <div class="text-2xl font-semibold">{{ $total }}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-sm text-slate-500">Acceptance Rate</div>
      <div class="text-2xl font-semibold">{{ $acceptRate }}%</div>
      <div class="mt-2 h-2 rounded-full bg-slate-100">
        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $acceptRate }}%"></div>
      </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-sm text-slate-500">Top Buyer (Accepted)</div>
      <div class="text-lg font-semibold">{{ $topBuyer?->endpoint ?? '—' }}</div>
      <div class="text-sm text-slate-500">{{ $topBuyer?->accepted ?? 0 }} accepted</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-sm text-slate-500">Top Payout Buyer</div>
      <div class="text-lg font-semibold">{{ $topPayoutBuyer?->endpoint ?? '—' }}</div>
      <div class="text-sm text-slate-500">${{ $topPayoutBuyer?->max_payout ?? 0 }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-sm text-slate-500">RPM (per 1000)</div>
      <div class="text-2xl font-semibold">${{ $rpm }}</div>
      <div class="text-sm text-slate-500">Revenue: ${{ $totalRevenue }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-sm text-slate-500">Duplicates</div>
      <div class="text-2xl font-semibold">{{ $duplicateCount }}</div>
      <div class="text-sm text-slate-500">{{ $duplicateRate }}% of attempts</div>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-6">
    <div class="text-sm text-slate-500">Rejected %</div>
    @php
      $rejectRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;
    @endphp
    <div class="text-2xl font-semibold">{{ $rejectRate }}%</div>
    <div class="mt-2 h-2 rounded-full bg-slate-100">
      <div class="h-2 rounded-full bg-red-500" style="width: {{ $rejectRate }}%"></div>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-4 mb-6">
    <h3 class="text-lg font-semibold mb-3">Buyer Stats</h3>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500">
          <th class="py-2">Buyer</th>
          <th class="py-2">Total</th>
          <th class="py-2">Accepted</th>
          <th class="py-2">Rejected</th>
          <th class="py-2">Max Payout</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($buyerStats as $row)
          <tr class="border-t border-slate-100">
            <td class="py-2">{{ $row->endpoint ?: 'Unknown' }}</td>
            <td class="py-2">{{ $row->total }}</td>
            <td class="py-2">{{ $row->accepted }}</td>
            <td class="py-2">{{ $row->rejected }}</td>
            <td class="py-2">${{ $row->max_payout ?? 0 }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-slate-500 py-3">No data for selected filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <h3 class="text-lg font-semibold mb-3">Products</h3>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="py-1">Product</th>
            <th class="py-1">Leads</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($productStats as $row)
            <tr class="border-t border-slate-100">
              <td class="py-1">{{ $row->product?->code ?? 'Unknown' }}</td>
              <td class="py-1">{{ $row->total }}</td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-slate-500 py-2">No data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <h3 class="text-lg font-semibold mb-3">Campaigns</h3>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="py-1">Campaign</th>
            <th class="py-1">Leads</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($campaignStats as $row)
            <tr class="border-t border-slate-100">
              <td class="py-1">{{ $row->campaign?->code ?? 'Unknown' }}</td>
              <td class="py-1">{{ $row->total }}</td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-slate-500 py-2">No data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <h3 class="text-lg font-semibold mb-3">Publishers</h3>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="py-1">Publisher</th>
            <th class="py-1">Leads</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($publisherStats as $row)
            <tr class="border-t border-slate-100">
              <td class="py-1">{{ $row->publisher?->code ?? 'Unknown' }}</td>
              <td class="py-1">{{ $row->total }}</td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-slate-500 py-2">No data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-4">
    <h3 class="text-lg font-semibold mb-3">Recent Attempts</h3>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500">
          <th class="py-2">Time</th>
          <th class="py-2">Buyer</th>
          <th class="py-2">Status</th>
          <th class="py-2">Lead</th>
          <th class="py-2">Forwarding</th>
          <th class="py-2">HTTP</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($attempts as $attempt)
          <tr class="border-t border-slate-100">
            <td class="py-2">{{ $attempt->created_at }}</td>
            <td class="py-2">{{ $attempt->endpoint }}</td>
            <td class="py-2">
              @php
                $badgeClass = $attempt->status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : ($attempt->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600');
              @endphp
              <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ ucfirst($attempt->status) }}</span>
            </td>
            <td class="py-2">
              {{ $attempt->lead->first_name ?? '' }} {{ $attempt->lead->last_name ?? '' }}
              <div class="text-xs text-slate-500">{{ $attempt->lead->phone ?? '' }}</div>
            </td>
            <td class="py-2">{{ $attempt->forwarding_number }}</td>
            <td class="py-2">{{ $attempt->http_status }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-slate-500 py-3">No attempts found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
