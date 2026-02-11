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
    @php
      $fullWidth = $fullWidth ?? false;
      $navItems = [
        ["label" => "Dashboard", "route" => "dashboard", "active" => request()->routeIs("dashboard")],
        ["label" => "Buyers", "route" => "buyers.index", "active" => request()->routeIs("buyers.*")],
        ["label" => "Lead Fields", "route" => "fields.index", "active" => request()->routeIs("fields.*")],
        ["label" => "Leads", "route" => "leads.index", "active" => request()->routeIs("leads.*")],
        ["label" => "Products", "route" => "products.index", "active" => request()->routeIs("products.*")],
        ["label" => "Campaigns", "route" => "campaigns.index", "active" => request()->routeIs("campaigns.*")],
        ["label" => "Publishers", "route" => "publishers.index", "active" => request()->routeIs("publishers.*")],
      ];
    @endphp
    <div class="min-h-screen flex flex-col lg:flex-row">
      <aside class="bg-slate-900 text-white lg:w-64 w-full flex flex-col">
        <div class="px-6 py-6 border-b border-white/10">
          <a class="text-xl font-semibold tracking-wide" href="{{ route('dashboard') }}">Lead Admin</a>
          <div class="text-xs text-slate-400 mt-1">Control Center</div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
          @foreach ($navItems as $item)
            <a
              href="{{ route($item['route']) }}"
              class="flex items-center gap-2 rounded-lg px-3 py-2 transition {{ $item['active'] ? 'bg-white/10 text-white' : 'text-slate-300 hover:text-white hover:bg-white/5' }}"
            >
              <span class="font-medium">{{ $item['label'] }}</span>
            </a>
          @endforeach
        </nav>
        <form method="post" action="{{ route('logout') }}" class="px-4 pb-6">
          @csrf
          <button class="w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm hover:bg-white/10" type="submit">Logout</button>
        </form>
      </aside>
      <main class="flex-1">
        <div class="{{ $fullWidth ? 'w-full' : 'max-w-7xl' }} {{ $fullWidth ? '' : 'mx-auto' }} px-6 py-6">
          @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 mb-4">
              {{ $errors->first() }}
            </div>
          @endif
          @yield('content')
        </div>
      </main>
    </div>
  </body>
</html>
