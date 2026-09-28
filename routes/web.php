<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Auth Controllers
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;

// Other Controllers
use App\Http\Controllers\PumpUserController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NewSalesController;
use App\Http\Controllers\NozzleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentItemController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\DailyCheckReportController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\PaymentItemEntryController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PartyListController;
use App\Http\Controllers\StockManagementController;

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::get('/register', [UserController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

// registration routes
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::get('/register', [UserController::class, 'showRegisterForm'])->name('register');
// Removed duplicate route for register.store pointing to UserController
// Route::post('/register', [UserController::class, 'register'])->name('register.submit');



// use App\Http\Controllers\RegistrationController; // Commented out as the class is undefined




// use App\Http\Controllers\LoginController; // Commented out as the class is undefined

// Forgot Password routes
Route::get('forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.otp.send');
Route::get('verify-otp', [ForgotPasswordController::class, 'showOtpVerificationPage'])->name('password.otp.verify.page');
Route::post('verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.otp.verify');
Route::get('reset-password', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('password.reset.form');
Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

// Get items for a specific pump (for AJAX requests)
Route::get('/get-pump-items/{pump}', [ItemController::class, 'getPumpItems'])->name('get.pump.items');

// Dashboard route
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    // Get today's total amount and liters from payment entries
    $totalAmount = \App\Models\PaymentItemEntry::whereDate('created_at', Carbon::today())
        ->where('amount', '>', 0)
        ->sum('amount');

    $totalLiters = \App\Models\PaymentItemEntry::whereDate('created_at', Carbon::today())
        ->where('amount', '>', 0)
        ->sum('litre');

    // Get loans data for total outstanding amount
    if ($user->user_type === 'super_admin') {
        $loans = \App\Models\Loan::with(['paymentEntries'])->get();
    } else {
        // For admin and operator users, only show loans for their assigned pumps
        $loans = \App\Models\Loan::join('pumps', 'loans.pump_id', '=', 'pumps.id')
            ->join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
            ->where('pump_users.email', $user->email)
            ->where('pump_users.status', 'active')
            ->where('pumps.status', 'active')
            ->select('loans.*')
            ->with(['paymentEntries', 'pump'])
            ->get();
    }

   return response()
    ->view('index', compact('totalAmount', 'totalLiters', 'loans'))
    ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
    ->header('Pragma', 'no-cache')
    ->header('Expires', '0');
})->name('dashboard');

// User Management Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Pump Users routes
    Route::prefix('pump-users')->group(function () {
        Route::get('/', [PumpUserController::class, 'index'])->name('pump-users.index');
        Route::get('/create', [PumpUserController::class, 'create'])->name('pump-users.create');
        Route::post('/', [PumpUserController::class, 'store'])->name('pump-users.store');
        Route::get('/{pumpUser}/edit', [PumpUserController::class, 'edit'])->name('pump-users.edit');
        Route::put('/{pumpUser}', [PumpUserController::class, 'update'])->name('pump-users.update');
        Route::delete('/{pumpUser}', [PumpUserController::class, 'destroy'])->name('pump-users.destroy');
    });

    // Pumps routes
    Route::prefix('pumps')->middleware(['auth', \App\Http\Middleware\CheckPumpManagementAccess::class])->group(function () {
        Route::get('/', [PumpController::class, 'index'])->name('pumps.index');
        Route::get('/create', [PumpController::class, 'create'])->name('pumps.create');
        Route::post('/', [PumpController::class, 'store'])->name('pumps.store');
        Route::get('/{pump}/edit', [PumpController::class, 'edit'])->name('pumps.edit');
        Route::put('/{pump}', [PumpController::class, 'update'])->name('pumps.update');
        Route::delete('/{pump}', [PumpController::class, 'destroy'])->name('pumps.destroy');
        Route::get('/total-nozzles/{pumpId}', [PumpController::class, 'getTotalNozzles']);
    });

    // Items routes
    Route::resource('items', ItemController::class);
    Route::get('items/by-pump/{pump}', [ItemController::class, 'getItemsByPump'])->name('items.by-pump');
    Route::get('items/{item}/pumps', [ItemController::class, 'getAssignedPumps'])->name('items.assigned-pumps');

    // New Sales routes
    Route::resource('new-sales', NewSalesController::class);
    Route::get('/new-sales/nozzles-by-pump/{pumpId}', [NewSalesController::class, 'getNozzlesByPump'])->name('new-sales.nozzles-by-pump');
    Route::post('/new-sales/check-stock-availability', [NewSalesController::class, 'checkStockAvailability'])->name('new-sales.check-stock-availability');

    // Nozzles routes
    Route::get('/nozzles/available-numbers', [NozzleController::class, 'getAvailableNozzleNumbers'])->name('nozzles.available-numbers');
    Route::get('/nozzles/by-pump/{pumpId}', [NozzleController::class, 'getNozzlesByPump'])->name('nozzles.by-pump');
    Route::get('/nozzles/items-by-pump/{pumpId}', [NozzleController::class, 'getItemsByPump'])->name('nozzles.items-by-pump');
    Route::get('/api/pumps/{pumpId}/all-nozzles', [NozzleController::class, 'getAllNozzlesByPump'])->name('nozzles.all-by-pump');
    Route::get('/nozzles/test-api', function() {
        return response()->json([
            'message' => 'API is working',
            'test_data' => [
                'numbers' => [
                    ['value' => 1, 'text' => '1', 'used' => false],
                    ['value' => 2, 'text' => '2', 'used' => true],
                    ['value' => 3, 'text' => '3', 'used' => false]
                ],
                'total_nozzles' => 3
            ]
        ]);
    })->name('nozzles.test-api');

    // Payment routes
    Route::prefix('payment')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('payment.index');
        Route::post('/', [PaymentController::class, 'store'])->name('payment.store');
        Route::get('/edit/{id}', [PaymentController::class, 'edit'])->name('payment.edit');
        Route::put('/update/{id}', [PaymentController::class, 'update'])->name('payment.update');
        Route::delete('/destroy/{id}', [PaymentController::class, 'destroy'])->name('payment.destroy');
    });

    // Payment Entries routes
    Route::prefix('payment-entries')->group(function () {
        Route::get('/{id}/edit', [PaymentItemEntryController::class, 'edit'])->name('payment-entries.edit');
        Route::put('/{id}', [PaymentItemEntryController::class, 'update'])->name('payment-entries.update');
        Route::delete('/{id}', [PaymentItemEntryController::class, 'destroy'])->name('payment-entries.destroy');
    });

    // Loan Routes
    Route::resource('loans', LoanController::class);
    Route::get('/get-nozzles/{pump}', [LoanController::class, 'getNozzlesByPump'])->name('loans.nozzles-by-pump');
    Route::post('/loans/{loan}/payment', [LoanController::class, 'payment'])->name('loans.payment');

    // Report Routes
    Route::get('report', [ReportController::class, 'index'])->name('report.index');
    Route::get('daily-check-report', [DailyCheckReportController::class, 'index'])->name('daily-check-report.index');
    Route::get('daily-check-report/export', [DailyCheckReportController::class, 'export'])->name('daily-check-report.export');
    Route::get('/pump-sales-report', [ReportController::class, 'pumpSalesReport'])->name('pump.sales.report');

    Route::resource('nozzles', NozzleController::class);

    // Rates routes (merged into ShiftController)
    Route::post('rates', [ShiftController::class, 'storeRate'])->name('rates.store');
    Route::put('rates/{rate}', [ShiftController::class, 'updateRate'])->name('rates.update');
    Route::delete('rates/{rate}', [ShiftController::class, 'destroyRate'])->name('rates.destroy');
    Route::post('rates/bulk-store', [ShiftController::class, 'bulkStoreRate'])->name('rates.bulk-store');

    // Shift routes
    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::put('shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
    Route::post('shifts/bulk-store', [ShiftController::class, 'bulkStore'])->name('shifts.bulk-store');

    // API routes
    Route::prefix('api')->group(function () {
        Route::get('pumps/{pump}/items', [ShiftController::class, 'getPumpItems']);
        Route::get('pumps/{pump}/items-with-rates', [ShiftController::class, 'getPumpItemsWithRates']);
        Route::get('pumps/{pump}/shifts', [ShiftController::class, 'getPumpShifts']);
        Route::get('pumps/{pump}/items/{item}/rate', [ShiftController::class, 'getItemRate']);
        Route::get('all-pumps-with-items-rates', [ShiftController::class, 'getAllPumpsWithItemsAndRates']);
        Route::post('bulk-save-rates', [ShiftController::class, 'bulkSaveRates']);
        Route::get('/nozzles/by-user/{userId}', [NozzleController::class, 'getNozzlesByUser'])->name('nozzles.by-user');
        Route::get('/customers/search', [PaymentItemController::class, 'searchCustomers'])->name('customers.search');

        // Test route
        Route::get('test', function() {
            return response()->json([
                'message' => 'API is working',
                'items' => \App\Models\Item::all()
            ]);
        });
    });

    // Payment Items Routes
    Route::get('/payment-items', [PaymentItemController::class, 'index'])->name('payment-items.index');
    Route::get('/payment-items/{nozzle}', [PaymentItemController::class, 'show'])->name('payment-items.show');
    Route::post('/payment-items', [PaymentItemController::class, 'store'])->name('payment-items.store');
    Route::put('/payment-items/{id}', [PaymentItemController::class, 'update'])->name('payment-items.update');
    Route::delete('/payment-items/{id}', [PaymentItemController::class, 'destroy'])->name('payment-items.destroy');
    Route::post('/sales/close', [SaleController::class, 'close'])->name('sales.close');
    Route::get('/api/payment-data/{nozzleId}', [PaymentItemController::class, 'getPaymentData'])->name('api.payment-data');

    // Vehicle routes
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::get('/vehicles/loan/{loanId}', [VehicleController::class, 'getByLoan'])->name('vehicles.by-loan');

    // Attendance routes
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    });

    Route::get('/customer-credit-sales', [SaleController::class, 'customerCreditSales'])->name('customer-credit-sales');

    // Party List routes
    Route::prefix('party-list')->group(function () {
        Route::get('/', [PartyListController::class, 'index'])->name('party-list.index');
        Route::get('/payment-details/{paymentType}', [PartyListController::class, 'showPaymentTypeDetails'])->name('party-list.payment-details');
        Route::get('/expense-details/{categoryId}', [PartyListController::class, 'showExpenseDetails'])->name('party-list.expense-details');
        Route::get('/expense-details-by-name/{categoryName}', [PartyListController::class, 'showExpenseDetailsByName'])->name('party-list.expense-details-by-name');
        Route::get('/{id}', [PartyListController::class, 'show'])->name('party-list.show');
        Route::post('/', [PartyListController::class, 'store'])->name('party-list.store');
        Route::post('/store-with-loan', [PartyListController::class, 'storeWithLoan'])->name('party-list.store-with-loan');
        Route::post('/store-with-payment', [PartyListController::class, 'storeWithPayment'])->name('party-list.store-with-payment');
        Route::post('/store-with-expense', [PartyListController::class, 'storeWithExpense'])->name('party-list.store-with-expense');
        Route::put('/{id}', [PartyListController::class, 'update'])->name('party-list.update');
        Route::delete('/{id}', [PartyListController::class, 'destroy'])->name('party-list.destroy');
    });

    Route::get('/profile', [UserController::class, 'show'])->name('profile');

    // Registered Users Management Routes
    Route::prefix('registered-users')->group(function () {
        Route::get('/', [RegisteredUserController::class, 'index'])->name('registered-users.index');
        Route::put('/{user}/activate', [RegisteredUserController::class, 'activate'])->name('registered-users.activate');
        Route::put('/{user}/deactivate', [RegisteredUserController::class, 'deactivate'])->name('registered-users.deactivate');
    });

    // Stock Management Routes
    Route::resource('stock-management', StockManagementController::class)->parameters([
        'stock-management' => 'stock'
    ]);

    // Expense Category Management Routes
    Route::resource('categories', \App\Http\Controllers\ExpensesCategoryController::class);
    Route::delete('categories/{category}', [\App\Http\Controllers\ExpensesCategoryController::class, 'destroy'])->name('categories.destroy');

    // Expense Management Routes
    Route::get('expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('expenses/create', [\App\Http\Controllers\ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('expenses/{expense}/edit', [\App\Http\Controllers\ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    
    
});

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login'); // Redirect to the login page after logout
})->name('logout');

// Catch-all route for other pages
Route::get('{any}', [HomeController::class, 'index'])->name('index')->where('any', '.*');



