@extends('layouts.app')

@section('content')
  <div class="card">
    <h3>Add Buyer</h3>
    <form method="post" action="{{ route('buyers.store') }}">
      @csrf
      <div class="row">
        <div>
          <label for="code">Code (D#)</label>
          <input id="code" name="code" type="text" required />
        </div>
        <div>
          <label for="name">Name</label>
          <input id="name" name="name" type="text" required />
        </div>
        <div>
          <label for="type">Type</label>
          <select id="type" name="type" required>
            <option value="single">Single</option>
            <option value="ping_post">Ping/Post</option>
            <option value="rtb">RTB</option>
          </select>
        </div>
        <div>
          <label for="active">Active</label>
          <select id="active" name="active">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
        <div style="align-self: flex-end;">
          <button type="submit">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Buyers</h3>
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Type</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($buyers as $buyer)
          <tr>
            <td>{{ $buyer->code }}</td>
            <td>{{ $buyer->name }}</td>
            <td>{{ $buyer->type }}</td>
            <td>{{ $buyer->active ? 'Active' : 'Inactive' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="muted">No buyers yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
