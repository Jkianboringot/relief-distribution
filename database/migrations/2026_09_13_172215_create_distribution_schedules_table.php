<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->string('location')->nullable();
            $table->string('barangay');
            $table->foreignId('relief_pack_id')->constrained('relief_packs');
            $table->unsignedInteger('planned_quantity'); // boxes allotted for this schedule
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