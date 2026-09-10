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
        Schema::create('benificiaries', function (Blueprint $table) {
          $table->id();

            $table->foreignId('barangay_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->date('birthdate');

            $table->enum('gender', [
                'male',
                'female',
            ]);

            $table->string('address');
            $table->unsignedInteger('household_members');

            $table->string('qr_code')->unique();

            $table->enum('status', [
                'unclaimed',
                'claimed',
            ])->default('unclaimed');

            $table->foreignId('registered_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index([
                'barangay_id',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benificiaries');
    }
};
