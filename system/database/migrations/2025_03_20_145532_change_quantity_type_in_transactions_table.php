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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('loading_quantity', 10, 5)->default(0)->change();
            $table->decimal('unloading_quantity', 10, 5)->default(0)->change();
            $table->decimal('unloading_rate', 10, 5)->default(0)->change();
            $table->decimal('loading_rate', 10, 5)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->float('loading_quantity')->default(0)->change();
            $table->float('unloading_quantity')->default(0)->change();
            $table->float('unloading_rate')->default(0)->change();
            $table->float('loading_rate')->default(0)->change();
        });
    }
};
