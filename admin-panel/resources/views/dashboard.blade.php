@extends('layouts.app')

@section('content')
  <div class="card">
    <h3>Filters</h3>
    <form method="get" action="{{ route('dashboard') }}">
      <div class="row">
        <div>
          <label for="start_date">Start</label>
          <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] }}" />
        </div>
        <div>
          <label for="end_date">End</label>
          <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] }}" />
        </div>
        <div>
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">All</option>
            <option value="accepted" @selected($filters['status'] === 'accepted')>Accepted</option>
            <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
            <option value="unknown" @selected($filters['status'] === 'unknown')>Unknown</option>
          </select>
        </div>
        <div>
          <label for="buyer">Buyer</label>
          <select id="buyer" name="buyer">
            <option value="">All</option>
            @foreach ($buyers as $buyer)
              <option value="{{ $buyer->code }}" @selected($filters['buyer'] === $buyer->code)>{{ $buyer->code }} - {{ $buyer->name }}</option>
            @endforeach
          </select>
        </div>
        <div style="align-self: flex-end;">
          <button type="submit">Apply</button>
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

  <div class="card">
    <h3>Summary</h3>
    <div class="row">
      <div>✅ Accepted: <strong>{{ $accepted }}</strong></div>
      <div>❌ Rejected: <strong>{{ $rejected }}</strong></div>
      <div>⚪ Unknown: <strong>{{ $unknown }}</strong></div>
      <div>📦 Total: <strong>{{ $total }}</strong></div>
    </div>
  </div>

  <div class="card">
    <h3>Buyer Stats</h3>
    <table>
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

  <div class="card">
    <h3>Recent Attempts</h3>
    <table>
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
