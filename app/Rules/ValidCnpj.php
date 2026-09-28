<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Desde julho de 2026 a Receita Federal emite também CNPJs alfanuméricos.
        $cnpj = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));

        if (strlen($cnpj) !== 14
            || ! preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $cnpj)
            || preg_match('/^(\d)\1{13}$/D', $cnpj)) {
            $fail('Informe um CNPJ válido.');

            return;
        }

        for ($length = 12; $length < 14; $length++) {
            $weights = $length === 12
                ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
                : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

            $sum = 0;

            for ($i = 0; $i < $length; $i++) {
                // Valor ASCII - 48: 0..9 => 0..9; A..Z => 17..42.
                $sum += (ord($cnpj[$i]) - 48) * $weights[$i];
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[$length] !== $digit) {
                $fail('Informe um CNPJ válido.');

                return;
            }
        }
    }
}

