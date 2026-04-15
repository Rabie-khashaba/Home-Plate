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
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PaymobRedirectController;




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


    Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');



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
            Route::get('delivery-fees', [AreaController::class, 'deliveryFees'])->name('delivery_fees.index');
            Route::post('delivery-fees/{area}', [AreaController::class, 'updateFee'])->name('delivery_fees.update');
            Route::resource('admins', AdminController::class);
            Route::resource('roles', RoleController::class);


            Route::get('general-settings', [GeneralSettingController::class, 'edit'])->name('general_settings.edit');
            Route::put('general-settings', [GeneralSettingController::class, 'update'])->name('general_settings.update');



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


    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('create', [ItemController::class, 'create'])->name('create');
        Route::post('store', [ItemController::class, 'store'])->name('store');
        Route::get('{item}', [ItemController::class, 'show'])->name('show');
        Route::get('{item}/edit', [ItemController::class, 'edit'])->name('edit');
        Route::put('{item}', [ItemController::class, 'update'])->name('update');
        Route::delete('{item}', [ItemController::class, 'destroy'])->name('destroy');
        Route::post('{item}/approve', [ItemController::class, 'approve'])->name('approve');
        Route::post('{item}/reject', [ItemController::class, 'reject'])->name('reject');
    });

     Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('create', [NotificationController::class, 'create'])->name('create');
        Route::post('store', [NotificationController::class, 'store'])->name('store');
        Route::delete('{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::post('{notification}/send-now', [NotificationController::class, 'sendNow'])->name('send-now');
    });

     Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
     Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
     Route::get('payments/paymob/response', [PaymobRedirectController::class, 'response'])->name('payments.paymob.response');
     Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

     Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('{type}/{id}', [WalletController::class, 'show'])->name('show');
        Route::post('{type}/{id}/adjust', [WalletController::class, 'adjust'])->name('adjust');
    });

     Route::prefix('activity-logs')->name('activity_logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::post('clear', [ActivityLogController::class, 'clear'])->name('clear');
    });

     Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('{ticket}', [SupportTicketController::class, 'show'])->name('show');
        Route::post('{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::delete('{ticket}', [SupportTicketController::class, 'destroy'])->name('destroy');
    });

     Route::prefix('ratings')->name('ratings.')->group(function () {
        Route::get('/', [RatingController::class, 'index'])->name('index');
        Route::delete('{rating}', [RatingController::class, 'destroy'])->name('destroy');
    });

     Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::get('create', [BannerController::class, 'create'])->name('create');
        Route::post('store', [BannerController::class, 'store'])->name('store');
        Route::get('{banner}/edit', [BannerController::class, 'edit'])->name('edit');
        Route::put('{banner}', [BannerController::class, 'update'])->name('update');
        Route::delete('{banner}', [BannerController::class, 'destroy'])->name('destroy');
        Route::post('{banner}/toggle', [BannerController::class, 'toggle'])->name('toggle');
    });

     Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('create', [CouponController::class, 'create'])->name('create');
        Route::post('store', [CouponController::class, 'store'])->name('store');
        Route::get('{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('{coupon}', [CouponController::class, 'destroy'])->name('destroy');
        Route::post('{coupon}/toggle', [CouponController::class, 'toggle'])->name('toggle');
    });

     Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('create', [OrderController::class, 'create'])->name('create');
        Route::post('store', [OrderController::class, 'store'])->name('store');
        Route::get('{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('{order}', [OrderController::class, 'update'])->name('update');
        Route::get('{order}', [OrderController::class, 'show'])->name('show');
    });


});








require __DIR__.'/auth.php';
