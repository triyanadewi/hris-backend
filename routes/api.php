<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\CheckClockExportController;
use App\Http\Controllers\CheckClocksController;
use App\Http\Controllers\CheckClockSettingsController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDetailsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LetterFormatsController;
use App\Http\Controllers\LettersController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePlanController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::options('{any}', function () {
    return response()->json(['message' => 'OK'], 200);
})->where('any', '.*');

Route::get('companies', [CompanyController::class, 'index']);
Route::get('/company', [CompanyController::class, 'show']);
Route::get('/position-branch-company', [App\Http\Controllers\DropDownController::class, 'getPositionBranchCompany']);

// --- Rute untuk Admin ---
Route::prefix('admin')->group(function () {
    // Public routes (no authentication required)
    Route::withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->group(function(){
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::get('/google', [AuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
    });

    // Protected routes (auth + role admin)
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Profile management
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        // Authentication management
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        // Admin profile management (phone & photo only)
        Route::get('/admin-profile', [AdminController::class, 'profile']);
        Route::put('/admin-profile', [AdminController::class, 'update']);
        Route::get('/admins', [AdminController::class, 'index']);
        Route::delete('/admins/{id}', [AdminController::class, 'destroy']);
        Route::prefix('checkclocks')->group(function () {
            Route::get('/', [CheckClocksController::class, 'index']);
            Route::get('/filter', [CheckClocksController::class, 'indexWithFilters']);
            Route::get('/employees', [CheckClocksController::class, 'getEmployees']);
            Route::get('/export', [CheckClockExportController::class, 'export']);
            Route::options('/export', function () {
                return response('', 200);
            });
            Route::post('/', [CheckClocksController::class, 'store']);
            Route::get('/{id}', [CheckClocksController::class, 'show']);
            // Additional routes for approval
            Route::put('/{id}/approve', [CheckClocksController::class, 'approve']);
            Route::put('/{id}/reject', [CheckClocksController::class, 'reject']);
        });
        Route::apiResource('work-settings', CheckClockSettingsController::class, [
            'except' => ['edit', 'create'], // Tidak perlu untuk API
        ]);

        // Rute publik lainnya
        Route::get('/package-plans', [PackagePlanController::class, 'index']);
        Route::get('/companies', [CompanyController::class, 'index']); // Mengambil daftar perusahaan
        Route::get('/company', [CompanyController::class, 'show']);    // Mengambil detail perusahaan tertentu (perlu parameter?)
        Route::apiResource('checkouts', CheckoutController::class);    // Rute RESTful untuk checkout

        // Step 1 CompanyProfile
        Route::get('/company/me', [CompanyController::class, 'getAuthenticatedCompany']);

        // Order (Pemesanan & Pembayaran)
        Route::post('/orders', [PackageController::class, 'createOrder']);
        Route::get('/orders/{orderId}', [PackageController::class, 'getOrderWithCompany']);
        Route::post('/orders/{orderId}/confirm-payment', [PackageController::class, 'confirmPayment']);

        // Company Info
        Route::get('/company/fixed', [PackageController::class, 'getCompanyForCheckout']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/companies/{id}', [CompanyController::class, 'show']);

        Route::prefix('packages')->group(function () {
            Route::get('/', [PackageController::class, 'getPackages']);
            Route::get('/company', [PackageController::class, 'getAuthenticatedCompany']);
            Route::post('/order', [PackageController::class, 'createOrder']);
            Route::get('/order/{orderId}', [PackageController::class, 'getOrderWithCompany']);
            Route::put('/order/{orderId}', [PackageController::class, 'updateOrder']);
            Route::post('/order/{orderId}/payment', [PackageController::class, 'createPayment']);
            Route::get('/order/{orderId}/payment/{paymentId}/status', [PackageController::class, 'checkPaymentStatus']);
        });

        Route::prefix('company-details')->group(function () {
            Route::get('/subscription-info', [CompanyDetailsController::class, 'getSubscriptionInfo']);
            Route::get('/branches', [CompanyDetailsController::class, 'getBranches']);
            Route::get('/', [CompanyDetailsController::class, 'getBranches']); // Add this for compatibility
            Route::post('/', [CompanyDetailsController::class, 'store']);
        });

        // Webhook Routes (should be outside any auth middleware)
        Route::post('/webhook/xendit', [PaymentController::class, 'handleWebhook']);

        // Payment success/failure redirect routes (for frontend)
        Route::get('/payment/success', function (Request $request) {
            return redirect(config('app.frontend_url') . '/payment/success?' . http_build_query($request->all()));
        });

        Route::get('/payment/failed', function (Request $request) {
            return redirect(config('app.frontend_url') . '/payment/failed?' . http_build_query($request->all()));
        });

    });
});

// Rute untuk Employee
Route::prefix('employee')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::put('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    Route::delete('/achievements/{id}', [EmployeeController::class, 'removeAchievement']);
    Route::get('/subscription/status', [EmployeeController::class, 'getSubscriptionStatus']);
});

Route::get('/position-branch-company', [App\Http\Controllers\DropDownController::class, 'getPositionBranchCompany']);

Route::prefix('letters')->group(function () {
    Route::get('/', [LettersController::class, 'index']);
    Route::post('/', [LettersController::class, 'store']);
    Route::put('/{id}', [LettersController::class, 'update']);
    Route::delete('/{id}', [LettersController::class, 'destroy']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'API works!']);
});

// Rute untuk mendapatkan informasi perusahaan dari DB (jika ini berbeda dari /company di atas)
Route::get('/company-info', function () {
    $company = DB::table('companies')->first([
        'name',
        'email',
        'head_office_phone',
    ]);
    return response()->json($company);
});

Route::get('/companies', function () {
    $company = DB::table('companies')->first([
        'name',
        'email',
        'head_office_phone',
    ]);
    return response()->json($company);
});

Route::prefix('employee')->group(function () {
    Route::post('/login', [EmployeeAuthController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', fn(Request $request) => $request->user()->load('employee'));

        // Karyawan: Clock setting
        Route::prefix('check-clock-settings')->group(function () {
            Route::get('/', [CheckClockSettingsController::class, 'index']);
            Route::post('/', [CheckClockSettingsController::class, 'store']);
            Route::put('/{id}', [CheckClockSettingsController::class, 'update']);
            Route::delete('/{id}', [CheckClockSettingsController::class, 'destroy']);
        });

        // Karyawan: Clock in/out
        Route::prefix('check-clocks')->group(function () {
            Route::get('/', [CheckClocksController::class, 'index']);
            Route::get('/employees', [CheckClocksController::class, 'getEmployees']);
            Route::post('/', [CheckClocksController::class, 'store']);
            Route::put('/{id}', [CheckClocksController::class, 'update']);
            Route::delete('/{id}', [CheckClocksController::class, 'destroy']);
            Route::get('/report', [CheckClocksController::class, 'report']);
        });

        // Karyawan: Employee data
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index']);
            Route::get('/{id}', [EmployeeController::class, 'show']);
            Route::put('/{id}', [EmployeeController::class, 'update']);
            Route::delete('/{id}', [EmployeeController::class, 'destroy']);
            Route::delete('/achievements/{id}', [EmployeeController::class, 'removeAchievement']);
        });

        // Karyawan: Letters
        Route::prefix('letters')->group(function () {
            Route::get('/', [LettersController::class, 'index']);
            Route::post('/', [LettersController::class, 'store']);
            Route::put('/{id}', [LettersController::class, 'update']);
            Route::delete('/{id}', [LettersController::class, 'destroy']);
        });

        // Karyawan: Letter formats
        Route::prefix('letterFormats')->group(function () {
            Route::get('/', [LetterFormatsController::class, 'index']);
            Route::post('/', [LetterFormatsController::class, 'store']);
            Route::put('/{id}', [LetterFormatsController::class, 'update']);
            Route::delete('/{id}', [LetterFormatsController::class, 'destroy']);
        });
    }); // Akhir dari middleware auth:sanctum untuk user
}); // Akhir dari prefix user
