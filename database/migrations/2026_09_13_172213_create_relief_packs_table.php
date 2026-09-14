<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relief_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Family Food Pack"
            $table->text('description')->nullable();
            $table->unsignedInteger('current_stock')->default(0); // boxes on hand
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relief_packs');
    }
};