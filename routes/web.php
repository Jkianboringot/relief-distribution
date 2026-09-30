<?php

/*
|--------------------------------------------------------------------------
| ROLE ACCESS NOTES (comments only, no code changed)
|--------------------------------------------------------------------------
| Roles (from the manuscript):
|   ADMIN = LGU/MSWDO Admin
|   BRGY  = Barangay Official
|
| NOTE: Right now the routes only use ['auth', 'verified'].
| There is NO role middleware, so ANY logged-in user (ADMIN or BRGY)
| can technically open every route below. The comments show what each
| role SHOULD be able to do / not do based on the manuscript.
|
| Summary:
|   ADMIN -> CAN: dashboard, users, barangays, beneficiaries (+QR),
|                 relief packs, pack receipts, schedules, delete transactions
|   BRGY  -> CAN: view schedules, scan/verify QR, claim (approve release),
|                 view beneficiaries of own barangay
|            CANNOT: manage users, barangays, relief packs, pack receipts,
|                 create/edit/delete schedules, delete transactions
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\BenificiaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Models\Barangay;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DistributionScheduleController;
use App\Http\Controllers\DistributionTransactionController;
use App\Http\Controllers\PackReceiptController;
use App\Http\Controllers\ReliefPackController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;

Route::redirect('/', 'dashboard')->name('home');

// ROLE: PUBLIC (no login needed, outside auth group)
// ADMIN: can use   | BRGY: can use
// Used when a QR code is scanned to verify a beneficiary.
// WARNING: anyone with the link can open it. Consider putting it behind auth.
Route::get('/scan/{qr_code}', [BenificiaryController::class, 'scan'])
    ->name('beneficiaries.scan');

   Route::middleware('auth')->group(function () {
    Route::get('/scan', [ScanController::class, 'live'])->name('scan.live');
    Route::get('/scan/{qr_code}', [ScanController::class, 'show'])->name('scan.show');
    Route::post('/scan/{qr_code}/confirm', [ScanController::class, 'confirm'])->name('scan.confirm');
});


Route::middleware(['auth', 'verified'])->group(function () {

    // ADMIN: CAN view (full analytics) | BRGY: CAN view (limited to own barangay idealgly)
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // BARANGAYS - ADMIN: CAN (view, create, edit, delete) | BRGY: CANNOT
    // (BRGY may only need to view its own barangay, not manage the list)
    Route::prefix('barangays')->name('barangays.')->group(
        function () {
            Route::get('/', [BarangayController::class, 'index'])
                ->name('index');

            // this is for productVIew
            Route::get('/{barangay}/products', [BarangayController::class, 'products'])
                ->name('products');


            Route::get('/create', [BarangayController::class, 'create'])->name('create');
            Route::post('/', [BarangayController::class, 'store'])->name('store');

            // just call ui
            Route::get('/{barangay}/edit', [BarangayController::class, 'edit'])->name('edit');
            Route::put('/{barangay}', [BarangayController::class, 'update'])->name('update');

            Route::delete('/{barangay}', [BarangayController::class, 'delete'])->name('delete');


        }
    );

    // BENEFICIARIES
    // ADMIN: CAN view, create, edit, delete, generate/view QR
    // BRGY:  CAN view list (verify beneficiaries, own barangay), view QR
    //        CANNOT delete; create/edit only if you allow barangay to help register
    Route::prefix('beneficiaries')->name('beneficiaries.')->group(
        function () {
            Route::get('/', [BenificiaryController::class, 'index'])
                ->name('index');

            // this is for productVIew
            Route::get('/{Benificiary}/products', [BenificiaryController::class, 'products'])
                ->name('products');


            Route::get('/create', [BenificiaryController::class, 'create'])->name('create');
            Route::post('/', [BenificiaryController::class, 'store'])->name('store');

            // just call ui
            Route::get('/{Benificiary}/edit', [BenificiaryController::class, 'edit'])->name('edit');
            Route::put('/{Benificiary}', [BenificiaryController::class, 'update'])->name('update');

            Route::delete('/{Benificiary}', [BenificiaryController::class, 'delete'])->name('delete');
            // inside your auth group
            Route::get('/{beneficiary}/qr', [BenificiaryController::class, 'qr'])
                ->name('qr');

        }
    );



    // USERS - ADMIN: CAN (view, create, edit, delete accounts)
    //         BRGY:  CANNOT (no access to user account management)
    Route::prefix('users')->name('users.')->group(
        function () {
            Route::get('/', [UserController::class, 'index'])
                ->name('index');

            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');

            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');

            Route::delete('/{user}', [UserController::class, 'delete'])->name('delete');
        }
    );


    // PACK RECEIPTS (stock received) - ADMIN: CAN (view, add, edit, delete)
//                                  BRGY:  CANNOT (inventory is managed by LGU/MSWDO)
    Route::prefix('pack-receipts')->name('pack-receipts.')->group(function () {
        Route::get('/', [PackReceiptController::class, 'index'])->name('index');
        Route::post('/', [PackReceiptController::class, 'store'])->name('store');
        Route::get('/create', [PackReceiptController::class, 'create'])->name('create');
        Route::get('/{packReceipt}/edit', [PackReceiptController::class, 'edit'])->name('edit');
        Route::put('/{packReceipt}', [PackReceiptController::class, 'update'])->name('update');
        Route::delete('/{packReceipt}', [PackReceiptController::class, 'destroy'])->name('delete');
    });

    // RELIEF PACKS (inventory) - ADMIN: CAN (view, create, edit, receive stock, view receipts, delete)
    //                            BRGY:  CANNOT manage; at most view stock status (read-only)
    Route::prefix('relief-packs')->name('relief-packs.')->group(

        function () {
            Route::get('/', [ReliefPackController::class, 'index'])->name('index');
            Route::post('/', [ReliefPackController::class, 'store'])->name('store');
            Route::get('/create', [ReliefPackController::class, 'create'])->name('create');
            Route::get('/{reliefPack}/edit', [ReliefPackController::class, 'edit'])->name('edit');
            Route::put('/{reliefPack}', [ReliefPackController::class, 'update'])->name('update');
            Route::post('/{reliefPack}/receive', [ReliefPackController::class, 'receiveStock'])->name('receive');
            Route::get('/{reliefPack}/receipts', [ReliefPackController::class, 'receipts'])->name('receipts');
            Route::delete('/{reliefPack}', [ReliefPackController::class, 'delete'])->name('delete');

        }

    );

    // DISTRIBUTION SCHEDULES
    // ADMIN: CAN view, create, edit, delete, update status, claim
    // BRGY:  CAN view schedules (index/show) and claim (scan QR, approve release)
    //        CANNOT create, edit, delete schedules or change schedule status
  Route::prefix('distribution')->name('distribution.')->group(function () {
    Route::get('/', [DistributionScheduleController::class, 'index'])->name('index');
    Route::get('/create', [DistributionScheduleController::class, 'create'])->name('create');
    Route::post('/', [DistributionScheduleController::class, 'store'])->name('store');
    Route::get('/{schedule}', [DistributionScheduleController::class, 'show'])->name('show');
    Route::get('/{schedule}/edit', [DistributionScheduleController::class, 'edit'])->name('edit');
    Route::put('/{schedule}', [DistributionScheduleController::class, 'update'])->name('update');
    Route::delete('/{schedule}', [DistributionScheduleController::class, 'destroy'])->name('destroy');
    Route::post('/{schedule}/claim', [DistributionTransactionController::class, 'store'])->name('claim');
    Route::patch('/{schedule}/status', [DistributionScheduleController::class, 'updateStatus'])->name('status');

    Route::prefix('{schedule}/allocations')->name('allocations.')->group(function () {
        Route::get('/', [AllocationController::class, 'edit'])->name('edit');
        Route::get('/search', [AllocationController::class, 'search'])->name('search');
        Route::post('/', [AllocationController::class, 'store'])->name('store');
    });

    Route::delete('/allocations/{allocation}', [AllocationController::class, 'destroy'])
        ->name('allocations.destroy');
});


    // DISTRIBUTION TRANSACTIONS - ADMIN: CAN delete (correct/void a record)
    //                             BRGY:  CANNOT delete (keeps audit trail safe)
    Route::prefix('distribution-transactions')->name('distribution-transactions.')->group(
        function () {
            Route::delete('/{transaction}', [DistributionTransactionController::class, 'destroy'])->name('destroy');
        }
    );


    // REPORTS - ADMIN: CAN view all reports, export CSV, print
    //           BRGY:  CAN view distribution report of OWN barangay only (needs role check)
    //                  CANNOT view inventory / all-barangay reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });


});

require __DIR__ . '/settings.php';