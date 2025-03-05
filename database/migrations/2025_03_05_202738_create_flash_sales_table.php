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
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('flash_sale_duration')->default(24); // Duration = endDateTime - startDateTime
            $table->timestamp('start_date')->nullable(); // Sale start date
            $table->timestamp('end_date')->nullable(); // Sale end date
            $table->boolean('is_active')->default(false);
            $table->json('products'); // List of products on sale (product_id, discount)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
