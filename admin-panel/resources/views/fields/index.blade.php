@extends('layouts.app')

@section('content')
  <div class="card shadow-sm">
    <h3>Add Lead Field</h3>
    <form method="post" action="{{ route('fields.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-3">
          <label for="key">Key</label>
          <input id="key" name="key" type="text" class="form-control" required />
        </div>
        <div class="col-md-4">
          <label for="label">Label</label>
          <input id="label" name="label" type="text" class="form-control" required />
        </div>
        <div class="col-md-2">
          <label for="type">Type</label>
          <select id="type" name="type" class="form-select" required>
            <option value="text">Text</option>
            <option value="email">Email</option>
            <option value="tel">Phone</option>
            <option value="date">Date</option>
            <option value="select">Select</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="options">Options (JSON for select)</label>
          <input id="options" name="options" type="text" class="form-control" placeholder='{"Yes":"Yes","No":"No"}' />
        </div>
        <div class="col-md-1" style="align-self: flex-end;">
          <button type="submit" class="btn btn-primary">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card shadow-sm">
    <h3>Lead Fields</h3>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Key</th>
          <th>Label</th>
          <th>Type</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($fields as $field)
          <tr>
            <td>{{ $field->key }}</td>
            <td>{{ $field->label }}</td>
            <td>{{ $field->type }}</td>
            <td>{{ $field->active ? 'Active' : 'Inactive' }}</td>
            <td>
              <form method="post" action="{{ route('fields.toggle', $field) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">{{ $field->active ? 'Disable' : 'Enable' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="muted">No fields yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
