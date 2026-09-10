<?php

use App\Enums\ProductStatusEnum;
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
        Schema::table('products', function (Blueprint $table) {
            // $table->string('status')->default(value: ProductStatusEnum::IN_STOCK->value);
        });
        //in the future if i wnat to add a new column i can just do this, 
        //aslong as the tbla name is teh same with other table, you can add as many column you wnat
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
