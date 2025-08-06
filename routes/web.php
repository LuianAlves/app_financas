<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\AuthController;

// Api Controllers
use App\Http\Controllers\Api\{
    UserController as ApiUserController,
    AccountController as ApiAccountController,
    CardController as ApiCardController,
    CardTransactionController as ApiCardTransactionController,
    TransactionCategoryController as ApiTransactionCategoryController,
    InvoiceController as ApiInvoiceController,
    RecurrentController as ApiRecurrentController,
    SavingController as ApiSavingController,
    TransactionController as ApiTransactionController,
};

// Web Controllers
use App\Http\Controllers\Web\{
    AccountController as WebAccountController,
    CardController as WebCardController,
    DashboardController as WebDashboardController,
    NotificationController as WebNotificationController,
    SavingController as WebSavingController,
};

Route::get('/login', [AuthController::class, 'welcome']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
Route::get('/register', [AuthController::class, 'registerView']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::any('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware(['auth', config('jetstream.auth_session')])->group(function () {
    // Dashboard
    Route::get('/dashboard', [WebDashboardController::class, 'dashboard'])->name('dashboard');

    // Users
    Route::resource('users', ApiUserController::class)->scoped(['user' => 'uuid']);

    // Accounts
    Route::get('/account', [WebAccountController::class, 'index'])->name('account-view.index');
    Route::resource('accounts', ApiAccountController::class)->scoped(['account' => 'uuid']);

    // Cards
    Route::get('/card', [WebCardController::class, 'index'])->name('card-view.index');
    Route::resource('cards', ApiCardController::class)->scoped(['card' => 'uuid']);

    //Savings
    Route::get('/saving', [WebSavingController::class, 'index'])->name('saving-view.index');
    Route::resource('savings', ApiSavingController::class)->scoped(['saving' => 'uuid']);


    Route::resource('transaction-categories', ApiTransactionCategoryController::class)->scoped(['category' => 'uuid']);
    Route::resource('transactions', ApiTransactionController::class)->scoped(['transaction' => 'uuid']);
    Route::resource('recurrents', ApiRecurrentController::class)->scoped(['recurrent' => 'uuid']);
    Route::resource('invoices', ApiInvoiceController::class)->scoped(['invoice' => 'uuid']);
    Route::resource('card-transactions', ApiCardTransactionController::class)->scoped(['cardTransaction' => 'uuid']);



    Route::get('notifications', [WebNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [WebNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('notifications/{notification}', [WebNotificationController::class, 'destroy'])->name('notifications.destroy');
});
