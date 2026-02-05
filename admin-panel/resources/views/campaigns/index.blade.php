@extends('layouts.app')

@section('content')
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Add Campaign</h3>
    <form method="post" action="{{ route('campaigns.store') }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label for="code" class="text-sm text-slate-600">Code</label>
          <input id="code" name="code" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <div class="md:col-span-2">
          <label for="name" class="text-sm text-slate-600">Name</label>
          <input id="name" name="name" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required />
        </div>
        <div class="md:col-span-2">
          <label for="product_id" class="text-sm text-slate-600">Product</label>
          <select id="product_id" name="product_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            @foreach ($products as $product)
              <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Campaigns</h3>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500">
          <th class="py-2">Code</th>
          <th class="py-2">Name</th>
          <th class="py-2">Product</th>
          <th class="py-2">Status</th>
          <th class="py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($campaigns as $campaign)
          <tr class="border-t border-slate-100">
            <td class="py-2">{{ $campaign->code }}</td>
            <td class="py-2">{{ $campaign->name }}</td>
            <td class="py-2">{{ $campaign->product?->code ?? '' }}</td>
            <td class="py-2">{{ $campaign->active ? 'Active' : 'Inactive' }}</td>
            <td class="py-2">
              <form method="post" action="{{ route('campaigns.toggle', $campaign) }}" class="inline">
                @csrf
                <button type="submit" class="border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100">{{ $campaign->active ? 'Disable' : 'Enable' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-slate-500 py-3">No campaigns yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
