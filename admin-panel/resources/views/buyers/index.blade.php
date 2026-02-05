@extends('layouts.app')

@section('content')
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Add Buyer</h3>
    <form method="post" action="{{ route('buyers.store') }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
          <label for="code" class="text-sm text-slate-600">Code (D#)</label>
          <input id="code" name="code" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <div class="md:col-span-2">
          <label for="name" class="text-sm text-slate-600">Name</label>
          <input id="name" name="name" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <div>
          <label for="type" class="text-sm text-slate-600">Type</label>
          <select id="type" name="type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="single">Single</option>
            <option value="ping_post">Ping/Post</option>
            <option value="rtb">RTB</option>
          </select>
        </div>
        <div>
          <label for="scope" class="text-sm text-slate-600">Scope</label>
          <select id="scope" name="scope" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="single">Single</option>
            <option value="unified">Unified</option>
            <option value="rtb">RTB</option>
            <option value="all">All</option>
          </select>
        </div>
        <div>
          <label for="active" class="text-sm text-slate-600">Active</label>
          <select id="active" name="active" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Add</button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mt-4">
        <div>
          <label for="payload_format" class="text-sm text-slate-600">Payload</label>
          <select id="payload_format" name="payload_format" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="form">Form</option>
            <option value="json">JSON</option>
            <option value="xml">XML</option>
          </select>
        </div>
        <div>
          <label for="platform" class="text-sm text-slate-600">Platform</label>
          <select id="platform" name="platform" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="custom">Custom</option>
            <option value="trackdrive">TrackDrive</option>
            <option value="ringba">Ringba</option>
            <option value="leadspedia">Leadspedia</option>
            <option value="retreaver">Retreaver</option>
            <option value="leadconduit">LeadConduit</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label for="ping_url" class="text-sm text-slate-600">Ping URL</label>
          <input id="ping_url" name="ping_url" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div class="md:col-span-2">
          <label for="post_url" class="text-sm text-slate-600">Post URL</label>
          <input id="post_url" name="post_url" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label for="public_enabled" class="text-sm text-slate-600">Public</label>
          <select id="public_enabled" name="public_enabled" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Buyers</h3>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500">
          <th class="py-2">Code</th>
          <th class="py-2">Name</th>
          <th class="py-2">Type</th>
          <th class="py-2">Scope</th>
          <th class="py-2">Status</th>
          <th class="py-2">Public Link</th>
          <th class="py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($buyers as $buyer)
          <tr class="border-t border-slate-100">
            <td class="py-2">{{ $buyer->code }}</td>
            <td class="py-2">{{ $buyer->name }}</td>
            <td class="py-2">{{ $buyer->type }}</td>
            <td class="py-2">{{ $buyer->scope }}</td>
            <td class="py-2">{{ $buyer->active ? 'Active' : 'Inactive' }}</td>
            <td class="py-2">
              @if ($buyer->public_token && $buyer->public_enabled)
                <a class="text-blue-600 hover:text-blue-800 text-xs" href="{{ url('/f/' . $buyer->public_token) }}" target="_blank">Open</a>
              @else
                <span class="text-slate-400 text-xs">—</span>
              @endif
            </td>
            <td class="py-2">
              <a class="text-blue-600 hover:text-blue-800 mr-3" href="{{ route('buyers.edit', $buyer) }}">Edit</a>
              <form method="post" action="{{ route('buyers.toggle', $buyer) }}" class="inline">
                @csrf
                <button type="submit" class="border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100">{{ $buyer->active ? 'Disable' : 'Enable' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-slate-500 py-3">No buyers yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
