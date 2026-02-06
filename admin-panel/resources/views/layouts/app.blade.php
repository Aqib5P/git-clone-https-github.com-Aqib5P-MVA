<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'Lead Admin' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 text-slate-900" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
    <nav class="bg-white border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-6 py-4 flex flex-wrap items-center gap-4">
        <a class="font-semibold text-lg text-slate-900" href="{{ route('dashboard') }}">Lead Admin</a>
        <div class="flex flex-wrap gap-3 text-sm">
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('dashboard') }}">Dashboard</a>
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('buyers.index') }}">Buyers</a>
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('fields.index') }}">Lead Fields</a>
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('leads.index') }}">Leads</a>
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('products.index') }}">Products</a>
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('campaigns.index') }}">Campaigns</a>
          <a class="text-slate-600 hover:text-slate-900" href="{{ route('publishers.index') }}">Publishers</a>
        </div>
        <form method="post" action="{{ route('logout') }}" class="ml-auto">
          @csrf
          <button class="border border-slate-300 px-3 py-1.5 rounded-lg text-sm hover:bg-slate-100" type="submit">Logout</button>
        </form>
      </div>
    </nav>

    @php
      $fullWidth = $fullWidth ?? false;
    @endphp
    <div class="{{ $fullWidth ? 'w-full' : 'max-w-7xl mx-auto' }} {{ $fullWidth ? '' : 'px-6' }} py-6">
      @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 mb-4">
          {{ $errors->first() }}
        </div>
      @endif
      @yield('content')
    </div>
  </body>
</html>
