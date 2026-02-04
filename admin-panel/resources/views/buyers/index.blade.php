@extends('layouts.app')

@section('content')
  <div class="card shadow-sm">
    <h3>Add Buyer</h3>
    <form method="post" action="{{ route('buyers.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-3">
          <label for="code">Code (D#)</label>
          <input id="code" name="code" type="text" class="form-control" required />
        </div>
        <div class="col-md-4">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" class="form-control" required />
        </div>
        <div class="col-md-2">
          <label for="type">Type</label>
          <select id="type" name="type" class="form-select" required>
            <option value="single">Single</option>
            <option value="ping_post">Ping/Post</option>
            <option value="rtb">RTB</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="active">Active</label>
          <select id="active" name="active" class="form-select">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
        <div class="col-md-1" style="align-self: flex-end;">
          <button type="submit" class="btn btn-primary">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card shadow-sm">
    <h3>Buyers</h3>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Type</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($buyers as $buyer)
          <tr>
            <td>{{ $buyer->code }}</td>
            <td>{{ $buyer->name }}</td>
            <td>{{ $buyer->type }}</td>
            <td>{{ $buyer->active ? 'Active' : 'Inactive' }}</td>
            <td>
              <a class="btn btn-link btn-sm" href="{{ route('buyers.edit', $buyer) }}">Edit</a>
              <form method="post" action="{{ route('buyers.toggle', $buyer) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">{{ $buyer->active ? 'Disable' : 'Enable' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="muted">No buyers yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
