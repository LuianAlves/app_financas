<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Card;
use Illuminate\Support\Facades\Auth;


class TransactionController extends Controller
{
    public function index()
    {
        $categories = TransactionCategory::all();
        $cards = Card::with('account')->get();
        $accounts = Account::all();

        return view('app.transactions.transaction.transaction_index', compact('categories', 'cards', 'accounts'));
    }
}
