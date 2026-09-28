<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SellerReview extends Model
{
    use HasUlids;

    protected $fillable = ['seller_id', 'actor_user_id', 'decision', 'from_status', 'to_status', 'reason'];
}

