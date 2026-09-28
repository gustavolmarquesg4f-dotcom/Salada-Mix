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
            $table->string('label', 60);
            $table->string('recipient', 160);
            $table->char('postal_code', 8);
            $table->string('street', 180);
            $table->string('number', 20);
            $table->string('complement', 120)->nullable();
            $table->string('district', 100);
            $table->string('city', 120);
            $table->char('state', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};

