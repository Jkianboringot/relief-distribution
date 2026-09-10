<?php

use App\Enums\barangayType;
use Filament\Livewire\Sidebar;
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
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('district',100)->nullable(); 

            $table->string('code')->unique();
            $table->string('name',75)->unique(); 
           $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
