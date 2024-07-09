<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method')->default('naira_wallet');
            $table->foreignId('card_id')->constrained('cards');
            $table->unsignedBigInteger('rate');
            $table->unsignedBigInteger('total_amount');
            $table->json('images')->nullable();
            $table->json('ecodes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'declined']);
            $table->softDeletes()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
