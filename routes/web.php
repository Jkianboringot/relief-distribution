<?php

use App\Http\Controllers\BarangayController;
use App\Http\Controllers\BenificiaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Models\barangay;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DistributionScheduleController;
use App\Http\Controllers\DistributionTransactionController;
use App\Http\Controllers\ReliefPackController;

Route::redirect('/', 'dashboard')->name('home');

Route::get('/scan/{qr_code}', [BenificiaryController::class, 'scan'])
    ->name('beneficiaries.scan');


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');


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


    Route::prefix('distribution')->name('distribution.')->group(

        function () {
            Route::get('/', [DistributionScheduleController::class, 'index'])->name('index');
            Route::get('/create', [DistributionScheduleController::class, 'create'])->name('create');
            Route::post('/', [DistributionScheduleController::class, 'store'])->name('store');
            Route::get('/{schedule}', [DistributionScheduleController::class, 'show'])->name('show');
            Route::post('/{schedule}/claim', [DistributionTransactionController::class, 'store'])->name('claim');

        }
    );
    Route::prefix('distribution-transactions')->name('distribution-transactions.')->group(
        function () {
            Route::delete('/{transaction}', [DistributionTransactionController::class, 'destroy'])->name('destroy');
        }
    );
});

require __DIR__ . '/settings.php';
