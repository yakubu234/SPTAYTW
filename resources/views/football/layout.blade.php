<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sporty Safe Engine')</title>
    <style>
        :root{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033;background:#f5f7fb}*{box-sizing:border-box}body{margin:0}.wrap{max-width:1240px;margin:auto;padding:24px}.nav{background:#111827;color:#fff}.nav .wrap{display:flex;gap:18px;align-items:center;padding-top:16px;padding-bottom:16px}.brand{font-weight:800;margin-right:auto}.nav a{color:#dbeafe;text-decoration:none}.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}.stat{font-size:28px;font-weight:800}.muted{color:#64748b;font-size:13px}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 14px;background:#111827;color:#fff;text-decoration:none;cursor:pointer}.btn.secondary{background:#e2e8f0;color:#172033}input,select{padding:9px;border:1px solid #cbd5e1;border-radius:7px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;vertical-align:top}th{font-size:12px;text-transform:uppercase;color:#64748b}.badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#e2e8f0;font-size:12px;font-weight:700}.strong_qualified,.qualified{background:#dcfce7;color:#166534}.watch{background:#fef3c7;color:#92400e}.skip{background:#fee2e2;color:#991b1b}.alert{padding:12px;border-radius:8px;background:#dcfce7;margin-bottom:16px}.risk{color:#991b1b}.row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.row label{display:flex;flex-direction:column;gap:5px;font-size:13px}.scroll{overflow:auto}h1{margin-top:0}@media(max-width:700px){.wrap{padding:14px}.nav .wrap{align-items:flex-start;flex-wrap:wrap}.brand{width:100%}table{font-size:13px}}
    </style>
</head>
<body>
<nav class="nav"><div class="wrap"><div class="brand">Sporty Safe Engine</div><a href="{{ route('football.dashboard') }}">Dashboard</a><a href="{{ route('football.tickets.index') }}">Tickets</a><a href="{{ route('football.performance') }}">Performance</a><a href="{{ route('football.calibration') }}">Calibration</a></div></nav>
<main class="wrap">
@if(session('message'))<div class="alert">{{ session('message') }}</div>@endif
@yield('content')
</main>
</body>
</html>
