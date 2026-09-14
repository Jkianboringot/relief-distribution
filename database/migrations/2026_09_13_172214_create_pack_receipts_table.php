<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        //         ```php
// // OK, this part of the system/database is responsible for recording
// // relief-pack stock received by the LGU from external sources,
// // such as the Provincial Office or Municipal Government.
// //
// // When we create a PackReceipt record, we are recording that a certain
// // quantity of relief packs was received. This receipt is what allows
// // the system to add that quantity to the LGU's available stock.
// //
// // So, this part of the system is mainly responsible for receiving
// // and recording stock at the LGU level before that stock is later
// // transferred or distributed to the Barangays.
// ```

        Schema::create('pack_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('source_name'); // DSWD, Provincial Office, Municipal LGU, Donation
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