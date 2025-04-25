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
        Schema::create('system_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('log_type', ['error', 'info', 'debug', 'warning'])->comment('Type of log');
            $table->text('message')->comment('Log message');
            $table->json('context')->nullable()->comment('Additional context for the log');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID of the user associated with the log');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->integer('status_code')->nullable()->comment('Related HTTP or system status code');
            $table->timestamps();

            $table->index('log_type');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_performance_logs');
    }
};
