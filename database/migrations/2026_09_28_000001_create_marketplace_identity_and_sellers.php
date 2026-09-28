<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('platform_role', 24)->default('customer')->index();
        });

        Schema::create('sellers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('legal_name', 200);
            $table->string('trade_name', 160);
            $table->char('cnpj', 14)->unique();
            $table->string('contact_email');
            $table->string('status', 24)->default('submitted')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seller_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 24);
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->unique(['seller_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('seller_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('sellers')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 24);
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $table->string('action', 128);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['seller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('seller_reviews');
        Schema::dropIfExists('seller_memberships');
        Schema::dropIfExists('sellers');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('platform_role');
        });
    }
};

