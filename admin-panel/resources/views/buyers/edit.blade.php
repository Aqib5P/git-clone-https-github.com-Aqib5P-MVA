@extends('layouts.app')

@section('content')
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Edit Buyer: {{ $buyer->code }}</h3>
    <form method="post" action="{{ route('buyers.update', $buyer) }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="md:col-span-2">
          <label for="name" class="text-sm text-slate-600">Name</label>
          <input id="name" name="name" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $buyer->name }}" required />
        </div>
        <div>
          <label for="type" class="text-sm text-slate-600">Type</label>
          <select id="type" name="type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="single" @selected($buyer->type === 'single')>Single</option>
            <option value="ping_post" @selected($buyer->type === 'ping_post')>Ping/Post</option>
            <option value="rtb" @selected($buyer->type === 'rtb')>RTB</option>
          </select>
        </div>
        <div>
          <label for="scope" class="text-sm text-slate-600">Scope</label>
          <select id="scope" name="scope" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="single" @selected($buyer->scope === 'single')>Single</option>
            <option value="unified" @selected($buyer->scope === 'unified')>Unified</option>
            <option value="rtb" @selected($buyer->scope === 'rtb')>RTB</option>
            <option value="all" @selected($buyer->scope === 'all')>All</option>
          </select>
        </div>
        <div>
          <label for="payload_format" class="text-sm text-slate-600">Payload</label>
          <select id="payload_format" name="payload_format" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="form" @selected($buyer->payload_format === 'form')>Form</option>
            <option value="json" @selected($buyer->payload_format === 'json')>JSON</option>
            <option value="xml" @selected($buyer->payload_format === 'xml')>XML</option>
          </select>
        </div>
        <div>
          <label for="active" class="text-sm text-slate-600">Active</label>
          <select id="active" name="active" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="1" @selected($buyer->active)>Yes</option>
            <option value="0" @selected(!$buyer->active)>No</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mt-4">
        <div class="md:col-span-3">
          <label for="ping_url" class="text-sm text-slate-600">Ping URL</label>
          <input id="ping_url" name="ping_url" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $buyer->ping_url }}" />
        </div>
        <div class="md:col-span-3">
          <label for="post_url" class="text-sm text-slate-600">Post URL</label>
          <input id="post_url" name="post_url" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $buyer->post_url }}" />
        </div>
        <div>
          <label for="platform" class="text-sm text-slate-600">Platform</label>
          <select id="platform" name="platform" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="custom" @selected($buyer->platform === 'custom')>Custom</option>
            <option value="trackdrive" @selected($buyer->platform === 'trackdrive')>TrackDrive</option>
            <option value="ringba" @selected($buyer->platform === 'ringba')>Ringba</option>
            <option value="leadspedia" @selected($buyer->platform === 'leadspedia')>Leadspedia</option>
            <option value="retreaver" @selected($buyer->platform === 'retreaver')>Retreaver</option>
            <option value="leadconduit" @selected($buyer->platform === 'leadconduit')>LeadConduit</option>
          </select>
        </div>
        <div class="md:col-span-3">
          <label for="headers_json" class="text-sm text-slate-600">Headers (JSON)</label>
          <input id="headers_json" name="headers_json" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $buyer->headers_json ? json_encode($buyer->headers_json) : '' }}" />
        </div>
        <div>
          <label for="public_enabled" class="text-sm text-slate-600">Public Link</label>
          <select id="public_enabled" name="public_enabled" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="1" @selected($buyer->public_enabled)>Enabled</option>
            <option value="0" @selected(!$buyer->public_enabled)>Disabled</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Save</button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
          <label for="default_product_id" class="text-sm text-slate-600">Product</label>
          <select id="default_product_id" name="default_product_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">None</option>
            @foreach ($products as $product)
              <option value="{{ $product->id }}" @selected($buyer->default_product_id === $product->id)>{{ $product->code }} — {{ $product->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="default_campaign_id" class="text-sm text-slate-600">Campaign</label>
          <select id="default_campaign_id" name="default_campaign_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">None</option>
            @foreach ($campaigns as $campaign)
              <option value="{{ $campaign->id }}" @selected($buyer->default_campaign_id === $campaign->id)>{{ $campaign->code }} — {{ $campaign->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="default_publisher_id" class="text-sm text-slate-600">Publisher</label>
          <select id="default_publisher_id" name="default_publisher_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">None</option>
            @foreach ($publishers as $publisher)
              <option value="{{ $publisher->id }}" @selected($buyer->default_publisher_id === $publisher->id)>{{ $publisher->code }} — {{ $publisher->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
        <div>
          <label for="payout_type" class="text-sm text-slate-600">Payout Type</label>
          <select id="payout_type" name="payout_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="dynamic" @selected($buyer->payout_type === 'dynamic')>Dynamic</option>
            <option value="static" @selected($buyer->payout_type === 'static')>Static</option>
          </select>
        </div>
        <div>
          <label for="static_payout" class="text-sm text-slate-600">Static Payout</label>
          <input id="static_payout" name="static_payout" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $buyer->static_payout }}" />
        </div>
        <div>
          <label for="payout_model" class="text-sm text-slate-600">Payout Model</label>
          <select id="payout_model" name="payout_model" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="CPL" @selected($buyer->payout_model === 'CPL')>CPL</option>
            <option value="CPA" @selected($buyer->payout_model === 'CPA')>CPA</option>
            <option value="CPQ" @selected($buyer->payout_model === 'CPQ')>CPQ</option>
          </select>
        </div>
        <div>
          <label for="payment_terms" class="text-sm text-slate-600">Payment Terms</label>
          <input id="payment_terms" name="payment_terms" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ $buyer->payment_terms }}" />
        </div>
      </div>

      @php
        $rules = $buyer->response_rules ?? [];
      @endphp
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
          <label for="response_accept" class="text-sm text-slate-600">Accept keywords (comma)</label>
          <input id="response_accept" name="response_accept" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $rules['accept'] ?? []) }}" />
        </div>
        <div>
          <label for="response_reject" class="text-sm text-slate-600">Reject keywords (comma)</label>
          <input id="response_reject" name="response_reject" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $rules['reject'] ?? []) }}" />
        </div>
        <div>
          <label for="forwarding_keys" class="text-sm text-slate-600">Forwarding keys (comma)</label>
          <input id="forwarding_keys" name="forwarding_keys" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $rules['forwarding_keys'] ?? []) }}" />
        </div>
        <div>
          <label for="payout_keys" class="text-sm text-slate-600">Payout keys (comma)</label>
          <input id="payout_keys" name="payout_keys" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $rules['payout_keys'] ?? []) }}" />
        </div>
        <div>
          <label for="bid_keys" class="text-sm text-slate-600">Bid keys (comma)</label>
          <input id="bid_keys" name="bid_keys" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $rules['bid_keys'] ?? []) }}" />
        </div>
      </div>
    </form>

    @if ($buyer->public_token)
      <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="text-sm text-slate-600">Public Form</div>
        <a class="text-blue-600 hover:text-blue-800" href="{{ url('/f/' . $buyer->public_token) }}" target="_blank">{{ url('/f/' . $buyer->public_token) }}</a>
        <form method="post" action="{{ route('buyers.token', $buyer) }}" class="inline">
          @csrf
          <button type="submit" class="ml-3 border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100">Regenerate</button>
        </form>
      </div>
    @endif
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold mb-4">Add Field</h3>
    <form method="post" action="{{ route('buyers.template', $buyer) }}" class="mb-4">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-2">
          <label for="template_key" class="text-sm text-slate-600">Apply Template</label>
          <select id="template_key" name="template_key" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            @foreach (config('buyer_templates') as $key => $template)
              <option value="{{ $key }}">{{ $template['label'] ?? $key }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="replace" class="text-sm text-slate-600">Replace Existing</label>
          <select id="replace" name="replace" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-100">Apply</button>
        </div>
      </div>
    </form>
    <form method="post" action="{{ route('buyers.fields.store', $buyer) }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="md:col-span-2">
          <label for="field_name" class="text-sm text-slate-600">Buyer Field Name</label>
          <input id="field_name" name="field_name" list="lead_field_names" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. first_name" required />
          <datalist id="lead_field_names">
            @foreach ($leadFields as $field)
              <option value="{{ $field->key }}"></option>
            @endforeach
          </datalist>
        </div>
        <div>
          <label for="direction" class="text-sm text-slate-600">Direction</label>
          <select id="direction" name="direction" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
            <option value="single">Single</option>
            <option value="ping">Ping</option>
            <option value="post">Post</option>
          </select>
        </div>
        <div>
          <label for="source_type" class="text-sm text-slate-600">Source</label>
          <select id="source_type" name="source_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="lead">Lead Field</option>
            <option value="static">Static</option>
            <option value="computed">Computed</option>
          </select>
        </div>
        <div>
          <label for="source_key" class="text-sm text-slate-600">Source Field</label>
          <select id="source_key" name="source_key" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">Auto</option>
            @foreach ($leadFields as $field)
              <option value="{{ $field->key }}">{{ $field->key }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="source_value" class="text-sm text-slate-600">Static Value</label>
          <input id="source_value" name="source_value" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
        </div>
        <div>
          <label for="required" class="text-sm text-slate-600">Req</label>
          <select id="required" name="required" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 hover:bg-blue-700">Add</button>
        </div>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Fields</h3>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500">
          <th class="py-2">Field</th>
          <th class="py-2">Direction</th>
          <th class="py-2">Source</th>
          <th class="py-2">Required</th>
          <th class="py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($fields as $field)
          <tr class="border-t border-slate-100">
            <td class="py-2">{{ $field->field_name }}</td>
            <td class="py-2">{{ $field->direction }}</td>
            <td class="py-2">{{ $field->source_type }} {{ $field->source_key ? '(' . $field->source_key . ')' : '' }}</td>
            <td class="py-2">{{ $field->required ? 'Yes' : 'No' }}</td>
            <td class="py-2">
              <form method="post" action="{{ route('buyers.fields.update', $field) }}" class="inline">
                @csrf
                <input type="hidden" name="required" value="{{ $field->required ? 0 : 1 }}" />
                <button type="submit" class="border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100">{{ $field->required ? 'Make Optional' : 'Make Required' }}</button>
              </form>
              <form method="post" action="{{ route('buyers.fields.delete', $field) }}" class="inline">
                @csrf
                <button type="submit" class="border border-red-200 text-red-600 px-2 py-1 rounded-lg text-xs hover:bg-red-50">Delete</button>
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

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mt-6">
    <h3 class="text-lg font-semibold mb-4">Test Buyer</h3>
    <form method="post" action="{{ route('buyers.test', $buyer) }}" class="space-y-4">
      @csrf
      <div>
        <label for="lead_json" class="text-sm text-slate-600">Lead JSON</label>
        <textarea id="lead_json" name="lead_json" rows="6" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs">{ "first_name": "John", "last_name": "Doe", "phone": "5551234567", "zip5": "90210" }</textarea>
      </div>
      <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">Send Test</button>
    </form>

    @if (session('test_result'))
      <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="text-sm font-semibold mb-2">Parsed Result</div>
        <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result')['parsed'], JSON_PRETTY_PRINT) }}</pre>
      </div>
      <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="text-sm font-semibold mb-2">Raw Response</div>
        <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result')['raw'], JSON_PRETTY_PRINT) }}</pre>
      </div>
    @endif
  </div>
@endsection
