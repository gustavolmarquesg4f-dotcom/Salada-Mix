<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use LogicException;

class DisabledPaymentGateway implements PaymentGateway
{
    public function createSellerCheckout(string $suborderId, string $idempotencyKey): array
    {
        throw new LogicException('O checkout ainda não está homologado.');
    }

    public function verifyWebhook(array $headers, string $rawBody): array
    {
        throw new LogicException('Não há webhook financeiro habilitado.');
    }
}

