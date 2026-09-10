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
        // REMOVE

        Schema::create('returneds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('barangay_id')->constrained();
            $table->index('barangay_id','returns_barangay_id_index');//even if its small its essential for faster query and filter
//even if its small its essential for faster query and filter go ingredient table and read example 


            $table->index('order_id','returns_order_id_index');

            //this feels wrong , later justify to me why is this not just a pivot table, or why does it needs
            //id(pk) i count just make order_id, barangay_id pk
            //rethink this later
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returneds');
    }
};
