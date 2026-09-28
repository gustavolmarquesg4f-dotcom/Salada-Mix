<?php

namespace App\Domain\Buyer;

use App\Models\AuditLog;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddressManager
{
    public function list(User $user): array
    {
        return CustomerAddress::query()->where('user_id', $user->id)
            ->orderByDesc('is_default')->orderByDesc('updated_at')->get()->toArray();
    }

    public function create(User $user, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $data): CustomerAddress {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $count = CustomerAddress::query()->where('user_id', $user->id)->count();

            if ($count >= 10) {
                throw ValidationException::withMessages(['label' => 'Limite de 10 endereços por conta atingido.']);
            }

            $isFirst = $count === 0;
            $makeDefault = $isFirst || ($data['is_default'] ?? false);

            if ($makeDefault) {
                CustomerAddress::query()->where('user_id', $user->id)->update(['is_default' => false]);
            }

            $address = CustomerAddress::query()->create(array_merge($data, [
                'user_id' => $user->id,
                'is_default' => $makeDefault,
            ]));
            // Never let caller-supplied tenant information override identity.
            $address->forceFill(['user_id' => $user->id, 'is_default' => $makeDefault])->save();
            $this->audit($user, 'buyer.address.created', $address->id);

            return $address;
        });
    }

    public function update(User $user, string $id, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $id, $data): CustomerAddress {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $address = CustomerAddress::query()->where('user_id', $user->id)->findOrFail($id);
            $makeDefault = $data['is_default'] ?? $address->is_default;

            if ($makeDefault) {
                CustomerAddress::query()->where('user_id', $user->id)
                    ->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $address->fill($data);
            // Cannot unset the only default via arbitrary PATCH.
            $address->is_default = $address->is_default || $makeDefault;
            $address->save();
            $this->audit($user, 'buyer.address.updated', $address->id);

            return $address;
        });
    }

    public function delete(User $user, string $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $address = CustomerAddress::query()->where('user_id', $user->id)->findOrFail($id);
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = CustomerAddress::query()->where('user_id', $user->id)
                    ->orderBy('created_at')->orderBy('id')->first();
                $next?->forceFill(['is_default' => true])->save();
            }

            $this->audit($user, 'buyer.address.deleted', $id);
        });
    }

    private function audit(User $user, string $action, string $id): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => $action,
            'metadata' => ['address_id' => $id],
        ]);
    }
}

