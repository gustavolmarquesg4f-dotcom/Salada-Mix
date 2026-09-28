<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialIdentity extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'provider', 'provider_user_id', 'provider_email', 'avatar_url', 'last_login_at',
    ];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

