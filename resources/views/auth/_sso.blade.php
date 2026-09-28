@php
    $providers = collect(config('sso.providers', []))->filter(fn ($provider) => (bool) ($provider['enabled'] ?? false));
@endphp

@if ($providers->isNotEmpty())
    <div class="space-y-3">
        <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
            <span class="h-px flex-1 bg-slate-200"></span><span>ou continue com</span><span class="h-px flex-1 bg-slate-200"></span>
        </div>
        @foreach ($providers as $id => $provider)
            <a href="{{ route('sso.redirect', $id) }}"
               class="flex w-full items-center justify-center rounded-xl border bg-white p-3 font-bold text-slate-700 hover:border-teal-600 hover:text-teal-700">
                {{ $provider['label'] ?? ucfirst($id) }}
            </a>
        @endforeach
        <p class="text-xs text-slate-500">O Salada Mix não armazena o token OAuth do provedor após identificar sua conta.</p>
    </div>
@endif

