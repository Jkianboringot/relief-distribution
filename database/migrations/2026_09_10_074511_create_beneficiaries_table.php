<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('barangay_id')
                ->constrained()
                ->cascadeOnDelete();

            // TODO index firstname and lastname if not what is the point of doing it like that
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->date('birthdate');

            $table->string('gender')->default(Gender::Other->value);

            $table->string('address');
            $table->unsignedInteger('household_members');

            $table->string('qr_code')->unique();

            // $table->enum('status', [
            //     'unclaimed',
            //     'claimed',
            // ])->default('unclaimed');

            $table->foreignId('registered_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->unique(
                ['barangay_id', 'first_name', 'last_name', 'birthdate'],
                'beneficiaries_unique_person_per_barangay'
            );

            $table->index(['last_name', 'first_name']); // for searching

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
