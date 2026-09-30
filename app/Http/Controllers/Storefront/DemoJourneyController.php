<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Cart\CartManager;
use App\Domain\Orders\HmlSandboxOrderService;
use App\Domain\Payments\HmlSandboxPaymentService;
use App\Domain\Shipping\HmlSandboxQuoteService;
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

        return redirect()->route('demo.checkout')->with('status', 'Sessão de homologação criada sem dados pessoais.');
    }

    public function checkout(
        Request $request,
        CartManager $cart,
        HmlSandboxQuoteService $quotes,
        HmlSandboxOrderService $orders
    ): View {
        $user = HmlDemo::buyer($request);
        $orders->expireDue(100);
        $snapshot = $cart->read($user);
        $address = DB::table('customer_addresses')->where('user_id', $user->id)
            ->orderByDesc('is_default')->orderBy('created_at')->first();
        $shipping = null;
        $shippingError = null;

        if ($snapshot['items'] !== [] && collect($snapshot['items'])->every(fn (array $line): bool => $line['available'])) {
            try {
                $shipping = $quotes->forBuyer($user);
            } catch (ValidationException $exception) {
                $shippingError = collect($exception->errors())->flatten()->first();
            }
        }

        return view('storefront.demo-checkout', [
            'snapshot' => $snapshot,
            'address' => $address,
            'shipping' => $shipping,
            'shippingError' => $shippingError,
            'idempotencyKey' => Str::random(32),
        ]);
    }

    public function create(Request $request, HmlSandboxOrderService $orders): RedirectResponse
    {
        $user = HmlDemo::buyer($request);
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'quotes' => ['required', 'array', 'min:1', 'max:20'],
            'quotes.*' => ['required', 'ulid'],
        ]);

        $id = $orders->create($user, $data['idempotency_key'], $data['quotes']);

        return redirect()->route('demo.order', $id)
            ->with('status', 'Pedido sandbox criado com frete cotado e estoque DEMO reservado por 15 minutos.');
    }

    public function index(Request $request, HmlSandboxOrderService $service): View
    {
        $user = HmlDemo::buyer($request);
        $service->expireDue(100);
        $orders = DB::table('demo_orders')->where('user_id', $user->id)
            ->orderByDesc('created_at')->paginate(20);

        return view('storefront.demo-orders', compact('orders'));
    }

    public function show(Request $request, string $order, HmlSandboxOrderService $orders): View
    {
        $user = HmlDemo::buyer($request);
        $data = $orders->detail($user, $order);

        return view('storefront.demo-order', [
            ...$data,
            'paymentKey' => Str::random(32),
        ]);
    }

    public function payment(
        Request $request,
        string $order,
        HmlSandboxPaymentService $payments
    ): RedirectResponse {
        $user = HmlDemo::buyer($request);
        $data = $request->validate([
            'outcome' => ['required', 'in:approve,decline'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);
        $payments->attempt($user, $order, $data['outcome'], $data['idempotency_key']);

        return redirect()->route('demo.order', $order)->with(
            'status',
            $data['outcome'] === 'approve'
                ? 'Pagamento SANDBOX aprovado; estoque DEMO consumido sem qualquer cobrança externa.'
                : 'Pagamento SANDBOX recusado; a reserva permanece disponível para nova tentativa até expirar.'
        );
    }

    public function transition(
        Request $request,
        string $order,
        HmlSandboxOrderService $orders
    ): RedirectResponse {
        $user = HmlDemo::buyer($request);
        $data = $request->validate(['action' => ['required', 'in:prepare,ship,deliver,cancel']]);

        if ($data['action'] === 'cancel') {
            $orders->cancel($user, $order);
        } else {
            $orders->transition($user, $order, $data['action']);
        }

        return redirect()->route('demo.order', $order)
            ->with('status', 'Etapa SANDBOX atualizada no banco da homologação.');
    }
}
