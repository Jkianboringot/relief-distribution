<?php

use App\Http\Controllers\barangayController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Models\barangay;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')->name('home');

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
            Route::get('/', [BeneficiaryController::class, 'index'])
                ->name('index');

            // this is for productVIew
            Route::get('/{beneficiary}/products', [BeneficiaryController::class, 'products'])
                ->name('products');


            Route::get('/create', [BeneficiaryController::class, 'create'])->name('create');
            Route::post('/', [BeneficiaryController::class, 'store'])->name('store');

            // just call ui
            Route::get('/{beneficiary}/edit', [BeneficiaryController::class, 'edit'])->name('edit');
            Route::put('/{beneficiary}', [BeneficiaryController::class, 'update'])->name('update');

            Route::delete('/{beneficiary}', [BeneficiaryController::class, 'delete'])->name('delete');


        }
    );




});

require __DIR__ . '/settings.php';
