@extends('layouts.app')
@section('title', 'Demonstração integrada — Salada Mix')
@section('content')
<section class="sm-preview-panel">
    <span class="sm-eyebrow">HOMOLOGAÇÃO • DADOS SINTÉTICOS</span>
    <h1 class="text-3xl font-black mt-2">Explore o Salada Mix em funcionamento</h1>
    <p class="mt-3">Este painel consulta o MariaDB da homologação. A FE-06 é a apresentação visual; as telas abaixo usam o Laravel e persistem apenas as ações permitidas de uma conta autenticada.</p>
    <div class="sm-preview-grid mt-6">
        <div class="sm-preview-stat"><strong>{{ $shops }}</strong><span>lojas demonstrativas</span></div>
        <div class="sm-preview-stat"><strong>{{ $products }}</strong><span>produtos sintéticos</span></div>
        <div class="sm-preview-stat"><strong>{{ $offers }}</strong><span>ofertas sintéticas</span></div>
        <div class="sm-preview-stat"><strong>{{ $categories }}</strong><span>departamentos ativos</span></div>
    </div>
    <div class="sm-preview-actions mt-6">
        @if(!auth()->check())
        <form method="post" action="{{ route('demo.start') }}">@csrf<button class="sm-btn sm-btn-primary" type="submit">Entrar como comprador fictício — sem senha</button></form>
        @elseif((string) session('salada_demo_user_id') === (string) auth()->id())
        <a class="sm-btn sm-btn-primary" href="{{ route('demo.checkout') }}">Abrir checkout de demonstração</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('demo.orders') }}">Acompanhar pedidos DEMO</a>
        @else
        <p>Você está conectado a uma conta real. Para abrir uma sessão de comprador sintético, encerre a sessão atual.</p>
        @endif
        <a class="sm-btn sm-btn-secondary" href="{{ route('storefront.search') }}">Explorar e adicionar produtos</a>
        <a class="sm-btn sm-btn-primary" href="{{ route('storefront.live') }}">Explorar vitrine conectada ao banco</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('storefront.search') }}">Busca e filtros reais</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('register') }}">Criar conta de teste</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('login') }}">Entrar</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('home') }}">Ver FE-06 visual</a>
    </div>
</section>
<section class="sm-preview-panel mt-6">
    <h2 class="text-2xl font-bold">Jornadas com backend</h2>
    <p>Ao autenticar uma conta de teste e verificar seu e-mail, sacola, favoritos e endereços utilizam a sessão protegida e o banco de dados. A gestão de vendedor exige vínculo com a empresa; a administração exige perfil autorizado e MFA.</p>
    <div class="sm-preview-actions mt-4">
        <a class="sm-btn sm-btn-secondary" href="{{ route('buyer.cart.page') }}">Minha sacola</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('buyer.wishlist.page') }}">Favoritos</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('buyer.addresses.index') }}">Endereços</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('buyer.checkout.prepare') }}">Preparação de compra</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('seller.apply') }}">Inscrição de vendedor</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('admin.sellers.index') }}">Administração protegida</a>
    </div>
</section>
<div class="sm-notice info mt-6" role="status"><strong>Limite desta versão:</strong> pedidos, frete, pagamento e pós-venda podem ser percorridos na simulação isolada. Nenhuma compra ou cobrança real é executada. Não utilize informações pessoais reais nesta demonstração.</div>
@endsection
