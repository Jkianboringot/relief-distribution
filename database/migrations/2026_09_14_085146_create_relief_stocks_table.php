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
        Schema::create('relief_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relief_pack_id')->constrained('relief_packs');
            $table->foreignId('pack_receipt_id')->constrained('pack_receipts');
            $table->decimal('quantity',8,2)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relief_stocks');
    }
};
