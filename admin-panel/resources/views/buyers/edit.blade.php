@extends('layouts.app')

@section('content')
  <div class="card shadow-sm">
    <h3>Edit Buyer: {{ $buyer->code }}</h3>
    <form method="post" action="{{ route('buyers.update', $buyer) }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" class="form-control" value="{{ $buyer->name }}" required />
        </div>
        <div class="col-md-3">
          <label for="type">Type</label>
          <select id="type" name="type" class="form-select" required>
            <option value="single" @selected($buyer->type === 'single')>Single</option>
            <option value="ping_post" @selected($buyer->type === 'ping_post')>Ping/Post</option>
            <option value="rtb" @selected($buyer->type === 'rtb')>RTB</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="active">Active</label>
          <select id="active" name="active" class="form-select">
            <option value="1" @selected($buyer->active)>Yes</option>
            <option value="0" @selected(!$buyer->active)>No</option>
          </select>
        </div>
        <div class="col-md-2" style="align-self: flex-end;">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card shadow-sm">
    <h3>Add Field</h3>
    <form method="post" action="{{ route('buyers.fields.store', $buyer) }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label for="field_name">Field</label>
          <select id="field_name" name="field_name" class="form-select" required>
            @foreach ($leadFields as $key => $meta)
              <option value="{{ $key }}">{{ $key }} — {{ $meta['label'] ?? $key }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label for="direction">Direction</label>
          <select id="direction" name="direction" class="form-select" required>
            <option value="single">Single</option>
            <option value="ping">Ping</option>
            <option value="post">Post</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="required">Required</label>
          <select id="required" name="required" class="form-select">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
        <div class="col-md-2" style="align-self: flex-end;">
          <button type="submit" class="btn btn-primary">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card shadow-sm">
    <h3>Fields</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Field</th>
          <th>Direction</th>
          <th>Required</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($fields as $field)
          <tr>
            <td>{{ $field->field_name }}</td>
            <td>{{ $field->direction }}</td>
            <td>{{ $field->required ? 'Yes' : 'No' }}</td>
            <td>
              <form method="post" action="{{ route('buyers.fields.update', $field) }}" style="display:inline;">
                @csrf
                <input type="hidden" name="required" value="{{ $field->required ? 0 : 1 }}" />
                <button type="submit" class="btn btn-outline-secondary btn-sm">{{ $field->required ? 'Make Optional' : 'Make Required' }}</button>
              </form>
              <form method="post" action="{{ route('buyers.fields.delete', $field) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="muted">No fields yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
