<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        // will act as the transfer between lgu and barangay
        Schema::create('distribution_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->string('location')->nullable();
            $table->foreignId('barangay_id')->constrained('barangays');

            $table->enum('status', ['pending', 'ongoing', 'completed'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_schedules');
    }
};