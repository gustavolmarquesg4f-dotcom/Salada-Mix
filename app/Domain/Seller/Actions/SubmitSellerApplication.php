<?php

namespace App\Domain\Seller\Actions;

use App\Models\AuditLog;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitSellerApplication
{
    public function execute(User $owner, array $data): Seller
    {
        $cnpj = preg_replace('/\D/', '', $data['cnpj']);

        if (Seller::query()->where('cnpj', $cnpj)->exists()) {
            throw ValidationException::withMessages(['cnpj' => 'CNPJ já cadastrado.']);
        }

        return DB::transaction(function () use ($owner, $data, $cnpj): Seller {
            $seller = Seller::query()->create([
                'owner_user_id' => $owner->id,
                'legal_name' => $data['legal_name'],
                'trade_name' => $data['trade_name'],
                'cnpj' => $cnpj,
                'contact_email' => $data['contact_email'],
                'status' => 'submitted',
            ]);

            SellerMembership::query()->create([
                'seller_id' => $seller->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'status' => 'active',
            ]);

            AuditLog::query()->create([
                'actor_user_id' => $owner->id,
                'seller_id' => $seller->id,
                'action' => 'seller.application.submitted',
                'metadata' => ['status' => 'submitted'],
            ]);

            return $seller;
        });
    }
}

