<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Orders\DraftOrderService;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderDraftController extends Controller
{
    public function index(Request $request, DraftOrderService $service): JsonResponse
    {
        return response()->json($service->list($request->user()));
    }

    public function store(Request $request, DraftOrderService $service): JsonResponse
    {
        $data = $request->validate([
            'address_id' => ['required', 'ulid'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64',
                'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        return response()->json($service->create(
            $request->user(), $data['address_id'], $data['idempotency_key']
        ), 201);
    }

    public function show(Request $request, string $order, DraftOrderService $service): JsonResponse
    {
        return response()->json($service->detail($request->user(), $order));
    }

    public function cancel(Request $request, string $order, DraftOrderService $service): JsonResponse
    {
        return response()->json($service->cancel($request->user(), $order));
    }

    public function seller(Request $request, Seller $seller, DraftOrderService $service): JsonResponse
    {
        return response()->json($service->forSeller($seller->id, $request->user()->id));
    }

    public function admin(DraftOrderService $service): JsonResponse
    {
        return response()->json($service->adminSnapshot());
    }
}

