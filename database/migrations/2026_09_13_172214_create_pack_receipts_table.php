<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pack_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relief_pack_id')->constrained('relief_packs');
            $table->string('source_name'); // DSWD, Provincial Office, Municipal LGU, Donation
            $table->unsignedInteger('quantity_received');
            $table->date('date_received');
            $table->foreignId('received_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_receipts');
    }
};