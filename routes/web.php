<?php
use App\Exports\OrdersExport;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AppSettingController::class, 'openApp'])->name('admin');
    Route::get('/admin', [AppSettingController::class, 'openApp'])->name('admin');

    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::prefix('admin')->name('admin.')->group(function () {
        // Admin only
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('users', UserController::class);
            Route::get('app-setting', [AppSettingController::class, 'index'])->name('app-setting.index');
            Route::put('app-setting', [AppSettingController::class, 'update'])->name('app-setting.store');
            Route::resource('roles', \App\Http\Controllers\RoleController::class);
            Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
        });

        Route::resource('banners', BannerController::class);
        Route::patch('/banners/{id}/toggle-status', [BannerController::class, 'toggleStatus'])->name('banners.toggleStatus');
        Route::resource('categories', CategoryController::class);
        Route::post('categories/ajax-store', [CategoryController::class, 'ajaxStore'])->name('categories.ajaxStore');
        Route::resource('articles', ArticleController::class);
        Route::post('uploadCKEditor', [UploadController::class, 'uploadCKEditor'])->name('uploadCKEditor');
        Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

        // Profile routes
        Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    });
});

Route::get('/login', function () {
    return view('login');
})->name('login.index');
Route::get('/signup', [LoginController::class, 'showSignupForm'])->name('signup.form');
Route::post('/signup', [LoginController::class, 'signup'])->name('signup');

Route::post('/login', [LoginController::class, 'login'])->name('login');


