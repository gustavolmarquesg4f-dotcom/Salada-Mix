<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('password_login_enabled')->default(true)->after('password');
        });

        Schema::create('social_identities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id', 191);
            $table->string('provider_email')->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
            $table->index(['provider_email', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_identities');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_login_enabled');
        });
    }
};

