<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $primaryKey = 'offer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['offer_id', 'seller_id', 'quantity_on_hand', 'quantity_reserved'];

    protected function casts(): array
    {
        return ['quantity_on_hand' => 'integer', 'quantity_reserved' => 'integer'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(SellerOffer::class, 'offer_id');
    }
}

