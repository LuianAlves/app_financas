<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Saving;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $accounts = Account::where('user_id', Auth::id())->sum('current_balance');
        $savings = Saving::where('user_id', Auth::id())->sum('current_amount');

        $categorySums = TransactionCategory::where('user_id', Auth::id())
            ->selectRaw('type, SUM(monthly_limit) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $income = $categorySums['entrada'] ?? 0;
        $prevision = $accounts + $income;

        $expense = $categorySums['despesa'] ?? 0;
        $balance = $income - $expense;

        $total = $balance + $accounts;

        return view('app.dashboard', compact('accounts', 'savings', 'total', 'categorySums', 'income', 'balance', 'prevision'));
    }
}
