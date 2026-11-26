{{--
    Kerangka halaman error. Sengaja mandiri (CSS inline, tanpa Vite, sesi, atau database) agar tetap tampil
    walaupun aplikasi sedang bermasalah, dan tidak pernah menampilkan path folder atau potongan kode.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('code') — @yield('title') | SIMPIL</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; background: #f9fafb; color: #1f2937;
               font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        header { background: #fff; border-bottom: 1px solid #e5e7eb; }
        header .wrap { max-width: 72rem; margin: 0 auto; padding: 1rem; display: flex; align-items: center; justify-content: space-between; }
        header a { text-decoration: none; color: #111827; font-size: 1.5rem; font-weight: 700; }
        header img { height: 2.5rem; width: auto; }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem 1rem; }
        .card { max-width: 32rem; width: 100%; text-align: center; }
        .code { font-size: 4.5rem; font-weight: 800; line-height: 1; color: #dc2626; margin: 0; }
        h1 { font-size: 1.5rem; margin: 1rem 0 .5rem; }
        p { color: #4b5563; line-height: 1.6; margin: 0 0 1.75rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; }
        .btn { display: inline-block; padding: .625rem 1.25rem; border-radius: .5rem; font-size: .875rem; font-weight: 600; text-decoration: none; }
        .btn-primary { background: #dc2626; color: #fff; }
        .btn-primary:hover { background: #b91c1c; }
        .btn-secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #f3f4f6; }
        footer { text-align: center; font-size: .875rem; color: #6b7280; padding: 1.5rem 1rem; border-top: 1px solid #e5e7eb; background: #fff; }
    </style>
</head>
<body>
    <header>
        <div class="wrap">
            <a href="{{ url('/') }}">SIMPIL</a>
            <img src="{{ asset('images/logo-brida.png') }}" alt="BRIDA Kota Makassar">
        </div>
    </header>

    <main>
        <div class="card">
            <p class="code">@yield('code')</p>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="actions">
                <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
                @hasSection('secondary')
                    @yield('secondary')
                @else
                    <a href="javascript:history.back()" class="btn btn-secondary">Halaman Sebelumnya</a>
                @endif
            </div>
        </div>
    </main>

    <footer>&copy; {{ date('Y') }} SIMPIL — BRIDA Kota Makassar</footer>
</body>
</html>
