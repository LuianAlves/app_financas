<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\{
    AuthController,
};

// Api
use App\Http\Controllers\Api\{
    AccountController,
    CardController,
    CategoryController,
    TransactionController,
    RecurrentController,
    InvoiceController,
    CardTransactionController,
    SavingController,
    NotificationController,
};

// Web
use App\Http\Controllers\Web\{
    DashboardController
};

Route::get('/login', [AuthController::class, 'welcome']);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');

Route::get('/register', [AuthController::class, 'registerView']);
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::any('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware(['auth', config('jetstream.auth_session')])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::resource('accounts', AccountController::class)->parameters(['accounts' => 'account:uuid']);
    Route::resource('cards', CardController::class)->parameters(['cards' => 'card:uuid']);
    Route::resource('categories', CategoryController::class)->parameters(['categories' => 'category:uuid']);
    Route::resource('transactions', TransactionController::class)->parameters(['transactions' => 'transaction:uuid']);
    Route::resource('recurrents', RecurrentController::class)->parameters(['recurrents' => 'recurrent:uuid']);
    Route::resource('invoices', InvoiceController::class)->parameters(['invoices' => 'invoice:uuid']);
    Route::resource('card-transactions', CardTransactionController::class)->parameters(['card-transactions' => 'cardTransaction:uuid']);
    Route::resource('savings', SavingController::class)->parameters(['savings' => 'saving:uuid']);

    // Rotas específicas para notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
