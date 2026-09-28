<?php

namespace App\Domain\Payments\Contracts;

interface PaymentGateway
{
    /** Retorna identificador e URL hospedada do checkout para um único vendedor. */
    public function createSellerCheckout(string $suborderId, string $idempotencyKey): array;

    /** Somente eventos verificados pelo provedor podem afetar estados financeiros. */
    public function verifyWebhook(array $headers, string $rawBody): array;
}

