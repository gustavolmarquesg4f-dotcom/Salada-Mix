<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUlids;

    protected $fillable = [
        'category_id', 'created_by_seller_id', 'name', 'slug', 'description',
        'weight_grams', 'length_cm', 'width_cm', 'height_cm', 'review_status',
    ];

    protected function casts(): array
    {
        return [
            'weight_grams' => 'integer', 'length_cm' => 'integer', 'width_cm' => 'integer', 'height_cm' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position')->orderBy('id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SellerOffer::class);
    }
}

