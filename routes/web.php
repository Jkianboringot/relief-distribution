<?php

/*
|--------------------------------------------------------------------------
| PERMISSION ACCESS (enforced by `can:` middleware -> Spatie permissions)
|--------------------------------------------------------------------------
| Permissions and who has them are defined in RolePermissionSeeder.
|
|   lgustaff (ADMIN = LGU/MSWDO Admin) -> all permissions
|   barangayofficial (BRGY)            -> distributions.view, distributions.create,
|                                         distributions.status, beneficiaries.view,
|                                         analytics.dashboard, reports.view,
|                                         verify.Benificiary, approval.stockverifcation
|
| BRGY cannot: users, barangays, inventory (relief packs / pack receipts),
|              edit/delete schedules, allocations, delete transactions, export reports.
|
| TODO (must be done inside the controllers, `can:` cannot do it):
|   BRGY should only see data of its OWN barangay in: dashboard, beneficiaries,
|   schedules, reports (BRGY must not see inventory / all-barangay reports).
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\BenificiaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DistributionScheduleController;
use App\Http\Controllers\DistributionTransactionController;
use App\Http\Controllers\PackReceiptController;
use App\Http\Controllers\ReliefPackController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;

Route::redirect('/', 'dashboard')->name('home');

// NOTE: the old PUBLIC route `GET /scan/{qr_code}` (beneficiaries.scan) was removed.
// It had the same URL as scan.show below and always won, so the login-protected
// ScanController@show never ran and beneficiary data was open to anyone with the link.
// The URL is unchanged, so already printed QR codes still work (they now ask for login).

Route::middleware(['auth', 'verified'])->group(function () {

    // DASHBOARD - ADMIN + BRGY (BRGY limited to own barangay -> controller)
    Route::middleware('can:analytics.dashboard')->get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // SCAN / VERIFY QR - ADMIN + BRGY
    Route::middleware('can:verify.Benificiary')->get('/scan', [ScanController::class, 'live'])
        ->name('scan.live');
    Route::middleware('can:verify.Benificiary')->get('/scan/{qr_code}', [ScanController::class, 'show'])
        ->name('scan.show');
    Route::middleware('can:approval.stockverifcation')->post('/scan/{qr_code}/confirm', [ScanController::class, 'confirm'])
        ->name('scan.confirm');

    // BARANGAYS - ADMIN only
    Route::prefix('barangays')->name('barangays.')->group(
        function () {
            Route::middleware('can:barangays.view')->get('/', [BarangayController::class, 'index'])
                ->name('index');

            // this is for productVIew
            Route::middleware('can:barangays.view')->get('/{barangay}/products', [BarangayController::class, 'products'])
                ->name('products');

            Route::middleware('can:barangays.create')->get('/create', [BarangayController::class, 'create'])
                ->name('create');
            Route::middleware('can:barangays.create')->post('/', [BarangayController::class, 'store'])
                ->name('store');

            // just call ui
            Route::middleware('can:barangays.update')->get('/{barangay}/edit', [BarangayController::class, 'edit'])
                ->name('edit');
            Route::middleware('can:barangays.update')->put('/{barangay}', [BarangayController::class, 'update'])
                ->name('update');

            Route::middleware('can:barangays.delete')->delete('/{barangay}', [BarangayController::class, 'delete'])
                ->name('delete');
        }
    );

    // BENEFICIARIES
    // ADMIN: view, create, edit, delete, QR
    // BRGY:  view list, view products, view QR (read-only; own barangay -> controller)
    Route::prefix('beneficiaries')->name('beneficiaries.')->group(
        function () {
            Route::middleware('can:beneficiaries.view')->get('/', [BenificiaryController::class, 'index'])
                ->name('index');

            // this is for productVIew
            Route::middleware('can:beneficiaries.view')->get('/{Benificiary}/products', [BenificiaryController::class, 'products'])
                ->name('products');

            Route::middleware('can:beneficiaries.create')->get('/create', [BenificiaryController::class, 'create'])
                ->name('create');
            Route::middleware('can:beneficiaries.create')->post('/', [BenificiaryController::class, 'store'])
                ->name('store');

            // just call ui
            Route::middleware('can:beneficiaries.update')->get('/{Benificiary}/edit', [BenificiaryController::class, 'edit'])
                ->name('edit');
            Route::middleware('can:beneficiaries.update')->put('/{Benificiary}', [BenificiaryController::class, 'update'])
                ->name('update');

            Route::middleware('can:beneficiaries.delete')->delete('/{Benificiary}', [BenificiaryController::class, 'delete'])
                ->name('delete');

            Route::middleware('can:beneficiaries.view')->get('/{beneficiary}/qr', [BenificiaryController::class, 'qr'])
                ->name('qr');
        }
    );

    // USERS - ADMIN only (account management)
    Route::middleware('can:users.route')->prefix('users')->name('users.')->group(
        function () {
            Route::middleware('can:users.view')->get('/', [UserController::class, 'index'])
                ->name('index');

            Route::middleware('can:users.create')->get('/create', [UserController::class, 'create'])
                ->name('create');
            Route::middleware('can:users.create')->post('/', [UserController::class, 'store'])
                ->name('store');

            Route::middleware('can:users.update')->get('/{user}/edit', [UserController::class, 'edit'])
                ->name('edit');
            Route::middleware('can:users.update')->put('/{user}', [UserController::class, 'update'])
                ->name('update');

            Route::middleware('can:users.delete')->delete('/{user}', [UserController::class, 'delete'])
                ->name('delete');
        }
    );

    // PACK RECEIPTS (stock received) - ADMIN only (inventory is managed by LGU/MSWDO)
    Route::prefix('pack-receipts')->name('pack-receipts.')->group(function () {
        Route::middleware('can:inventory.view')->get('/', [PackReceiptController::class, 'index'])
            ->name('index');
        Route::middleware('can:inventory.create')->post('/', [PackReceiptController::class, 'store'])
            ->name('store');
        Route::middleware('can:inventory.create')->get('/create', [PackReceiptController::class, 'create'])
            ->name('create');
        Route::middleware('can:inventory.update')->get('/{packReceipt}/edit', [PackReceiptController::class, 'edit'])
            ->name('edit');
        Route::middleware('can:inventory.update')->put('/{packReceipt}', [PackReceiptController::class, 'update'])
            ->name('update');
        Route::middleware('can:inventory.delete')->delete('/{packReceipt}', [PackReceiptController::class, 'destroy'])
            ->name('delete');
    });

    // RELIEF PACKS (inventory) - ADMIN only
    Route::prefix('relief-packs')->name('relief-packs.')->group(
        function () {
            Route::middleware('can:inventory.view')->get('/', [ReliefPackController::class, 'index'])
                ->name('index');
            Route::middleware('can:inventory.create')->post('/', [ReliefPackController::class, 'store'])
                ->name('store');
            Route::middleware('can:inventory.create')->get('/create', [ReliefPackController::class, 'create'])
                ->name('create');
            Route::middleware('can:inventory.update')->get('/{reliefPack}/edit', [ReliefPackController::class, 'edit'])
                ->name('edit');
            Route::middleware('can:inventory.update')->put('/{reliefPack}', [ReliefPackController::class, 'update'])
                ->name('update');
            Route::middleware('can:inventory.update')->post('/{reliefPack}/receive', [ReliefPackController::class, 'receiveStock'])
                ->name('receive');
            Route::middleware('can:inventory.view')->get('/{reliefPack}/receipts', [ReliefPackController::class, 'receipts'])
                ->name('receipts');
            Route::middleware('can:inventory.delete')->delete('/{reliefPack}', [ReliefPackController::class, 'delete'])
                ->name('delete');
        }
    );

    // DISTRIBUTION SCHEDULES
    // ADMIN: view, create, edit, delete, update status, allocations, claim
    // BRGY:  view, create, change status (to "ongoing" only), claim (scan QR, approve release)
    //        CANNOT edit, delete, or manage allocations
    // ORDER MATTERS: `/create` must stay above `/{schedule}`.
    Route::prefix('distribution')->name('distribution.')->group(function () {
        Route::middleware('can:distributions.view')->get('/', [DistributionScheduleController::class, 'index'])
            ->name('index');

        Route::middleware('can:distributions.create')->get('/create', [DistributionScheduleController::class, 'create'])
            ->name('create');
        Route::middleware('can:distributions.create')->post('/', [DistributionScheduleController::class, 'store'])
            ->name('store');

        Route::middleware('can:distributions.view')->get('/{schedule}', [DistributionScheduleController::class, 'show'])
            ->name('show');

        Route::middleware('can:distributions.update')->get('/{schedule}/edit', [DistributionScheduleController::class, 'edit'])
            ->name('edit');
        Route::middleware('can:distributions.update')->put('/{schedule}', [DistributionScheduleController::class, 'update'])
            ->name('update');
        Route::middleware('can:distributions.delete')->delete('/{schedule}', [DistributionScheduleController::class, 'destroy'])
            ->name('destroy');

        // claim (approve release) - ADMIN + BRGY
        Route::middleware('can:approval.stockverifcation')->post('/{schedule}/claim', [DistributionTransactionController::class, 'store'])
            ->name('claim');

        // change schedule status - ADMIN + BRGY
        // BRGY may only set "ongoing" -> this limit is enforced in DistributionScheduleController@updateStatus
        Route::middleware('can:distributions.status')->patch('/{schedule}/status', [DistributionScheduleController::class, 'updateStatus'])
            ->name('status');

        // Relief allocation - ADMIN only (treated as part of editing schedules)
        Route::prefix('{schedule}/allocations')->name('allocations.')->group(function () {
            Route::middleware('can:distributions.update')->get('/', [AllocationController::class, 'edit'])
                ->name('edit');
            Route::middleware('can:distributions.update')->get('/search', [AllocationController::class, 'search'])
                ->name('search');
            Route::middleware('can:distributions.update')->post('/', [AllocationController::class, 'store'])
                ->name('store');
        });

        Route::middleware('can:distributions.update')->delete('/allocations/{allocation}', [AllocationController::class, 'destroy'])
            ->name('allocations.destroy');
    });

    // DISTRIBUTION TRANSACTIONS - ADMIN only (correct/void a record, keeps audit trail safe)
    Route::prefix('distribution-transactions')->name('distribution-transactions.')->group(
        function () {
            Route::middleware('can:distributions.delete')->delete('/{transaction}', [DistributionTransactionController::class, 'destroy'])
                ->name('destroy');
        }
    );

    // REPORTS
    // ADMIN: view all reports, export CSV, print
    // BRGY:  view distribution report of OWN barangay only; CANNOT export
    // TODO: ReportController must limit BRGY to the distribution report of its OWN barangay
    //       (no inventory / all-barangay reports for BRGY).
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::middleware('can:reports.view')->get('/', [ReportController::class, 'index'])
            ->name('index');
        Route::middleware('can:reports.download')->get('/export', [ReportController::class, 'export'])
            ->name('export');
    });

});

require __DIR__ . '/settings.php';