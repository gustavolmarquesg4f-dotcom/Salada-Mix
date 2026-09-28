<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('idempotency_key', 64)->nullable()->after('user_id');
            $table->timestamp('expires_at')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('expires_at');
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('order_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignUlid('suborder_id')->nullable()->constrained('suborders')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 48);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });

        // MySQL 8.4 enforces the invariant even when another stock writer bypasses
        // the domain service. SQLite does not support ADD CONSTRAINT in ALTER TABLE.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stock_levels ADD CONSTRAINT chk_stock_reserved_le_on_hand CHECK (quantity_reserved <= quantity_on_hand)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stock_levels DROP CHECK chk_stock_reserved_le_on_hand');
        }

        Schema::dropIfExists('order_events');
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn(['idempotency_key', 'expires_at', 'cancelled_at']);
        });
    }
};

