<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SellerOffer extends Model
{
    use HasUlids;

    protected $fillable = [
        'seller_id', 'product_id', 'sku', 'price_cents', 'currency', 'review_status',
    ];

    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(StockLevel::class, 'offer_id');
    }
}

