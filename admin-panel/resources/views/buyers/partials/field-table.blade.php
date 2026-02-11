<div class="overflow-x-auto">
<table class="min-w-full text-sm">
  <thead>
    <tr class="text-left text-slate-500">
      <th class="py-2">Our Field</th>
      <th class="py-2">Buyer Field</th>
      <th class="py-2">Value</th>
      <th class="py-2">Source</th>
      <th class="py-2">Required</th>
      <th class="py-2">Direction</th>
      <th class="py-2">Actions</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($fieldRows as $field)
      <tr class="border-t border-slate-100">
        <td class="py-2">{{ $field->source_key ?? '—' }}</td>
        <td class="py-2">{{ $field->field_name }}</td>
        <td class="py-2">{{ $field->source_value ?? '—' }}</td>
        <td class="py-2">{{ $field->source_type }}</td>
        <td class="py-2">{{ $field->required ? 'Required' : 'Optional' }}</td>
        <td class="py-2">{{ $field->direction }}</td>
        <td class="py-2">
          <div class="flex flex-wrap items-center gap-2">
          <form method="post" action="{{ route('buyers.fields.update', $field) }}" class="inline">
            @csrf
            <input type="hidden" name="required" value="{{ $field->required ? 0 : 1 }}" />
            <button type="submit" class="border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100">{{ $field->required ? 'Make Optional' : 'Make Required' }}</button>
          </form>
          <details class="inline-block ml-2">
            <summary class="cursor-pointer border border-slate-300 px-2 py-1 rounded-lg text-xs hover:bg-slate-100 inline-block">Edit</summary>
            <div class="mt-2 p-3 border border-slate-200 rounded-lg bg-slate-50">
              <form method="post" action="{{ route('buyers.fields.update', $field) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <div>
                  <label class="text-xs text-slate-600">Direction</label>
                  <select name="direction" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1 text-xs">
                    <option value="single" @selected($field->direction === 'single')>Single</option>
                    <option value="ping" @selected($field->direction === 'ping')>Ping</option>
                    <option value="post" @selected($field->direction === 'post')>Post</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs text-slate-600">Source Type</label>
                  <select name="source_type" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1 text-xs">
                    <option value="lead" @selected($field->source_type === 'lead')>Lead</option>
                    <option value="static" @selected($field->source_type === 'static')>Static</option>
                    <option value="computed" @selected($field->source_type === 'computed')>Computed</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs text-slate-600">Our Field</label>
                  <input name="source_key" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1 text-xs" value="{{ $field->source_key }}" />
                </div>
                <div class="md:col-span-2">
                  <label class="text-xs text-slate-600">Value (static)</label>
                  <input name="source_value" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1 text-xs" value="{{ $field->source_value }}" />
                </div>
                <div>
                  <label class="text-xs text-slate-600">Required</label>
                  <select name="required" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1 text-xs">
                    <option value="1" @selected($field->required)>Required</option>
                    <option value="0" @selected(!$field->required)>Optional</option>
                  </select>
                </div>
                <div class="md:col-span-3 flex justify-end">
                  <button type="submit" class="rounded-lg bg-blue-600 text-white px-3 py-1 text-xs hover:bg-blue-700">Save</button>
                </div>
              </form>
            </div>
          </details>
          <form method="post" action="{{ route('buyers.fields.delete', $field) }}" class="inline">
            @csrf
            <button type="submit" class="border border-red-200 text-red-600 px-2 py-1 rounded-lg text-xs hover:bg-red-50">Delete</button>
          </form>
          </div>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="7" class="text-slate-500 py-3">No fields yet.</td>
      </tr>
    @endforelse
  </tbody>
</table>
</div>
