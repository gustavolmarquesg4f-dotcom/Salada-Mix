<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = ['actor_user_id', 'seller_id', 'action', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}

