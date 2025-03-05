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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name')->index();
            $table->text('product_description')->nullable();
            $table->decimal('product_price', 8, 2);
            $table->decimal('product_rating', 3, 2)->default(0); // 0.00 to 5.00
            $table->unsignedInteger('product_stock')->default(0);
            $table->unsignedInteger('product_sold')->default(0);
            $table->unsignedInteger('product_view')->default(0);
            $table->string('product_barcode')->unique()->index(); // unique barcode
            $table->string('slug')->unique()->index();
            $table->json('images');
            $table->boolean('is_active')->default(false);
            $table->foreignId('category_id')->constrained('categories');
            $table->boolean('onSale')->default(false);
            $table->decimal('discount', 8, 2)->default(0); // 0.00 to 100.00
            $table->timestamp('sale_start')->nullable(); // Sale start date
            $table->timestamp('sale_end')->nullable(); // Sale end date
            $table->timestamps();
            $table->softDeletes(); // Allows soft deleting
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
