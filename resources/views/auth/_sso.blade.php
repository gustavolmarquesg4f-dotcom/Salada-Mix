@php
    $providers = collect(config('sso.providers', []))->filter(fn ($provider) => (bool) ($provider['enabled'] ?? false));
@endphp
@if($providers->isNotEmpty())
    <div class="sm-auth-divider"><span>Ou continue com</span></div>
    <div class="sm-sso-options">
        @foreach ($providers as $id => $provider)
            <a href="{{ route('sso.redirect', $id) }}" class="sm-sso-button">
                <span class="sm-sso-mark" aria-hidden="true">{{ strtoupper(substr($id, 0, 1)) }}</span>
                Continuar com {{ $provider['label'] ?? ucfirst($id) }}
            </a>
        @endforeach
    </div>
    <p class="sm-form-help">A disponibilidade das opções depende das integrações habilitadas. O Salada Mix não mantém os tokens OAuth de acesso do provedor após identificar sua conta.</p>
@endif
