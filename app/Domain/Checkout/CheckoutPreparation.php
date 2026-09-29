<?php

namespace App\Domain\Checkout;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CheckoutPreparation
{
    public function __construct(private readonly CheckoutPreview $preview)
    {
    }

    /**
     * Read-only preparation. Address selection is request-scoped; it does not
     * create an order, reserve stock, request shipping or start a payment.
     */
    public function forBuyer(User $buyer, ?string $addressId = null): array
    {
        $addresses = DB::table('customer_addresses')
            ->where('user_id', $buyer->id)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get([
                'id', 'label', 'recipient_name', 'postal_code', 'street',
                'number', 'complement', 'neighborhood', 'city', 'state', 'is_default',
            ])
            ->map(fn (object $address): array => (array) $address)
            ->all();

        $selected = null;

        if ($addressId !== null) {
            foreach ($addresses as $address) {
                if ($address['id'] === $addressId) {
                    $selected = $address;
                    break;
                }
            }

            abort_unless($selected, 404);
        } else {
            $selected = $addresses[0] ?? null;
        }

        $preview = $this->preview->forBuyer($buyer);
        $groups = $preview['groups'];
        $hasItems = count($groups) > 0;
        $originsReady = $hasItems && collect($groups)->every(
            fn (array $group): bool => $group['shipping']['status'] !== 'origin_not_configured'
        );

        $issues = [];

        if (! $hasItems) {
            $issues[] = 'Adicione itens disponíveis à sacola.';
        }

        if ($preview['unavailable_items_count'] > 0) {
            $issues[] = 'Revise os itens indisponíveis na sacola.';
        }

        if (! $selected) {
            $issues[] = 'Cadastre um endereço de entrega.';
        }

        if ($hasItems && ! $originsReady) {
            $issues[] = 'Ao menos um vendedor ainda precisa configurar a origem de envio.';
        }

        $issues[] = 'Cotação real de frete ainda não integrada.';
        $issues[] = 'Pagamento ainda não homologado. Não é possível fechar um pedido.';

        return [
            'addresses' => $addresses,
            'selected_address' => $selected,
            'preview' => $preview,
            'readiness' => [
                'has_items' => $hasItems,
                'address_selected' => $selected !== null,
                'shipping_origins_ready' => $originsReady,
                'shipping_quotes_ready' => false,
                'payment_ready' => false,
                'can_place_order' => false,
                'issues' => $issues,
            ],
        ];
    }
}
