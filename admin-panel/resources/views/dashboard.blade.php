@extends('layouts.app')

@section('content')
  <div class="card shadow-sm">
    <h3>Filters</h3>
    <form method="get" action="{{ route('dashboard') }}">
      <div class="row g-3">
        <div class="col-md-3">
          <label for="start_date">Start</label>
          <input id="start_date" name="start_date" type="date" class="form-control" value="{{ $filters['start_date'] }}" />
        </div>
        <div class="col-md-3">
          <label for="end_date">End</label>
          <input id="end_date" name="end_date" type="date" class="form-control" value="{{ $filters['end_date'] }}" />
        </div>
        <div class="col-md-3">
          <label for="status">Status</label>
          <select id="status" name="status" class="form-select">
            <option value="">All</option>
            <option value="accepted" @selected($filters['status'] === 'accepted')>Accepted</option>
            <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
            <option value="unknown" @selected($filters['status'] === 'unknown')>Unknown</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="buyer">Buyer</label>
          <select id="buyer" name="buyer" class="form-select">
            <option value="">All</option>
            @foreach ($buyers as $buyer)
              <option value="{{ $buyer->code }}" @selected($filters['buyer'] === $buyer->code)>{{ $buyer->code }} - {{ $buyer->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2" style="align-self: flex-end;">
          <button type="submit" class="btn btn-primary">Apply</button>
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

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-success-subtle">
        <div class="card-body">
          <div class="text-muted">Accepted</div>
          <h3 class="mb-0">{{ $accepted }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-danger-subtle">
        <div class="card-body">
          <div class="text-muted">Rejected</div>
          <h3 class="mb-0">{{ $rejected }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-light">
        <div class="card-body">
          <div class="text-muted">Unknown</div>
          <h3 class="mb-0">{{ $unknown }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 bg-primary-subtle">
        <div class="card-body">
          <div class="text-muted">Total</div>
          <h3 class="mb-0">{{ $total }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted">Acceptance Rate</div>
          <h3 class="mb-2">{{ $acceptRate }}%</h3>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $acceptRate }}%"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted">Top Buyer (Accepted)</div>
          <h5 class="mb-0">{{ $topBuyer?->endpoint ?? '—' }}</h5>
          <div class="text-muted">{{ $topBuyer?->accepted ?? 0 }} accepted</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-muted">Top Payout Buyer</div>
          <h5 class="mb-0">{{ $topPayoutBuyer?->endpoint ?? '—' }}</h5>
          <div class="text-muted">${{ $topPayoutBuyer?->max_payout ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="text-muted">Rejected %</div>
      @php
        $rejectRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;
      @endphp
      <h3 class="mb-2">{{ $rejectRate }}%</h3>
      <div class="progress" style="height: 8px;">
        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $rejectRate }}%"></div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <h3>Buyer Stats</h3>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Buyer</th>
          <th>Total</th>
          <th>Accepted</th>
          <th>Rejected</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($buyerStats as $row)
          <tr>
            <td>{{ $row->endpoint ?: 'Unknown' }}</td>
            <td>{{ $row->total }}</td>
            <td>{{ $row->accepted }}</td>
            <td>{{ $row->rejected }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="muted">No data for selected filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="card shadow-sm">
    <h3>Recent Attempts</h3>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Time</th>
          <th>Buyer</th>
          <th>Status</th>
          <th>Lead</th>
          <th>Forwarding</th>
          <th>HTTP</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($attempts as $attempt)
          <tr>
            <td>{{ $attempt->created_at }}</td>
            <td>{{ $attempt->endpoint }}</td>
            <td>
              @php
                $badgeClass = $attempt->status === 'accepted' ? 'ok' : ($attempt->status === 'rejected' ? 'bad' : 'unknown');
              @endphp
              <span class="badge {{ $badgeClass }}">{{ ucfirst($attempt->status) }}</span>
            </td>
            <td>
              {{ $attempt->lead->first_name ?? '' }} {{ $attempt->lead->last_name ?? '' }}
              <div class="muted">{{ $attempt->lead->phone ?? '' }}</div>
            </td>
            <td>{{ $attempt->forwarding_number }}</td>
            <td>{{ $attempt->http_status }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="muted">No attempts found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
