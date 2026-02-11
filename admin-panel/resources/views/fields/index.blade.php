@extends('layouts.app')

@section('content')
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Add Lead Field</h3>
    <form method="post" action="{{ route('fields.store') }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label for="key" class="text-sm text-slate-600">Key</label>
          <input id="key" name="key" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <div class="md:col-span-2">
          <label for="label" class="text-sm text-slate-600">Label</label>
          <input id="label" name="label" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <div>
          <label for="type" class="text-sm text-slate-600">Type</label>
          <select id="type" name="type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="text">Text</option>
            <option value="email">Email</option>
            <option value="tel">Phone</option>
            <option value="date">Date</option>
            <option value="select">Select</option>
          </select>
        </div>
        <div>
          <label for="options" class="text-sm text-slate-600">Options (JSON)</label>
          <input id="options" name="options" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder='{"Yes":"Yes","No":"No"}' />
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Lead Fields</h3>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500">
          <th class="py-2">Key</th>
          <th class="py-2">Label</th>
          <th class="py-2">Type</th>
          <th class="py-2">Status</th>
          <th class="py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($fields as $field)
          <tr class="border-t border-slate-100">
            <td class="py-2">{{ $field->key }}</td>
            <td class="py-2">{{ $field->label }}</td>
            <td class="py-2">{{ $field->type }}</td>
            <td class="py-2">{{ $field->active ? 'Active' : 'Inactive' }}</td>
            <td class="py-2">
              <form method="post" action="{{ route('fields.toggle', $field) }}" class="inline">
                @csrf
                <button type="submit" class="border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100">{{ $field->active ? 'Disable' : 'Enable' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-slate-500 py-3">No fields yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
