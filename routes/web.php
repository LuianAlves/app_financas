<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\{
    AuthController,
};

use App\Http\Controllers\{
    DashboardController,
    UserController,
    AccountController,
    CardController,
    CardTransactionController,
    CategoryController,
    InvoiceController,
    NotificationController,
    RecurrentController,
    SavingController,
    TransactionController,
};




Route::get('/login', [AuthController::class, 'welcome']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
Route::get('/register', [AuthController::class, 'registerView']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::any('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware(['auth', config('jetstream.auth_session')])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class)->scoped(['user' => 'uuid']);

    Route::resource('accounts', AccountController::class)->scoped(['account' => 'uuid']);
    Route::resource('cards', CardController::class)->scoped(['card' => 'uuid']);
    Route::resource('categories', CategoryController::class)->scoped(['category' => 'uuid']);
    Route::resource('transactions', TransactionController::class)->scoped(['transaction' => 'uuid']);
    Route::resource('recurrents', RecurrentController::class)->scoped(['recurrent' => 'uuid']);
    Route::resource('invoices', InvoiceController::class)->scoped(['invoice' => 'uuid']);
    Route::resource('card-transactions', CardTransactionController::class)->scoped(['cardTransaction' => 'uuid']);
    Route::resource('savings', SavingController::class)->scoped(['saving' => 'uuid']);

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
