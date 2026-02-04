<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'Lead Admin' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
      .wrap { max-width: 1200px; margin: 0 auto; padding: 20px; }
      .card { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
      .muted { color: var(--muted); }
      label { font-size: 12px; color: var(--muted); }
      table th, table td { font-size: 13px; }
      .badge { padding: 2px 8px; border-radius: 999px; font-size: 11px; }
      .badge.ok { background: rgba(22, 163, 74, 0.1); color: var(--ok); border: 1px solid rgba(22, 163, 74, 0.3); }
      .badge.bad { background: rgba(220, 38, 38, 0.1); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.3); }
      .badge.unknown { background: rgba(148, 163, 184, 0.1); color: var(--muted); border: 1px solid rgba(148, 163, 184, 0.3); }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
      <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Lead Admin</a>
        <div class="collapse navbar-collapse show">
          <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('buyers.index') }}">Buyers</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('fields.index') }}">Lead Fields</a></li>
          </ul>
          <form method="post" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button class="btn btn-outline-primary btn-sm" type="submit">Logout</button>
          </form>
        </div>
      </div>
    </nav>

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
