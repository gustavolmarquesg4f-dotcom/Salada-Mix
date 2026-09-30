<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key', 64);
            $table->string('status', 24)->default('created_demo');
            $table->string('payment_status', 24)->default('pending_demo');
            $table->unsignedBigInteger('items_total_cents');
            $table->unsignedBigInteger('shipping_total_cents');
            $table->unsignedBigInteger('grand_total_cents');
            $table->json('address_snapshot');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('demo_order_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('demo_order_id')->constrained('demo_orders')->cascadeOnDelete();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->foreignUlid('offer_id')->constrained('seller_offers')->restrictOnDelete();
            $table->string('seller_snapshot', 160);
            $table->string('name_snapshot', 180);
            $table->string('sku_snapshot', 80);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_total_cents');
            $table->unique(['demo_order_id', 'offer_id']);
        });

        Schema::create('demo_order_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('demo_order_id')->constrained('demo_orders')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['demo_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_order_events');
        Schema::dropIfExists('demo_order_items');
        Schema::dropIfExists('demo_orders');
    }
};
