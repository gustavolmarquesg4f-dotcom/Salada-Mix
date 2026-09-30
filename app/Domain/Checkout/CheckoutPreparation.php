<?php

namespace App\Domain\Checkout;

use App\Domain\Shipping\HmlSandboxQuoteService;
use App\Models\User;
use App\Support\HmlDemo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CheckoutPreparation
{
    public function __construct(
        private readonly CheckoutPreview $preview,
        private readonly HmlSandboxQuoteService $sandboxQuotes
    ) {
    }

    /**
     * Prepares the buyer journey without creating a production order or payment.
     * In the isolated HML only, synthetic shipping quotes are persisted for 15
     * minutes so address, package and multi-seller behavior can be homologated.
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

        $sandboxEnabled = HmlDemo::enabled();
        $shippingQuotesReady = false;
        $shippingError = null;
        $shippingTotalCents = null;
        $grandTotalCents = null;

        if ($sandboxEnabled
            && $selected
            && $hasItems
            && $originsReady
            && $preview['unavailable_items_count'] === 0
        ) {
            try {
                $shipping = $this->sandboxQuotes->forBuyer($buyer, $selected['id']);
                $quotesBySeller = collect($shipping['groups'])->keyBy(
                    fn (array $group): string => (string) $group['seller']['id']
                );
                $shippingQuotesReady = true;
                $shippingTotalCents = 0;

                foreach ($groups as &$group) {
                    $quotedGroup = $quotesBySeller->get((string) $group['seller']['id']);
                    $quotes = $quotedGroup['quotes'] ?? [];
                    $selectedQuote = $quotes[0] ?? null;

                    if (! $selectedQuote) {
                        $shippingQuotesReady = false;
                        $group['shipping'] = [
                            'status' => 'sandbox_unavailable',
                            'amount_cents' => null,
                            'estimated_days' => null,
                            'service_name' => null,
                            'quotes' => [],
                        ];
                        continue;
                    }

                    $group['shipping'] = [
                        'status' => 'sandbox_quoted',
                        'amount_cents' => (int) $selectedQuote['amount_cents'],
                        'estimated_days' => (int) $selectedQuote['estimated_days'],
                        'service_name' => $selectedQuote['name'],
                        'quotes' => $quotes,
                    ];
                    $shippingTotalCents += (int) $selectedQuote['amount_cents'];
                }
                unset($group);

                if (! $shippingQuotesReady) {
                    $shippingTotalCents = null;
                }
            } catch (ValidationException $exception) {
                $shippingError = (string) collect($exception->errors())->flatten()->first();
                $shippingQuotesReady = false;
                $shippingTotalCents = null;
            }
        }

        if ($shippingQuotesReady && $shippingTotalCents !== null) {
            $grandTotalCents = (int) $preview['items_subtotal_cents'] + $shippingTotalCents;
        }

        $preview['groups'] = $groups;
        $preview['shipping_total_cents'] = $shippingTotalCents;
        $preview['grand_total_cents'] = $grandTotalCents;

        if ($sandboxEnabled && $shippingQuotesReady) {
            $preview['reason'] = 'Frete SANDBOX disponível apenas para homologação; pagamento real permanece desabilitado.';
        }

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

        if ($sandboxEnabled) {
            if ($shippingError) {
                $issues[] = 'Frete SANDBOX: '.$shippingError;
            } elseif ($hasItems && $selected && $originsReady && ! $shippingQuotesReady) {
                $issues[] = 'A cotação SANDBOX ainda não está disponível para esta sacola.';
            }
        } else {
            $issues[] = 'Cotação real de frete ainda não integrada.';
        }

        $issues[] = 'Pagamento ainda não homologado. Não é possível fechar um pedido real.';

        return [
            'addresses' => $addresses,
            'selected_address' => $selected,
            'preview' => $preview,
            'shipping_mode' => $sandboxEnabled ? 'sandbox' : 'pending',
            'shipping_error' => $shippingError,
            'readiness' => [
                'has_items' => $hasItems,
                'address_selected' => $selected !== null,
                'shipping_origins_ready' => $originsReady,
                'shipping_quotes_ready' => $shippingQuotesReady,
                'payment_ready' => false,
                'can_place_order' => false,
                'issues' => $issues,
            ],
        ];
    }
}
