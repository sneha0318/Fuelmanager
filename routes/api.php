<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\SaleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_middleware'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::post('/loans', [LoanController::class, 'store']);
Route::get('/loans/{loan}', [LoanController::class, 'show']);
Route::delete('/loans/{loan}', [LoanController::class, 'destroy']);

Route::post('/vehicles', [VehicleController::class, 'store']);
Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);

// Vehicle routes
Route::apiResource('vehicles', VehicleController::class);
Route::get('owners/{owner}/vehicles', [VehicleController::class, 'getByOwner']);
Route::get('vehicles/{vehicle}/fuel-transactions', [VehicleController::class, 'getFuelTransactions']);
Route::get('vehicles/search', [VehicleController::class, 'search']);

Route::get('/customer-credit-sales/{customerId}', [SaleController::class, 'getCustomerCreditSales']); 