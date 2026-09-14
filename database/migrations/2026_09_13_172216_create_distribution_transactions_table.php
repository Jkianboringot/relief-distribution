<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_schedule_id')->constrained('distribution_schedules')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries'); // the family head who claimed
            $table->unsignedInteger('quantity_boxes')->default(1); // always 1 box per family head
            $table->foreignId('verified_by')->constrained('users'); // staff who scanned the QR
            $table->timestamp('verification_timestamp')->nullable();
            $table->enum('status', ['claimed', 'pending'])->default('claimed');
            $table->timestamps();

            // A family head can only claim ONCE per schedule
        
    $table->unique(
        ['distribution_schedule_id', 'beneficiary_id'],
        'distribution_schedule_beneficiary_unique'
    );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_transactions');
    }
};