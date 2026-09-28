<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Buyer\AddressManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request, AddressManager $manager): JsonResponse
    {
        return response()->json(['data' => $manager->list($request->user())]);
    }

    public function store(Request $request, AddressManager $manager): JsonResponse
    {
        $address = $manager->create($request->user(), $this->validated($request, true));

        return response()->json(['data' => $address], 201);
    }

    public function update(Request $request, string $address, AddressManager $manager): JsonResponse
    {
        return response()->json(['data' => $manager->update($request->user(), $address, $this->validated($request, false))]);
    }

    public function destroy(Request $request, string $address, AddressManager $manager): JsonResponse
    {
        $manager->delete($request->user(), $address);

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function validated(Request $request, bool $create): array
    {
        $required = $create ? 'required' : 'sometimes';

        $data = $request->validate([
            'label' => [$required, 'string', 'max:60'],
            'recipient' => [$required, 'string', 'max:160'],
            'postal_code' => [$required, 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'street' => [$required, 'string', 'max:180'],
            'number' => [$required, 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'district' => [$required, 'string', 'max:100'],
            'city' => [$required, 'string', 'max:120'],
            'state' => [$required, 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['postal_code'])) {
            $data['postal_code'] = preg_replace('/\D/', '', $data['postal_code']);
        }

        if (isset($data['state'])) {
            $data['state'] = strtoupper($data['state']);
        }

        return $data;
    }
}

