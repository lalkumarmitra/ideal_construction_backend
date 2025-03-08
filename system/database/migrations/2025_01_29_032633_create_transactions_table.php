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
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('loading_point_id');
            $table->unsignedBigInteger('loading_vehicle_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('loading_point_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('loading_vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->date('loading_date');
            $table->float('loading_rate')->default(0);
            $table->float('loading_quantity')->default(0);

            $table->unsignedBigInteger('unloading_point_id')->nullable();
            $table->unsignedBigInteger('unloading_vehicle_id')->nullable();
            $table->foreign('unloading_point_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('unloading_vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->date('unloading_date')->nullable();
            $table->float('unloading_rate')->nullable();
            $table->float('unloading_quantity')->nullable();

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
