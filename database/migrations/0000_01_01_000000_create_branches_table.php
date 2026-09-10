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
            $table->string('location',100)->nullable(); 

            // i want to make this unique since they use it alot and lets just levrage that thinking to if they want 
            // search they want it on the name not location
            $table->string('name',75)->unique(); 
            $table->string('barangay_type',40)->default(barangayType::barangay->value);//dont really need this just mean first barangay as main
            // $table->index('location','barangays_location_index');
            // i index this becauase i will use locaton alot for filter , joins , and search espicailly in admin Sidebar
            // and cashier view for barangay_ingredient, barangay_order, etc

            
            $table->timestamps();

            //check what is joining with this by location, becuase from my knowlegde thier should be little 
            //locatoin maybe under 50 so maybe its not even worth making it indexes
            //same goes for barangay_type
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
