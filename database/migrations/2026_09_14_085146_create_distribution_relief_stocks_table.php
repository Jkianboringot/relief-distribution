<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distribution_relief_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relief_pack_id')->constrained('relief_packs');
            $table->foreignId('distribution_sched_id')->constrained('distribution_schedules');
            $table->unsignedInteger('entitlement_per_beneficiary')->default(1);
            $table->decimal('quantity', 8, 2)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_relief_stocks');
    }
};
