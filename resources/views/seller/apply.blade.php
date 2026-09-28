@extends('layouts.app')
@section('title', 'Cadastrar empresa — Salada Mix')
@section('content')
<div class="max-w-2xl"><h1 class="text-3xl font-black">Faça parte do Salada Mix</h1>
<p class="mt-3 text-slate-600">Envie os dados da empresa para avaliação. A aprovação não libera automaticamente pagamentos ou publicação de produtos.</p></div>
<form action="{{ route('seller.submit') }}" method="post" class="mt-7 max-w-2xl space-y-5 rounded-2xl border bg-white p-7">
    @csrf
    <label class="block text-sm font-semibold">Razão social <input name="legal_name" required maxlength="200" value="{{ old('legal_name') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">Nome fantasia <input name="trade_name" required maxlength="160" value="{{ old('trade_name') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">CNPJ <input name="cnpj" inputmode="text" required maxlength="18" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00 ou 00.000.000/E08G-12" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">E-mail comercial <input name="contact_email" type="email" required maxlength="255" value="{{ old('contact_email') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <p class="text-xs text-slate-500">Não envie documentos sensíveis por este formulário. A etapa documental será habilitada após revisão jurídica e do armazenamento privado.</p>
    <button class="rounded-xl bg-teal-700 px-6 py-3 font-bold text-white" type="submit">Enviar para análise</button>
</form>
@endsection
