@extends('layouts.app')

@section('content')
  @php
    $isPingPost = $buyer->type === 'ping_post';
  @endphp
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
            <option value="single" @selected(in_array($buyer->type, ['single','rtb']))>Full Post</option>
            <option value="ping_post" @selected($buyer->type === 'ping_post')>Ping/Post</option>
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
        $singleRules = $rules['single'] ?? $rules;
        $pingRules = $rules['ping'] ?? $rules;
        $postRules = $rules['post'] ?? $rules;
      @endphp
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
    <div class="grid grid-cols-1 {{ $isPingPost ? 'md:grid-cols-2' : '' }} gap-6">
      <form method="post" action="{{ route('buyers.fields.store', $buyer) }}" class="space-y-3">
        @csrf
        <input type="hidden" name="direction" value="{{ $isPingPost ? 'ping' : 'single' }}" />
        <div class="text-sm font-semibold text-slate-700">{{ $isPingPost ? 'Ping Fields' : 'Full Post Fields' }}</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-slate-600">Buyer Field</label>
            <input name="field_name" list="lead_field_names" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. first_name" required />
          </div>
          <div>
            <label class="text-xs text-slate-600">Source Type</label>
            <select name="source_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="lead">Lead Field</option>
              <option value="static">Static</option>
              <option value="computed">Computed</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-slate-600">Our Field</label>
            <select name="source_key" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="">Auto</option>
              @foreach ($leadFields as $field)
                <option value="{{ $field->key }}">{{ $field->key }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="text-xs text-slate-600">Static Value</label>
            <input name="source_value" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="text-xs text-slate-600">Required</label>
            <select name="required" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 text-sm hover:bg-blue-700">Add</button>
          </div>
        </div>
      </form>

      @if ($isPingPost)
        <form method="post" action="{{ route('buyers.fields.store', $buyer) }}" class="space-y-3">
          @csrf
          <input type="hidden" name="direction" value="post" />
          <div class="text-sm font-semibold text-slate-700">Post Fields</div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-slate-600">Buyer Field</label>
              <input name="field_name" list="lead_field_names" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. first_name" required />
            </div>
            <div>
              <label class="text-xs text-slate-600">Source Type</label>
              <select name="source_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="lead">Lead Field</option>
                <option value="static">Static</option>
                <option value="computed">Computed</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-slate-600">Our Field</label>
              <select name="source_key" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Auto</option>
                @foreach ($leadFields as $field)
                  <option value="{{ $field->key }}">{{ $field->key }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="text-xs text-slate-600">Static Value</label>
              <input name="source_value" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="text-xs text-slate-600">Required</label>
              <select name="required" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>
            <div class="flex items-end">
              <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2 text-sm hover:bg-blue-700">Add</button>
            </div>
          </div>
        </form>
      @endif
    </div>
    <datalist id="lead_field_names">
      @foreach ($leadFields as $field)
        <option value="{{ $field->key }}"></option>
      @endforeach
    </datalist>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Fields</h3>
    @php
      $pingFields = $fields->where('direction', 'ping');
      $postFields = $fields->where('direction', 'post');
      $singleFields = $fields->where('direction', 'single');
    @endphp

    @if ($isPingPost)
      <div class="space-y-6">
        <div>
          <div class="text-sm font-semibold text-slate-700 mb-2">Ping Mapping</div>
          @include('buyers.partials.field-table', ['fieldRows' => $pingFields])
          <div class="mt-4 rounded-xl border border-slate-200 p-4">
            <div class="text-sm font-semibold text-slate-700 mb-3">Ping Response Mapping</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="text-xs text-slate-600">Accept keywords</label>
                <input name="response_accept_ping" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $pingRules['accept'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Reject keywords</label>
                <input name="response_reject_ping" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $pingRules['reject'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Forwarding keys</label>
                <input name="forwarding_keys_ping" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $pingRules['forwarding_keys'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Payout keys</label>
                <input name="payout_keys_ping" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $pingRules['payout_keys'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Bid keys</label>
                <input name="bid_keys_ping" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $pingRules['bid_keys'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Duration keys</label>
                <input name="duration_keys_ping" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $pingRules['duration_keys'] ?? []) }}" />
              </div>
            </div>
          </div>
        </div>
        <div>
          <div class="text-sm font-semibold text-slate-700 mb-2">Post Mapping</div>
          @include('buyers.partials.field-table', ['fieldRows' => $postFields])
          <div class="mt-4 rounded-xl border border-slate-200 p-4">
            <div class="text-sm font-semibold text-slate-700 mb-3">Post Response Mapping</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="text-xs text-slate-600">Accept keywords</label>
                <input name="response_accept_post" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $postRules['accept'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Reject keywords</label>
                <input name="response_reject_post" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $postRules['reject'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Forwarding keys</label>
                <input name="forwarding_keys_post" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $postRules['forwarding_keys'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Payout keys</label>
                <input name="payout_keys_post" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $postRules['payout_keys'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Bid keys</label>
                <input name="bid_keys_post" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $postRules['bid_keys'] ?? []) }}" />
              </div>
              <div>
                <label class="text-xs text-slate-600">Duration keys</label>
                <input name="duration_keys_post" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $postRules['duration_keys'] ?? []) }}" />
              </div>
            </div>
          </div>
        </div>
      </div>
    @else
      @include('buyers.partials.field-table', ['fieldRows' => $singleFields])
      <div class="mt-4 rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-semibold text-slate-700 mb-3">Full Post Response Mapping</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="text-xs text-slate-600">Accept keywords</label>
            <input name="response_accept_single" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $singleRules['accept'] ?? []) }}" />
          </div>
          <div>
            <label class="text-xs text-slate-600">Reject keywords</label>
            <input name="response_reject_single" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $singleRules['reject'] ?? []) }}" />
          </div>
          <div>
            <label class="text-xs text-slate-600">Forwarding keys</label>
            <input name="forwarding_keys_single" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $singleRules['forwarding_keys'] ?? []) }}" />
          </div>
          <div>
            <label class="text-xs text-slate-600">Payout keys</label>
            <input name="payout_keys_single" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $singleRules['payout_keys'] ?? []) }}" />
          </div>
          <div>
            <label class="text-xs text-slate-600">Bid keys</label>
            <input name="bid_keys_single" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $singleRules['bid_keys'] ?? []) }}" />
          </div>
          <div>
            <label class="text-xs text-slate-600">Duration keys</label>
            <input name="duration_keys_single" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ implode(',', $singleRules['duration_keys'] ?? []) }}" />
          </div>
        </div>
      </div>
    @endif
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mt-6">
    <h3 class="text-lg font-semibold mb-4">Test Buyer</h3>

    @if ($isPingPost)
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <div class="text-sm font-semibold text-slate-700 mb-2">Ping Test</div>
          <form method="post" action="{{ route('buyers.test.ping', $buyer) }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              @foreach ($sampleLeadPing as $key => $value)
                <div>
                  <label class="text-xs text-slate-600">{{ $key }}</label>
                  <input name="lead_fields_ping[{{ $key }}]" value="{{ $value }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                </div>
              @endforeach
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">Send Ping Test</button>
          </form>

          @if (session('test_result_ping'))
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div class="text-sm font-semibold mb-2">Parsed Result</div>
              <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result_ping')['parsed'], JSON_PRETTY_PRINT) }}</pre>
            </div>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div class="text-sm font-semibold mb-2">Raw Response</div>
              <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result_ping')['raw'], JSON_PRETTY_PRINT) }}</pre>
            </div>
          @endif
        </div>
        <div>
          <div class="text-sm font-semibold text-slate-700 mb-2">Post Test</div>
          <form method="post" action="{{ route('buyers.test.post', $buyer) }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input name="ping_id" placeholder="Ping ID (optional)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs" />
              <input name="lead_id" placeholder="Lead ID (optional)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              @foreach ($sampleLeadPost as $key => $value)
                <div>
                  <label class="text-xs text-slate-600">{{ $key }}</label>
                  <input name="lead_fields_post[{{ $key }}]" value="{{ $value }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                </div>
              @endforeach
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">Send Post Test</button>
          </form>

          @if (session('test_result_post'))
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div class="text-sm font-semibold mb-2">Parsed Result</div>
              <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result_post')['parsed'], JSON_PRETTY_PRINT) }}</pre>
            </div>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div class="text-sm font-semibold mb-2">Raw Response</div>
              <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result_post')['raw'], JSON_PRETTY_PRINT) }}</pre>
            </div>
          @endif
        </div>
      </div>
    @else
      <form method="post" action="{{ route('buyers.test', $buyer) }}" class="space-y-3">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @foreach ($sampleLeadSingle as $key => $value)
            <div>
              <label class="text-xs text-slate-600">{{ $key }}</label>
              <input name="lead_fields_single[{{ $key }}]" value="{{ $value }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
            </div>
          @endforeach
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm hover:bg-blue-700">Send Full Post Test</button>
      </form>

      @if (session('test_result_single'))
        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm font-semibold mb-2">Parsed Result</div>
          <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result_single')['parsed'], JSON_PRETTY_PRINT) }}</pre>
        </div>
        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm font-semibold mb-2">Raw Response</div>
          <pre class="text-xs whitespace-pre-wrap">{{ json_encode(session('test_result_single')['raw'], JSON_PRETTY_PRINT) }}</pre>
        </div>
      @endif
    @endif
  </div>
@endsection
