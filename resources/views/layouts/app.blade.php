<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Salada Mix: tudo num só lugar. Marketplace em construção.">
    <title>@yield('title', 'Salada Mix')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="text-2xl font-black tracking-tight text-teal-700">SALADA<span class="text-orange-500"> MIX</span></a>
            <nav aria-label="Navegação principal" class="flex flex-wrap items-center gap-4 text-sm font-semibold">
                <a href="{{ route('home') }}" class="hover:text-teal-700">Início</a>
                <a href="{{ route('home') }}#departamentos" class="hover:text-teal-700">Departamentos</a>
                <a href="{{ route('seller.apply') }}" class="hover:text-teal-700">Quero vender</a>
                @auth
                    <a href="{{ route('buyer.account') }}" class="hover:text-teal-700">Minha conta</a>
                    @can('review-sellers')
                        <a href="{{ route('admin.sellers.index') }}" class="hover:text-teal-700">Administração</a>
                        <a href="{{ route('admin.catalog.index') }}" class="hover:text-teal-700">Moderação</a>
                    @endcan
                    <form action="{{ route('logout') }}" method="post">@csrf<button type="submit" class="hover:text-teal-700">Sair</button></form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-teal-700">Entrar</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-white hover:bg-teal-800">Criar conta</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        @if (session('status')) <div role="status" class="mb-5 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                <p class="font-bold">Confira os campos:</p>
                <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
        Salada Mix 2.0 — ambiente de fundação. Vendas e pagamentos ainda não estão habilitados.
    </footer>
    @livewireScripts
</body>
</html>
