<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('weight_grams')->nullable()->after('description');
            $table->unsignedSmallInteger('length_cm')->nullable()->after('weight_grams');
            $table->unsignedSmallInteger('width_cm')->nullable()->after('length_cm');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('width_cm');
        });

        Schema::table('demo_orders', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
        });

        Schema::create('demo_shipping_quotes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->char('cart_hash', 64);
            $table->string('service_code', 32);
            $table->string('service_name', 80);
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedSmallInteger('estimated_days');
            $table->char('destination_postal_code', 8);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'seller_id', 'cart_hash']);
            $table->index(['expires_at']);
        });

        Schema::create('demo_order_shipments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('demo_order_id')->constrained('demo_orders')->cascadeOnDelete();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->foreignUlid('quote_id')->constrained('demo_shipping_quotes')->restrictOnDelete();
            $table->string('service_code', 32);
            $table->string('service_name', 80);
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedSmallInteger('estimated_days');
            $table->char('destination_postal_code', 8);
            $table->timestamps();
            $table->unique(['demo_order_id', 'seller_id']);
        });

        Schema::create('demo_stock_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('demo_order_id')->constrained('demo_orders')->cascadeOnDelete();
            $table->ulid('seller_id');
            $table->ulid('offer_id');
            $table->unsignedSmallInteger('quantity');
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->foreign(['offer_id', 'seller_id'])->references(['id', 'seller_id'])
                ->on('seller_offers')->restrictOnDelete();
            $table->unique(['demo_order_id', 'offer_id']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('demo_payment_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('demo_order_id')->constrained('demo_orders')->cascadeOnDelete();
            $table->string('idempotency_key', 64);
            $table->string('provider', 32)->default('hml_sandbox');
            $table->string('provider_reference', 80)->unique();
            $table->string('outcome', 20);
            $table->string('status', 24);
            $table->unsignedBigInteger('amount_cents');
            $table->timestamps();
            $table->unique(['demo_order_id', 'idempotency_key']);
            $table->index(['demo_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_payment_attempts');
        Schema::dropIfExists('demo_stock_reservations');
        Schema::dropIfExists('demo_order_shipments');
        Schema::dropIfExists('demo_shipping_quotes');

        Schema::table('demo_orders', function (Blueprint $table): void {
            $table->dropColumn(['expires_at', 'paid_at']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['weight_grams', 'length_cm', 'width_cm', 'height_cm']);
        });
    }
};
