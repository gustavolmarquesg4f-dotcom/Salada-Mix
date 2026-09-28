<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'position']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignUlid('created_by_seller_id')->nullable()->constrained('sellers')->restrictOnDelete();
            $table->string('name', 180);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->string('review_status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'review_status']);
        });

        Schema::create('seller_offers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->string('sku', 80);
            $table->unsignedBigInteger('price_cents');
            $table->char('currency', 3)->default('BRL');
            $table->string('review_status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['seller_id', 'sku']);
            $table->unique(['seller_id', 'product_id']);
            $table->unique(['id', 'seller_id']);
            $table->index(['review_status', 'price_cents']);
        });

        Schema::create('stock_levels', function (Blueprint $table): void {
            $table->ulid('offer_id')->primary();
            $table->ulid('seller_id');
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->timestamps();
            $table->foreign(['offer_id', 'seller_id'])
                ->references(['id', 'seller_id'])->on('seller_offers')->restrictOnDelete();
            $table->index(['seller_id', 'quantity_on_hand']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('offer_id');
            $table->ulid('seller_id');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('quantity_delta');
            $table->string('reason', 40);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign(['offer_id', 'seller_id'])
                ->references(['id', 'seller_id'])->on('seller_offers')->restrictOnDelete();
            $table->index(['seller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('seller_offers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};

