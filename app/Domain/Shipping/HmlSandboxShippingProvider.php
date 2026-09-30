<?php

namespace App\Domain\Shipping;

use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Support\HmlDemo;
use InvalidArgumentException;

final class HmlSandboxShippingProvider implements ShippingProvider
{
    public function quote(string $sellerId, string $originPostalCode, string $destinationPostalCode, array $packages): array
    {
        HmlDemo::requireEnabled();

        if (! preg_match('/^\d{8}$/', $originPostalCode) || ! preg_match('/^\d{8}$/', $destinationPostalCode) || $packages === []) {
            throw new InvalidArgumentException('Dados insuficientes para a cotação sandbox.');
        }

        $weightGrams = 0;
        $volumeCm3 = 0;

        foreach ($packages as $package) {
            foreach (['weight_grams', 'length_cm', 'width_cm', 'height_cm', 'quantity'] as $field) {
                if (! isset($package[$field]) || (int) $package[$field] < 1) {
                    throw new InvalidArgumentException('Produto sem peso ou dimensões válidas.');
                }
            }
            $quantity = (int) $package['quantity'];
            $weightGrams += (int) $package['weight_grams'] * $quantity;
            $volumeCm3 += (int) $package['length_cm'] * (int) $package['width_cm'] * (int) $package['height_cm'] * $quantity;
        }

        // Fórmula exclusivamente de homologação. Não representa tabela, SLA ou contrato de transportadora.
        $physicalKg = $weightGrams / 1000;
        $volumetricKg = $volumeCm3 / 6000;
        $chargeableKg = max(0.1, $physicalKg, $volumetricKg);
        $originPrefix = (int) substr($originPostalCode, 0, 3);
        $destinationPrefix = (int) substr($destinationPostalCode, 0, 3);
        $zone = min(8, (int) ceil(abs($originPrefix - $destinationPrefix) / 100));
        $economic = 790 + (int) ceil($chargeableKg * 185) + ($zone * 120);
        $express = 1190 + (int) ceil($chargeableKg * 290) + ($zone * 170);

        return [
            ['code' => 'HML_ECO', 'name' => 'Sandbox Econômico', 'amount_cents' => $economic, 'estimated_days' => 5 + $zone],
            ['code' => 'HML_EXP', 'name' => 'Sandbox Expresso', 'amount_cents' => $express, 'estimated_days' => 2 + (int) ceil($zone / 2)],
        ];
    }
}
