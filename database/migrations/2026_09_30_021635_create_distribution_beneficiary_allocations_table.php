<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_beneficiary_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('distribution_schedule_id')
                ->constrained('distribution_schedules', 'id', 'dba_schedule_id_foreign')
                ->cascadeOnDelete();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries', 'id', 'dba_beneficiary_id_foreign')
                ->cascadeOnDelete();

            $table->foreignId('relief_pack_id')->constrained();
            $table->unsignedInteger('quantity'); // overrides the pack's default entitlement, for THIS beneficiary only
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['distribution_schedule_id', 'beneficiary_id', 'relief_pack_id'],
                'dba_sched_ben_pack_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_beneficiary_allocations');
    }
};