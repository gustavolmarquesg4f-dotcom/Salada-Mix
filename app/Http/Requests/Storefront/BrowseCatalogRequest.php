<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrowseCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->query('q'))) {
            $this->merge(['q' => trim(preg_replace('/\s+/u', ' ', $this->query('q')))]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9-]+$/'],
            'seller' => ['nullable', 'string', 'size:26', 'alpha_num'],
            'sort' => ['nullable', Rule::in(['recent', 'price_asc', 'price_desc'])],
            'min_price' => ['nullable', 'string', 'regex:/^\d{1,7}(?:[.,]\d{1,2})?$/'],
            'max_price' => ['nullable', 'string', 'regex:/^\d{1,7}(?:[.,]\d{1,2})?$/'],
        ];
    }

    public function filters(): array
    {
        $filters = $this->validated();
        $min = $this->toCents($filters['min_price'] ?? null);
        $max = $this->toCents($filters['max_price'] ?? null);

        if ($min !== null && $max !== null && $min > $max) {
            throw ValidationException::withMessages([
                'max_price' => 'O valor máximo deve ser maior ou igual ao valor mínimo.',
            ]);
        }

        $filters['min_cents'] = $min;
        $filters['max_cents'] = $max;

        return $filters;
    }

    private function toCents(?string $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $parts = explode('.', str_replace(',', '.', $amount), 2);

        return ((int) $parts[0] * 100)
            + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
