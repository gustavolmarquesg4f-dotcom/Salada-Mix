<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Salada Mix — moda, beleza, tecnologia, casa e muito mais. Marketplace em preparação.">
    <meta name="theme-color" content="#12694e">
    <title>@yield('title', 'Salada Mix — Tudo num só lugar')</title>
    <link rel="icon" href="{{ asset('assets/salada/salada-mix-simbolo.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="sm-body">
    <a href="#conteudo" class="sm-skip">Pular para o conteúdo</a>
    <x-salada.header />
    <div class="sm-status" role="status"><strong>PLATAFORMA EM PREPARAÇÃO</strong> · Empresas em análise; compras, frete e pagamentos reais ainda indisponíveis.</div>
    <main id="conteudo" class="sm-container sm-main" tabindex="-1">
        @if (session('status')) <div class="sm-notice success" role="status">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="sm-notice error" role="alert"><strong>Confira os campos:</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <x-salada.footer />
    @livewireScripts
</body>
</html>
