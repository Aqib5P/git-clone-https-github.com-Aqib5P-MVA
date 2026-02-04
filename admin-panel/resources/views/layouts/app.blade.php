<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'Lead Admin' }}</title>
    <style>
      :root {
        --bg: #f6f7fb;
        --panel: #ffffff;
        --border: #d9dfeb;
        --text: #0f172a;
        --muted: #64748b;
        --accent: #2563eb;
        --danger: #dc2626;
        --ok: #16a34a;
      }
      body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--text); }
      header { background: var(--panel); border-bottom: 1px solid var(--border); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; }
      header a { color: var(--accent); text-decoration: none; margin-right: 12px; }
      .wrap { max-width: 1100px; margin: 0 auto; padding: 20px; }
      .card { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
      .muted { color: var(--muted); }
      .row { display: flex; gap: 12px; flex-wrap: wrap; }
      label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 4px; }
      input, select, button { padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border); }
      button { background: rgba(37, 99, 235, 0.12); cursor: pointer; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border-bottom: 1px solid var(--border); padding: 8px; font-size: 13px; text-align: left; }
      .badge { padding: 2px 8px; border-radius: 999px; font-size: 11px; }
      .badge.ok { background: rgba(22, 163, 74, 0.1); color: var(--ok); border: 1px solid rgba(22, 163, 74, 0.3); }
      .badge.bad { background: rgba(220, 38, 38, 0.1); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.3); }
      .badge.unknown { background: rgba(148, 163, 184, 0.1); color: var(--muted); border: 1px solid rgba(148, 163, 184, 0.3); }
      .nav-right form { display: inline; }
    </style>
  </head>
  <body>
    <header>
      <div>
        <strong>Lead Admin</strong>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('buyers.index') }}">Buyers</a>
      </div>
      <div class="nav-right">
        <form method="post" action="{{ route('logout') }}">
          @csrf
          <button type="submit">Logout</button>
        </form>
      </div>
    </header>

    <div class="wrap">
      @if ($errors->any())
        <div class="card" style="border-color: rgba(220, 38, 38, 0.4); color: var(--danger);">
          {{ $errors->first() }}
        </div>
      @endif
      @yield('content')
    </div>
  </body>
</html>
