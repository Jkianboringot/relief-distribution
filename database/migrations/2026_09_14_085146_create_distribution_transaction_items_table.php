<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_transaction_items', function (Blueprint $table) {
            $table->id();

        $table->foreignId('distribution_transaction_id')
    ->constrained('distribution_transactions', indexName: 'dti_transaction_fk')
    ->cascadeOnDelete();

            $table->foreignId('relief_pack_id')->constrained();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(
                ['distribution_transaction_id', 'relief_pack_id'],
                'dti_transaction_pack_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_transaction_items');
    }
};