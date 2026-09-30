<footer class="sm-footer sm-footer-v2">
    <div class="sm-container sm-footer-benefits sm-footer-benefits-v2" aria-label="Recursos do marketplace">
        <div class="sm-benefit"><x-salada.icon name="shield" size="27" /><div><strong>Conta protegida</strong><span>Autenticação, permissões e MFA administrativo.</span></div></div>
        <div class="sm-benefit"><x-salada.icon name="truck" size="27" /><div><strong>Logística por loja</strong><span>Cotação e origem tratadas separadamente por vendedor.</span></div></div>
        <div class="sm-benefit"><x-salada.icon name="grid" size="27" /><div><strong>Variedade de verdade</strong><span>Um marketplace preparado para múltiplos departamentos.</span></div></div>
    </div>
    <div class="sm-container sm-footer-grid sm-footer-grid-v2">
        <div>
            <a class="sm-footer-brand" href="{{ route('home') }}" aria-label="Salada Mix — início"><img src="{{ asset('assets/salada/salada-mix-logo.svg') }}" alt="Salada Mix" width="170" height="35"></a>
            <p>Seu mix de estilos em um só lugar. Uma experiência pensada para encontrar produtos, lojas e categorias com facilidade.</p>
        </div>
        <div><h2>Explore</h2><a href="{{ route('home') }}#departamentos">Departamentos</a><a href="{{ route('storefront.search') }}">Todos os produtos</a><a href="{{ route('home') }}#ofertas">Destaques</a></div>
        <div><h2>Minha conta</h2>@auth<a href="{{ route('buyer.account') }}">Meus dados</a><a href="{{ route('buyer.cart.page') }}">Minha sacola</a><a href="{{ route('buyer.wishlist.page') }}">Favoritos</a>@else<a href="{{ route('login') }}">Entrar</a><a href="{{ route('register') }}">Criar conta</a>@endauth</div>
        <div><h2>Para vendedores</h2><a href="{{ route('seller.apply') }}">Quero vender</a>@can('review-sellers')<a href="{{ route('admin.manage') }}">Administração</a>@endcan<p>Empresas e ofertas passam por validação antes da publicação comercial.</p></div>
    </div>
    <div class="sm-container sm-footer-bottom"><span>© {{ date('Y') }} Salada Mix. Todos os direitos reservados.</span>@if(app()->environment('staging'))<span>HML · sem cobrança externa</span>@endif</div>
</footer>
