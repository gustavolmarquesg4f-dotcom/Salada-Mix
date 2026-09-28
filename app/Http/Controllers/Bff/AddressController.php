<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Buyer\AddressManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddressFields;
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
        $data = AddressFields::normalize($request->validate(AddressFields::rules(true)));
        $address = $manager->create($request->user(), $data);

        return response()->json(['data' => $address], 201);
    }

    public function update(Request $request, string $address, AddressManager $manager): JsonResponse
    {
        $rules = AddressFields::rules(true);

        foreach ($rules as $field => &$rule) {
            // PATCH accepts only supplied fields, with the same validations as the HTML form.
            if ($rule[0] === 'required') {
                $rule[0] = 'sometimes';
            }
        }
        unset($rule);

        $data = $request->validate($rules);

        if (array_key_exists('postal_code', $data)) {
            $data['postal_code'] = str_replace('-', '', $data['postal_code']);
        }

        if (array_key_exists('is_default', $data)) {
            $data['is_default'] = (bool) $data['is_default'];
        }

        return response()->json(['data' => $manager->update($request->user(), $address, $data)]);
    }

    public function destroy(Request $request, string $address, AddressManager $manager): JsonResponse
    {
        $manager->delete($request->user(), $address);

        return response()->json(['data' => ['deleted' => true]]);
    }
}

