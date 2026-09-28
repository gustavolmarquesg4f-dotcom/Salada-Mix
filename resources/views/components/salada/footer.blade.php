<footer class="sm-footer">
    <div class="sm-container sm-footer-benefits" aria-label="Informações do marketplace">
        <div class="sm-benefit"><x-salada.icon name="truck" size="26" /><div><strong>Entrega por região</strong><span>O cálculo de frete estará disponível após a integração.</span></div></div>
        <div class="sm-benefit"><x-salada.icon name="shield" size="26" /><div><strong>Experiência protegida</strong><span>Cadastro e acesso com permissões por perfil.</span></div></div>
        <div class="sm-benefit"><x-salada.icon name="grid" size="26" /><div><strong>Variedade em um só lugar</strong><span>Empresas cadastradas e ofertas sujeitas à revisão.</span></div></div>
    </div>
    <div class="sm-container sm-footer-grid">
        <div><a class="sm-footer-brand" href="{{ route('home') }}" aria-label="Salada Mix — início"><img src="{{ asset('assets/salada/salada-mix-logo.svg') }}" alt="Salada Mix" width="158" height="32"></a><p>Seu mix, seu estilo. Moda, beleza, tecnologia, casa e achados de diferentes departamentos.</p></div>
        <div><h2>Explore</h2><a href="{{ route('home') }}#departamentos">Departamentos</a><a href="{{ route('home') }}#ofertas">Ofertas</a><a href="{{ route('home') }}">Página inicial</a></div>
        <div><h2>Minha conta</h2>@auth<a href="{{ route('buyer.account') }}">Meus dados</a><form action="{{ route('logout') }}" method="post">@csrf<button type="submit" class="text-left text-[13px] text-salada-jade hover:underline">Sair</button></form>@else<a href="{{ route('login') }}">Entrar</a><a href="{{ route('register') }}">Criar conta</a>@endauth</div>
        <div><h2>Seja parceiro</h2><a href="{{ route('seller.apply') }}">Cadastrar empresa</a><p>Cadastro sujeito a aprovação. Pagamentos e vendas ainda não estão habilitados.</p></div>
    </div>
    <div class="sm-container sm-footer-bottom"><span>© {{ date('Y') }} Salada Mix. Todos os direitos reservados.</span><span>Ambiente de desenvolvimento — sem transações financeiras.</span></div>
</footer>
