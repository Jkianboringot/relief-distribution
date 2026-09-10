<?php

use App\Http\Controllers\barangayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Models\barangay;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route::prefix('products')->name('products.')->group(
    //     function () {
    //         Route::get('/', [ProductController::class, 'index'])->name('index');
    //         Route::get('/create', [ProductController::class, 'create'])->name('create');
    //         Route::post('/', [ProductController::class, 'store'])->name('store');
    //         Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');

    //         Route::delete('/{product}', [ProductController::class, 'delete'])->name('delete');
    //         Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    //     }
    // );

    // Route::prefix('inventories')->name('inventories.')->group(
    //     function () {
    //         Route::get('/', [InventoryController::class, 'index'])
    //             ->name('index');

    //         Route::get('/create-in', [InventoryController::class, 'createIn'])
    //             ->name('create-in');
    //         Route::post('/in', [InventoryController::class, 'storeIn'])
    //             ->name('store-in');

    //         Route::get('/create-out', [InventoryController::class, 'createOut'])
    //             ->name('create-out');
    //         Route::post('/out', [InventoryController::class, 'storeOut'])
    //             ->name('store-out');

    //         // just call ui
    //         Route::get('/{inventory}/edit-in', [InventoryController::class, 'editIn'])->name('edit-in');
    //         Route::put('/{inventory}/in', [InventoryController::class, 'inUpdate'])->name('in-update');

    //         Route::get('/{inventory}/edit-out', [InventoryController::class, 'editOut'])->name('edit-out');
    //         Route::put('/{inventory}/out', [InventoryController::class, 'updateOut'])->name('update-out');

    //         Route::delete('/{inventory}', [InventoryController::class, 'delete'])->name('delete');

    //     }
    // );

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

    // Route::prefix('sales')->name('sales.')->group(
    //     function () {
    //         Route::get('/', [SaleController::class, 'index'])
    //             ->name('index');

    //     }
    // );




});

require __DIR__ . '/settings.php';
