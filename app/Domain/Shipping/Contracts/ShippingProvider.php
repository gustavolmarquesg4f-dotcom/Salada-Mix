<?php

namespace App\Domain\Shipping\Contracts;

interface ShippingProvider
{
    /** Cotação vinculada à empresa, origem, destino e snapshot dos itens. */
    public function quote(string $sellerId, string $originPostalCode, string $destinationPostalCode, array $packages): array;
}

