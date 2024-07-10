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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('requests');
            $table->unsignedBigInteger('amount');
            $table->enum('status', ['pending', 'confirmed', 'declined'])->default('pending');
            $table->string('type');
            $table->string('reference')->nullable();
            $table->string('tnx_id')->nullable();
            $table->string('flw_status')->nullable();
            // $table->unsignedBigInteger('flw_fee')->nullable();
            $table->double('flw_fee')->nullable();
            $table->softDeletes()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
