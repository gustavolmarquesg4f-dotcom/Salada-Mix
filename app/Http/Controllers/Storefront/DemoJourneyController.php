<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Cart\CartManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\HmlDemo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class DemoJourneyController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        HmlDemo::requireEnabled();
        if ($request->user() && (string) $request->session()->get('salada_demo_user_id') === (string) $request->user()->id) {
            return redirect()->route('demo.checkout');
        }

        abort_if($request->user(), 403, 'Para testar como comprador, utilize uma sessão sem login real.');

        $user = DB::transaction(function (): User {
            $user = User::query()->create([
                'name' => 'Comprador de demonstração',
                'email' => 'buyer-'.Str::lower((string) Str::ulid()).'@saladamix-demo.test',
                'password' => Str::random(72),
            ]);
            $user->forceFill(['email_verified_at' => now(), 'platform_role' => 'customer'])->save();
            DB::table('customer_addresses')->insert([
                'id' => (string) Str::ulid(), 'user_id' => $user->id,
                'label' => 'Endereço fictício', 'recipient_name' => 'Comprador DEMO',
                'postal_code' => '70000000', 'street' => 'Rua de Demonstração',
                'number' => '100', 'neighborhood' => 'Bairro Fictício',
                'city' => 'Cidade de Teste', 'state' => 'DF', 'is_default' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('salada_demo_user_id', (string) $user->id);
        return redirect()->route('demo.checkout')->with('status', 'Sessão de teste criada, sem dados pessoais.');
    }

    public function checkout(Request $request, CartManager $cart): View
    {
        $user = HmlDemo::buyer($request);
        $snapshot = $cart->read($user);
        $address = DB::table('customer_addresses')->where('user_id', $user->id)->first();
        return view('storefront.demo-checkout', [
            'snapshot' => $snapshot,
            'address' => $address,
            'idempotencyKey' => Str::random(32),
        ]);
    }

    public function create(Request $request, CartManager $cart): RedirectResponse
    {
        $user = HmlDemo::buyer($request);
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $id = DB::transaction(function () use ($user, $data, $cart): string {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('demo_orders')->where('user_id', $user->id)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) return $existing->id;

            $snapshot = $cart->read($user);
            $items = collect($snapshot['items']);
            if ($items->isEmpty() || $items->count() > 20 || $items->contains(fn ($item) => ! $item['available'])) {
                throw ValidationException::withMessages(['cart' => 'Adicione produtos DEMO disponíveis e revise a sacola.']);
            }

            $ids = $items->pluck('offer_id')->all();
            $offers = DB::table('seller_offers')->join('products', 'products.id', '=', 'seller_offers.product_id')
                ->join('sellers', 'sellers.id', '=', 'seller_offers.seller_id')
                ->whereIn('seller_offers.id', $ids)
                ->where('products.slug', 'like', 'demo-%')
                ->where('sellers.trade_name', 'like', '%(DEMO)')
                ->get(['seller_offers.id', 'seller_offers.seller_id', 'seller_offers.sku',
                    'seller_offers.price_cents', 'products.name', 'sellers.trade_name'])->keyBy('id');
            if ($offers->count() !== $items->count()) {
                throw ValidationException::withMessages(['cart' => 'A compra de demonstração admite somente produtos sintéticos.']);
            }

            $address = DB::table('customer_addresses')->where('user_id', $user->id)->first();
            if (! $address) throw ValidationException::withMessages(['address' => 'Cadastre um endereço de teste.']);

            $subtotal = 0;
            $sellerIds = [];
            $rows = [];
            foreach ($items as $line) {
                $offer = $offers->get($line['offer_id']);
                if (! $offer || (int) $offer->price_cents !== (int) $line['offer']['price_cents']) {
                    throw ValidationException::withMessages(['cart' => 'Preço ou disponibilidade alterado. Atualize a sacola.']);
                }
                $q = (int) $line['quantity'];
                $sum = $q * (int) $offer->price_cents;
                $subtotal += $sum;
                $sellerIds[$offer->seller_id] = true;
                $rows[] = [
                    'id' => (string) Str::ulid(),
                    'seller_id' => $offer->seller_id, 'offer_id' => $offer->id,
                    'seller_snapshot' => $offer->trade_name, 'name_snapshot' => $offer->name,
                    'sku_snapshot' => $offer->sku, 'quantity' => $q,
                    'unit_price_cents' => $offer->price_cents, 'line_total_cents' => $sum,
                ];
            }
            // Fixed TEST-only illustration, NOT a carrier quote or shipping promise.
            $shipping = count($sellerIds) * 990;
            $id = (string) Str::ulid();
            DB::table('demo_orders')->insert([
                'id' => $id, 'user_id' => $user->id, 'idempotency_key' => $data['idempotency_key'],
                'status' => 'created_demo', 'payment_status' => 'pending_demo',
                'items_total_cents' => $subtotal, 'shipping_total_cents' => $shipping,
                'grand_total_cents' => $subtotal + $shipping,
                'address_snapshot' => json_encode((array) $address, JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('demo_order_items')->insert(array_map(fn ($row) => [
                ...$row, 'demo_order_id' => $id,
            ], $rows));
            $this->event($id, 'demo.order.created');
            return $id;
        }, 3);

        return redirect()->route('demo.order', $id)->with('status', 'Pedido fictício criado. Nenhum estoque foi reservado e nenhuma cobrança foi iniciada.');
    }

    public function index(Request $request): View
    {
        $user = HmlDemo::buyer($request);
        $orders = DB::table('demo_orders')->where('user_id', $user->id)
            ->orderByDesc('created_at')->paginate(20);
        return view('storefront.demo-orders', compact('orders'));
    }

    public function show(Request $request, string $order): View
    {
        $user = HmlDemo::buyer($request);
        $record = DB::table('demo_orders')->where('user_id', $user->id)->where('id', $order)->first();
        abort_unless($record, 404);
        $items = DB::table('demo_order_items')->where('demo_order_id', $record->id)->get();
        $events = DB::table('demo_order_events')->where('demo_order_id', $record->id)->orderBy('created_at')->orderBy('id')->get();
        return view('storefront.demo-order', compact('record', 'items', 'events'));
    }

    public function transition(Request $request, string $order): RedirectResponse
    {
        $user = HmlDemo::buyer($request);
        $data = $request->validate(['action' => ['required', 'in:pay,prepare,ship,deliver,cancel']]);
        DB::transaction(function () use ($user, $order, $data): void {
            $record = DB::table('demo_orders')->where('id', $order)->where('user_id', $user->id)
                ->lockForUpdate()->first();
            abort_unless($record, 404);
            $map = [
                'pay' => ['created_demo', 'paid_demo', 'simulated_paid'],
                'prepare' => ['paid_demo', 'preparing_demo', 'simulated_paid'],
                'ship' => ['preparing_demo', 'shipped_demo', 'simulated_paid'],
                'deliver' => ['shipped_demo', 'delivered_demo', 'simulated_paid'],
                'cancel' => ['created_demo', 'cancelled_demo', 'cancelled_demo'],
            ];
            [$from, $to, $payment] = $map[$data['action']];
            if ($record->status !== $from) {
                throw ValidationException::withMessages(['action' => 'Etapa inválida para o estado atual do pedido fictício.']);
            }
            DB::table('demo_orders')->where('id', $order)->where('user_id', $user->id)
                ->update(['status' => $to, 'payment_status' => $payment, 'updated_at' => now()]);
            $this->event($order, 'demo.'.$data['action']);
        }, 3);
        return redirect()->route('demo.order', $order)->with('status', 'Etapa fictícia atualizada. Nenhum pagamento, transporte ou estoque real foi acionado.');
    }

    private function event(string $order, string $type): void
    {
        DB::table('demo_order_events')->insert([
            'id' => (string) Str::ulid(), 'demo_order_id' => $order,
            'event_type' => $type, 'created_at' => now(),
        ]);
    }
}
