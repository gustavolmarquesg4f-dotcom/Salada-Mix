<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Salada Mix — moda, beleza, tecnologia, casa e muito mais em um marketplace multidepartamentos.">
    <meta name="theme-color" content="#0b654d">
    <title>@yield('title', 'Salada Mix — Seu mix de estilos')</title>
    <link rel="icon" href="{{ asset('assets/salada/salada-mix-simbolo.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="sm-body">
    <a href="#conteudo" class="sm-skip">Pular para o conteúdo</a>
    <x-salada.header />
    @if(app()->environment('staging'))
        <div class="sm-stage-strip" role="status">
            <span class="sm-stage-dot" aria-hidden="true"></span>
            <strong>HOMOLOGAÇÃO</strong>
            <span>Ambiente de demonstração com produtos e transações SANDBOX. Nenhuma cobrança externa é realizada.</span>
            <a href="{{ route('storefront.demo') }}">Abrir jornada de teste →</a>
        </div>
    @endif
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
