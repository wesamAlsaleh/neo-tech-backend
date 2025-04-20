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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Keep auto-incrementing ID for internal relationships
            $table->uuid('uuid')->unique(); // For public-facing URLs
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->enum('payment_method', ['cash', 'paypal', 'credit_card', 'debit_card'])->default('cash');
            $table->string('shipping_address');
            $table->timestamps();

            // Index for better query performance
            $table->index('uuid');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
