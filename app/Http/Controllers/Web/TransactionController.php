<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Card;
use Illuminate\Support\Facades\Auth;


class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['category', 'card'])
            ->where('user_id', Auth::id())
            ->orderByDesc('date')
            ->get();

        $transactions->each(function ($t) {
            $t->amount_brl = brlPrice($t->amount);
        });

        // 🔧 aqui está o que falta:
        $categories = TransactionCategory::where('user_id', Auth::id())->get();
        $cards = Card::where('user_id', Auth::id())->get();

        return view('app.transactions.transaction.transaction_index', compact('transactions', 'categories', 'cards'));
    }
}
