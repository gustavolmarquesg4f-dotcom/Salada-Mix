<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    use HasUlids;

    protected $table = 'product_media';
    protected $fillable = ['product_id', 'seller_id', 'uploaded_by', 'path', 'mime', 'alt', 'position'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
