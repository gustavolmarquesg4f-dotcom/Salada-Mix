@extends('layouts.app')
@section('title', 'Meus endereços — Salada Mix')
@section('content')
<div class="mx-auto max-w-5xl">
    <p class="text-sm font-bold uppercase tracking-wide text-violet-700">Minha conta</p>
    <h1 class="mt-2 text-3xl font-black">Meus endereços</h1>
    <p class="mt-2 text-slate-600">Seus dados são privados e usados apenas conforme as finalidades informadas. Nenhuma compra está habilitada.</p>
    @if(session('status')) <p role="status" class="mt-4 rounded-xl bg-green-50 p-4 text-green-800">{{ session('status') }}</p> @endif
    <div class="mt-6 grid gap-5 md:grid-cols-2">
        <section class="rounded-2xl border bg-white p-6">
            <h2 class="text-xl font-bold">Endereços salvos</h2>
            @forelse($addresses as $address)
                <div class="mt-4 rounded-xl border p-4">
                    <p class="font-bold">{{ $address->label }} @if($address->is_default) <span class="text-xs text-violet-700">(principal)</span> @endif</p>
                    <p class="text-sm text-slate-700">{{ $address->recipient_name }}</p>
                    <p class="text-sm text-slate-700">{{ $address->street }}, {{ $address->number }} — {{ $address->neighborhood }}</p>
                    <p class="text-sm text-slate-700">{{ $address->city }}/{{ $address->state }} · {{ substr($address->postal_code, 0, 5) }}-{{ substr($address->postal_code, 5) }}</p>
                    <form class="mt-3" method="POST" action="{{ route('buyer.addresses.destroy', $address->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm font-bold text-red-700">Excluir endereço</button>
                    </form>
                </div>
            @empty
                <p class="mt-4 text-sm text-slate-600">Nenhum endereço cadastrado.</p>
            @endforelse
        </section>
        <section class="rounded-2xl border bg-white p-6">
            <h2 class="text-xl font-bold">Adicionar endereço</h2>
            <form method="POST" action="{{ route('buyer.addresses.store') }}" class="mt-4 grid gap-3">
                @csrf
                @foreach(['label' => 'Apelido (casa, trabalho)', 'recipient_name' => 'Nome de quem recebe',
                    'phone' => 'Telefone (opcional)', 'postal_code' => 'CEP', 'street' => 'Rua / avenida',
                    'number' => 'Número', 'complement' => 'Complemento (opcional)',
                    'neighborhood' => 'Bairro', 'city' => 'Cidade', 'state' => 'UF (ex.: DF)'] as $field => $label)
                    <label class="block text-sm font-semibold">{{ $label }}
                        <input name="{{ $field }}" value="{{ old($field) }}" @unless(in_array($field, ['phone', 'complement'])) required @endunless
                               class="mt-1 block w-full rounded-lg border border-slate-300 p-3" />
                        @error($field) <span role="alert" class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                @endforeach
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default')) /> Usar como endereço principal</label>
                <button class="rounded-xl bg-violet-700 px-5 py-3 font-bold text-white">Salvar endereço</button>
            </form>
        </section>
    </div>
    <a class="mt-6 inline-block font-semibold text-violet-700" href="{{ route('buyer.account') }}">Voltar para minha conta</a>
</div>
@endsection
