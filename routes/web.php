<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AccountController,
    CardController,
    CategoryController,
    TransactionController,
    RecurrentController,
    InvoiceController,
    CardTransactionController,
    SavingController,
    NotificationController
};

// Middleware de autenticação, agrupamento
Route::middleware(['auth'])->group(function () {
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
