<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->string('email');
            $table->string('role', 24);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'email', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_invitations');
    }
};
