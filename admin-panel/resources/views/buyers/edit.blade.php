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
          <label for="scope">Scope</label>
          <select id="scope" name="scope" class="form-select" required>
            <option value="single" @selected($buyer->scope === 'single')>Single</option>
            <option value="unified" @selected($buyer->scope === 'unified')>Unified</option>
            <option value="rtb" @selected($buyer->scope === 'rtb')>RTB</option>
            <option value="all" @selected($buyer->scope === 'all')>All</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="payload_format">Payload</label>
          <select id="payload_format" name="payload_format" class="form-select" required>
            <option value="form" @selected($buyer->payload_format === 'form')>Form</option>
            <option value="json" @selected($buyer->payload_format === 'json')>JSON</option>
          </select>
        </div>
        <div class="col-md-1">
          <label for="active">Active</label>
          <select id="active" name="active" class="form-select">
            <option value="1" @selected($buyer->active)>Yes</option>
            <option value="0" @selected(!$buyer->active)>No</option>
          </select>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <label for="ping_url">Ping URL</label>
          <input id="ping_url" name="ping_url" type="text" class="form-control" value="{{ $buyer->ping_url }}" />
        </div>
        <div class="col-md-6">
          <label for="post_url">Post URL</label>
          <input id="post_url" name="post_url" type="text" class="form-control" value="{{ $buyer->post_url }}" />
        </div>
        <div class="col-md-8">
          <label for="headers_json">Headers (JSON)</label>
          <input id="headers_json" name="headers_json" type="text" class="form-control" value="{{ $buyer->headers_json ? json_encode($buyer->headers_json) : '' }}" />
        </div>
        <div class="col-md-2">
          <label for="public_enabled">Public Link</label>
          <select id="public_enabled" name="public_enabled" class="form-select">
            <option value="1" @selected($buyer->public_enabled)>Enabled</option>
            <option value="0" @selected(!$buyer->public_enabled)>Disabled</option>
          </select>
        </div>
        <div class="col-md-2" style="align-self: flex-end;">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>

    @if ($buyer->public_token)
      <div class="mt-3">
        <strong>Public Form:</strong>
        <a href="{{ url('/f/' . $buyer->public_token) }}" target="_blank">{{ url('/f/' . $buyer->public_token) }}</a>
        <form method="post" action="{{ route('buyers.token', $buyer) }}" style="display:inline;">
          @csrf
          <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate</button>
        </form>
      </div>
    @endif
  </div>

  <div class="card shadow-sm">
    <h3>Add Field</h3>
    <form method="post" action="{{ route('buyers.fields.store', $buyer) }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label for="field_name">Field</label>
          <select id="field_name" name="field_name" class="form-select" required>
            @foreach ($leadFields as $field)
              <option value="{{ $field->key }}">{{ $field->key }} — {{ $field->label }}</option>
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
          <label for="source_type">Source</label>
          <select id="source_type" name="source_type" class="form-select">
            <option value="lead">Lead Field</option>
            <option value="static">Static</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="source_key">Lead Key</label>
          <select id="source_key" name="source_key" class="form-select">
            <option value="">Auto</option>
            @foreach ($leadFields as $field)
              <option value="{{ $field->key }}">{{ $field->key }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label for="source_value">Static Value</label>
          <input id="source_value" name="source_value" type="text" class="form-control" />
        </div>
        <div class="col-md-1">
          <label for="required">Req</label>
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
          <th>Source</th>
          <th>Required</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($fields as $field)
          <tr>
            <td>{{ $field->field_name }}</td>
            <td>{{ $field->direction }}</td>
            <td>{{ $field->source_type }} {{ $field->source_key ? '(' . $field->source_key . ')' : '' }}</td>
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
            <td colspan="5" class="muted">No fields yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
