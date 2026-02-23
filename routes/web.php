<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\AppUserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\DeliveryController;

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
/*
Route::get('/', function () {
    return view('dashboard');
});
 */


Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('subcategories', SubcategoryController::class);
    Route::resource('countries', CountryController::class);
    Route::resource('cities', CityController::class);
    Route::resource('areas', AreaController::class);
    Route::resource('admins', AdminController::class);
    Route::resource('roles', RoleController::class);



    Route::prefix('app_users')->name('app_users.')->group(function(){
        Route::get('/', [AppUserController::class, 'index'])->name('index');
        Route::get('create', [AppUserController::class, 'create'])->name('create');
        Route::post('store', [AppUserController::class, 'store'])->name('store');
        Route::get('{app_user}', [AppUserController::class, 'show'])->name('show');
        Route::get('{app_user}/edit', [AppUserController::class, 'edit'])->name('edit');
        Route::put('{app_user}', [AppUserController::class, 'update'])->name('update');
        Route::delete('{app_user}', [AppUserController::class, 'destroy'])->name('destroy');
        Route::get('{app_user}/toggle', [AppUserController::class, 'toggleActive'])->name('toggleActive');
    });


    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', [VendorController::class, 'index'])->name('index');
        Route::get('create', [VendorController::class, 'create'])->name('create');
        Route::post('store', [VendorController::class, 'store'])->name('store');
        Route::get('{vendor}', [VendorController::class, 'show'])->name('show');
        Route::get('{vendor}/edit', [VendorController::class, 'edit'])->name('edit');
        Route::put('{vendor}', [VendorController::class, 'update'])->name('update');
        Route::delete('{vendor}', [VendorController::class, 'destroy'])->name('destroy');
        // ✅ تفعيل أو إلغاء التفعيل
        Route::post('{vendor}/toggle', [VendorController::class, 'toggleStatus'])->name('toggleStatus');
        // ✅ زر القبول
        Route::post('{vendor}/approve', [VendorController::class, 'approve'])->name('approve');
        // ❌ زر الرفض
        Route::post('{vendor}/reject', [VendorController::class, 'reject'])->name('reject');
    });



    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('create', [DeliveryController::class, 'create'])->name('create');
        Route::post('store', [DeliveryController::class, 'store'])->name('store');
        Route::get('{delivery}', [DeliveryController::class, 'show'])->name('show');
        Route::get('{delivery}/edit', [DeliveryController::class, 'edit'])->name('edit');
        Route::put('{delivery}', [DeliveryController::class, 'update'])->name('update');
        Route::delete('{delivery}', [DeliveryController::class, 'destroy'])->name('destroy');

        // toggle active (post)
        Route::post('{delivery}/toggle', [DeliveryController::class, 'toggleStatus'])->name('toggleStatus');
        // approve / reject
        Route::post('{delivery}/approve', [DeliveryController::class, 'approve'])->name('approve');
        Route::post('{delivery}/reject', [DeliveryController::class, 'reject'])->name('reject');
    });



});







require __DIR__.'/auth.php';
