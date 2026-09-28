@extends('layouts.app')
@section('title', 'Segurança da conta — Salada Mix')
@section('content')
<div class="mx-auto max-w-xl rounded-2xl border bg-white p-7">
    <h1 class="text-2xl font-black">Verificação de dois fatores</h1>
    <p class="mt-3 text-sm text-slate-600">Contas administrativas precisam de um aplicativo autenticador. Nunca compartilhe códigos ou segredos.</p>

    @if ($recoveryCodes)
        <div class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <h2 class="font-bold">Salve seus códigos de recuperação agora</h2>
            <p class="mt-2 text-sm">Cada código funciona uma única vez e não será exibido novamente.</p>
            <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm">@foreach ($recoveryCodes as $code)<span>{{ $code }}</span>@endforeach</div>
        </div>
    @endif

    @unless ($configured)
        <form action="{{ route('security.mfa.begin') }}" method="post" class="mt-6 space-y-3">
            @csrf
            <h2 class="font-bold">1. Configurar autenticador</h2>
            <p class="text-sm text-slate-600">Confirme sua senha para gerar uma chave temporária.</p>
            <label class="block text-sm font-semibold">Senha atual
                <input class="mt-1 w-full rounded-lg border p-3" name="current_password" type="password" required autocomplete="current-password">
            </label>
            <button class="rounded-xl bg-teal-700 p-3 font-bold text-white" type="submit">Gerar chave de configuração</button>
        </form>
        @if ($setupSecret)
            <div class="mt-6 rounded-lg border bg-slate-50 p-4">
                <h2 class="font-bold">2. Adicionar ao aplicativo</h2>
                <p class="mt-2 text-sm">No aplicativo autenticador, escolha adicionar conta manualmente e informe a chave:</p>
                <code class="mt-2 block break-all select-all text-sm font-bold">{{ $setupSecret }}</code>
                <p class="mt-2 text-xs text-slate-600">Tipo: baseado em tempo (TOTP), seis dígitos, intervalo de 30 segundos.</p>
                <form action="{{ route('security.mfa.confirm') }}" method="post" class="mt-4 space-y-3">
                    @csrf
                    <label class="block text-sm font-semibold">Código do aplicativo
                        <input class="mt-1 w-full rounded-lg border p-3" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">
                    </label>
                    <button class="rounded-xl bg-teal-700 p-3 font-bold text-white" type="submit">Confirmar segundo fator</button>
                </form>
            </div>
        @endif
    @elseif (! $verified)
        <form action="{{ route('security.mfa.challenge') }}" method="post" class="mt-6 space-y-4">
            @csrf
            <h2 class="font-bold">Confirme o acesso administrativo</h2>
            <label class="block text-sm font-semibold">Código do aplicativo
                <input class="mt-1 w-full rounded-lg border p-3" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code">
            </label>
            <p class="text-xs text-slate-500">Sem o aplicativo? Informe um código de recuperação em vez do código acima.</p>
            <label class="block text-sm font-semibold">Código de recuperação (alternativa)
                <input class="mt-1 w-full rounded-lg border p-3" name="recovery_code" maxlength="60">
            </label>
            <button class="rounded-xl bg-teal-700 p-3 font-bold text-white" type="submit">Verificar acesso</button>
        </form>
    @else
        <div class="mt-6 rounded-xl bg-emerald-50 p-4 text-emerald-900">Segundo fator confirmado nesta sessão.</div>
        <a class="mt-4 inline-block rounded-xl bg-teal-700 p-3 font-bold text-white" href="{{ route('admin.sellers.index') }}">Abrir administração</a>
        <form action="{{ route('security.mfa.regenerate') }}" method="post" class="mt-8 space-y-3">
            @csrf
            <h2 class="font-bold">Substituir códigos de recuperação</h2>
            <label class="block text-sm font-semibold">Senha atual
                <input class="mt-1 w-full rounded-lg border p-3" name="current_password" type="password" required autocomplete="current-password">
            </label>
            <label class="block text-sm font-semibold">Novo código do aplicativo
                <input class="mt-1 w-full rounded-lg border p-3" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code">
            </label>
            <button class="rounded-xl border border-teal-700 p-3 font-bold text-teal-800" type="submit">Gerar novos códigos</button>
        </form>
    @endif
</div>
@endsection

