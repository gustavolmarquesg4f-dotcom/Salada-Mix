<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class AddressFields
{
    private const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
        'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
        'SP', 'SE', 'TO',
    ];

    public static function rules(bool $recipient): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:'.($recipient ? '40' : '80')],
            'postal_code' => ['required', 'string', 'regex:/^[0-9]{5}-?[0-9]{3}$/'],
            'street' => ['required', 'string', 'max:180'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2', Rule::in(self::STATES)],
            'is_default' => ['sometimes', 'boolean'],
        ];

        if ($recipient) {
            $rules['recipient_name'] = ['required', 'string', 'max:160'];
            $rules['phone'] = ['nullable', 'string', 'regex:/^[0-9()+.\s-]{8,20}$/'];
        }

        return $rules;
    }

    public static function normalize(array $data): array
    {
        $data['postal_code'] = str_replace('-', '', $data['postal_code']);
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }
}
