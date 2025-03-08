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
            $table->float('transport_expense')->default(0)->after('txn_type');

            $table->unsignedBigInteger('loading_driver_id')->nullable()->after('transport_expense');
            $table->unsignedBigInteger('unloading_driver_id')->nullable()->after('loading_driver_id');
            $table->unsignedBigInteger('recorder_id')->nullable()->after('unloading_driver_id');
            $table->unsignedBigInteger('updater_id')->nullable()->after('recorder_id');
            $table->foreign('loading_driver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('unloading_driver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recorder_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updater_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transport_expense','loading_driver_id','unloading_driver_id','recorder_id','updater_id']);
        });
    }
};
