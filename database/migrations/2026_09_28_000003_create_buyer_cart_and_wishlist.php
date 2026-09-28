<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('offer_id')->constrained('seller_offers')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
            $table->primary(['user_id', 'offer_id']);
        });

        Schema::create('wishlist_items', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('offer_id')->constrained('seller_offers')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('cart_items');
    }
};

