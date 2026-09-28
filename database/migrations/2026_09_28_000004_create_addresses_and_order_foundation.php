<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 40);
            $table->string('recipient_name', 160);
            $table->string('phone', 20)->nullable();
            $table->char('postal_code', 8);
            $table->string('street', 180);
            $table->string('number', 20);
            $table->string('complement', 120)->nullable();
            $table->string('neighborhood', 120);
            $table->string('city', 120);
            $table->char('state', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('shipping_origins', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->string('label', 80);
            $table->char('postal_code', 8);
            $table->string('street', 180);
            $table->string('number', 20);
            $table->string('complement', 120)->nullable();
            $table->string('neighborhood', 120);
            $table->string('city', 120);
            $table->char('state', 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['seller_id', 'is_active', 'is_default']);
        });

        // Schema only: no route creates paid orders until shipping and PSP have been homologated.
        Schema::create('orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('draft');
            $table->unsignedBigInteger('items_total_cents')->default(0);
            $table->unsignedBigInteger('shipping_total_cents')->nullable();
            $table->unsignedBigInteger('grand_total_cents')->nullable();
            $table->char('currency', 3)->default('BRL');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('suborders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->string('status', 32)->default('created');
            $table->unsignedBigInteger('items_total_cents')->default(0);
            $table->unsignedBigInteger('shipping_cents')->nullable();
            $table->unsignedBigInteger('total_cents')->nullable();
            $table->json('delivery_address_snapshot')->nullable();
            $table->char('currency', 3)->default('BRL');
            $table->timestamps();
            $table->unique(['order_id', 'seller_id']);
            $table->unique(['id', 'seller_id']);
            $table->index(['seller_id', 'status', 'created_at']);
        });

        Schema::create('suborder_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('suborder_id');
            $table->ulid('seller_id');
            $table->ulid('offer_id');
            $table->string('name_snapshot', 180);
            $table->string('sku_snapshot', 80);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_total_cents');
            $table->timestamps();
            $table->foreign(['suborder_id', 'seller_id'])->references(['id', 'seller_id'])
                ->on('suborders')->restrictOnDelete();
            $table->foreign(['offer_id', 'seller_id'])->references(['id', 'seller_id'])
                ->on('seller_offers')->restrictOnDelete();
            $table->unique(['suborder_id', 'offer_id']);
        });

        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('suborder_id');
            $table->ulid('seller_id');
            $table->ulid('offer_id');
            $table->unsignedSmallInteger('quantity');
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->foreign(['suborder_id', 'seller_id'])->references(['id', 'seller_id'])
                ->on('suborders')->restrictOnDelete();
            $table->foreign(['offer_id', 'seller_id'])->references(['id', 'seller_id'])
                ->on('seller_offers')->restrictOnDelete();
            $table->unique(['suborder_id', 'offer_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('suborder_items');
        Schema::dropIfExists('suborders');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shipping_origins');
        Schema::dropIfExists('customer_addresses');
    }
};
