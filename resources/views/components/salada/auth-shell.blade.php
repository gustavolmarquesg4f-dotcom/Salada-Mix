@props([
    'eyebrow' => 'Sua conta Salada Mix',
    'title' => 'Bem-vindo(a)',
    'subtitle' => '',
    'sideTitle' => 'Seu mix começa aqui.',
])
<div class="sm-auth-layout">
    <section class="sm-auth-card" aria-labelledby="sm-auth-title">
        <a href="{{ route('home') }}" class="sm-auth-back"><span aria-hidden="true">←</span> Voltar para a loja</a>
        <span class="sm-eyebrow">{{ $eyebrow }}</span>
        <h1 id="sm-auth-title">{{ $title }}</h1>
        @if($subtitle)<p class="sm-auth-subtitle">{{ $subtitle }}</p>@endif
        {{ $slot }}
    </section>
    <aside class="sm-auth-aside" aria-label="Conheça o Salada Mix">
        <div class="sm-auth-aside-content">
            <img src="{{ asset('assets/salada/salada-mix-logo.svg') }}" width="212" height="45" alt="Salada Mix">
            <span class="sm-auth-overline">Um universo de possibilidades</span>
            <h2>{{ $sideTitle }}</h2>
            <p>Beleza, moda, tecnologia, casa e muito mais. Descubra produtos e acompanhe sua experiência em um só lugar.</p>
            <div class="sm-auth-bubbles" aria-hidden="true"><span>Beleza</span><span>Tecnologia</span><span>Casa</span><span>Moda</span></div>
            <p class="sm-auth-small">Compras e pagamentos reais ainda estão indisponíveis nesta etapa.</p>
        </div>
        <div class="sm-auth-aside-orbit" aria-hidden="true"></div>
    </aside>
</div>
