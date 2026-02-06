@extends('layouts.app')

@section('content')
  @php
    $fullWidth = true;
  @endphp

  <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-white">
    <div class="px-6 lg:px-10 py-10">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
        <div>
          <div class="text-xs uppercase tracking-[0.35em] text-slate-300">Dashboard</div>
          <h1 class="text-3xl lg:text-4xl font-semibold mt-2">Lead Performance Center</h1>
          <p class="text-sm text-slate-300 mt-2">Unified + RTB performance, payouts, and quality signals.</p>
          @if ($filters['scope'])
            <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200">
              <span class="uppercase tracking-wide">Scope</span>
              <span class="font-semibold">{{ strtoupper($filters['scope']) }}</span>
            </div>
          @endif
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div class="rounded-xl bg-white/10 border border-white/10 px-4 py-3">
            <div class="text-xs text-slate-300">Total Leads</div>
            <div class="text-lg font-semibold">{{ $total }}</div>
          </div>
          <div class="rounded-xl bg-white/10 border border-white/10 px-4 py-3">
            <div class="text-xs text-slate-300">Accepted</div>
            <div class="text-lg font-semibold">{{ $accepted }}</div>
          </div>
          <div class="rounded-xl bg-white/10 border border-white/10 px-4 py-3">
            <div class="text-xs text-slate-300">Rejected</div>
            <div class="text-lg font-semibold">{{ $rejected }}</div>
          </div>
          <div class="rounded-xl bg-white/10 border border-white/10 px-4 py-3">
            <div class="text-xs text-slate-300">Revenue</div>
            <div class="text-lg font-semibold">${{ number_format($totalRevenue, 2) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="px-6 lg:px-10 -mt-6 space-y-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div>
        <div class="text-xs uppercase tracking-wide text-slate-400">Reporting</div>
        <h3 class="text-lg font-semibold">Filters</h3>
      </div>
      @php
        $filterQuery = array_filter([
          "start_date" => $filters["start_date"],
          "end_date" => $filters["end_date"],
          "status" => $filters["status"],
          "buyer" => $filters["buyer"],
          "scope" => $filters["scope"],
        ], fn ($value) => $value !== null && $value !== "");
      @endphp
      <div class="flex items-center gap-3 text-sm">
        <a class="text-slate-500 hover:text-slate-900" href="{{ route('dashboard') }}">Reset</a>
        <a class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-700 hover:bg-slate-50" href="{{ route('leads.index', $filterQuery) }}">Open Leads</a>
      </div>
    </div>
    <form method="get" action="{{ route('dashboard') }}">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
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
          <label for="scope" class="text-sm text-slate-600">Scope</label>
          <select id="scope" name="scope" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All</option>
            @foreach ($scopeOptions as $option)
              <option value="{{ $option }}" @selected($filters['scope'] === $option)>{{ strtoupper($option) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="buyer" class="text-sm text-slate-600">Buyer</label>
          <select id="buyer" name="buyer" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All</option>
            @foreach ($buyers as $buyer)
              <option value="{{ $buyer->code }}" @selected($filters['buyer'] === $buyer->code)>{{ $buyer->code }} - {{ $buyer->name }} ({{ strtoupper($buyer->scope) }})</option>
            @endforeach
          </select>
        </div>
        <div>
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Apply</button>
        </div>
      </div>
    </form>
  </div>

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

  <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6">
    <div class="space-y-6">
      <div>
        <div class="text-sm text-slate-500 uppercase tracking-wide mb-3">Waterflow Metrics</div>
        <div class="flex flex-wrap items-end gap-6">
          <div class="w-40 h-40 rounded-full bg-white border border-slate-200 shadow-sm flex flex-col items-center justify-center">
            <div class="text-xs text-slate-500">Total Leads</div>
            <div class="text-2xl font-semibold">{{ $total }}</div>
          </div>
          <div class="w-44 h-44 rounded-full bg-emerald-50 border border-emerald-100 shadow-sm flex flex-col items-center justify-center">
            <div class="text-xs text-emerald-600">Acceptance</div>
            <div class="text-2xl font-semibold">{{ $acceptRate }}%</div>
          </div>
          <div class="w-36 h-36 rounded-full bg-red-50 border border-red-100 shadow-sm flex flex-col items-center justify-center">
            <div class="text-xs text-red-600">Rejected</div>
            <div class="text-xl font-semibold">{{ $rejected }}</div>
          </div>
          <div class="w-44 h-44 rounded-full bg-blue-50 border border-blue-100 shadow-sm flex flex-col items-center justify-center">
            <div class="text-xs text-blue-600">Revenue</div>
            <div class="text-2xl font-semibold">${{ number_format($totalRevenue, 2) }}</div>
          </div>
          <div class="w-36 h-36 rounded-full bg-amber-50 border border-amber-100 shadow-sm flex flex-col items-center justify-center text-center px-2">
            <div class="text-xs text-amber-600">Duplicate Leads</div>
            <div class="text-xl font-semibold">{{ $duplicateCount }}</div>
            <div class="text-[10px] text-amber-600">Buyer dupes: {{ $duplicateAttemptCount }}</div>
          </div>
        </div>
      </div>

      <div>
        <div class="text-sm text-slate-500 uppercase tracking-wide mb-3">Highlights</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Top Buyer (Accepted)</div>
            <div class="text-xl font-semibold mt-1">{{ $topBuyer?->endpoint ?? '—' }}</div>
            <div class="text-sm text-slate-500">{{ $topBuyer?->accepted ?? 0 }} accepted</div>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-slate-500">Top Payout Buyer</div>
            <div class="text-xl font-semibold mt-1">{{ $topPayoutBuyer?->endpoint ?? '—' }}</div>
            <div class="text-sm text-slate-500">${{ number_format($topPayoutBuyer?->max_payout ?? 0, 2) }}</div>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="text-sm text-slate-500">Rejected %</div>
        <div class="text-2xl font-semibold">{{ $rejectRate }}%</div>
        <div class="mt-2 h-2 rounded-full bg-slate-100">
          <div class="h-2 rounded-full bg-red-500" style="width: {{ $rejectRate }}%"></div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-xs uppercase tracking-wide text-slate-400">Buyer Performance</div>
            <h3 class="text-lg font-semibold">Buyer Stats</h3>
          </div>
          <div class="text-xs text-slate-500">{{ $buyerStats->count() }} buyers</div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          @forelse ($buyerStats as $row)
            @php
              $rowTotal = (int) $row->total;
              $rowAccepted = (int) $row->accepted;
              $rowRejected = (int) $row->rejected;
              $rowRate = $rowTotal > 0 ? round(($rowAccepted / $rowTotal) * 100, 1) : 0;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="text-xs text-slate-400 uppercase tracking-wide">Buyer</div>
                  <div class="text-lg font-semibold">{{ $row->endpoint ?: 'Unknown' }}</div>
                </div>
                <div class="text-xs text-slate-500">{{ $rowTotal }} total</div>
              </div>
              <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-emerald-100 text-emerald-700 px-2 py-1">{{ $rowAccepted }} accepted</span>
                <span class="rounded-full bg-red-100 text-red-700 px-2 py-1">{{ $rowRejected }} rejected</span>
              </div>
              <div class="mt-3">
                <div class="flex justify-between text-xs text-slate-500">
                  <span>Acceptance</span>
                  <span>{{ $rowRate }}%</span>
                </div>
                <div class="mt-1 h-2 rounded-full bg-white">
                  <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $rowRate }}%"></div>
                </div>
              </div>
              <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-lg bg-white p-2">
                  <div class="text-slate-400">Total Payout</div>
                  <div class="font-semibold">${{ number_format($row->total_payout ?? 0, 2) }}</div>
                </div>
                <div class="rounded-lg bg-white p-2">
                  <div class="text-slate-400">Max Payout</div>
                  <div class="font-semibold">${{ number_format($row->max_payout ?? 0, 2) }}</div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-slate-500">No data for selected filters.</div>
          @endforelse
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
    </div>

    <aside class="space-y-6">
      <details class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" open>
        <summary class="cursor-pointer list-none">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-lg font-semibold">Recent Attempts</h3>
              <div class="text-xs text-slate-500">Last {{ $attempts->count() }} attempts</div>
            </div>
            <span class="text-xs text-slate-400">Click to collapse</span>
          </div>
        </summary>
        <div class="mt-4 space-y-4">
          <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="rounded-full bg-slate-100 px-2 py-1">Scope: {{ $filters['scope'] ? strtoupper($filters['scope']) : 'ALL' }}</span>
            <span class="rounded-full bg-slate-100 px-2 py-1">Status: {{ $filters['status'] ? strtoupper($filters['status']) : 'ALL' }}</span>
            <span class="rounded-full bg-slate-100 px-2 py-1">Buyer: {{ $filters['buyer'] ?: 'ALL' }}</span>
          </div>
          <div class="divide-y divide-slate-100 text-sm">
            @forelse ($attempts as $attempt)
              @php
                $badgeClass = $attempt->status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : ($attempt->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600');
              @endphp
              <div class="py-3">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold text-slate-800">{{ $attempt->endpoint }}</div>
                    <div class="text-xs text-slate-500">{{ $attempt->created_at }}</div>
                  </div>
                  <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ ucfirst($attempt->status) }}</span>
                </div>
                <div class="mt-2 text-xs text-slate-500">
                  {{ $attempt->lead->first_name ?? '' }} {{ $attempt->lead->last_name ?? '' }}
                  <span class="text-slate-400">•</span> {{ $attempt->lead->phone ?? '' }}
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                  <span class="rounded-full bg-slate-100 px-2 py-0.5">FWD: {{ $attempt->forwarding_number ?: '—' }}</span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5">HTTP: {{ $attempt->http_status ?? '—' }}</span>
                </div>
              </div>
            @empty
              <div class="py-3 text-slate-500">No attempts found.</div>
            @endforelse
          </div>
        </div>
      </details>
    </aside>
  </div>
  </div>
@endsection
