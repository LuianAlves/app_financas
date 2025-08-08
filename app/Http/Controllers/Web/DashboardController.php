<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();

        $accountsBalance = Account::where('user_id', Auth::id())->sum('current_balance');
        $savingsBalance  = Saving::where('user_id', Auth::id())->sum('current_amount');

        $categorySums = Transaction::query()
            ->where('transactions.user_id', Auth::id())
            ->join('transaction_categories as tc', 'tc.id', '=', 'transactions.transaction_category_id')
            ->selectRaw('tc.type as category_type, SUM(transactions.amount) as total')
            ->groupBy('tc.type')
            ->pluck('total', 'category_type');

        $totalIncome  = (float) ($categorySums['entrada'] ?? 0);
        $totalExpense = (float) ($categorySums['despesa'] ?? 0);
        $prevision    = $accountsBalance + $totalIncome;
        $balance      = $totalIncome - $totalExpense;
        $total        = $balance + $accountsBalance;


        $recentTransactions = Transaction::with(['transactionCategory:id,name,type'])
            ->where('user_id', Auth::id())
            ->orderByDesc('date')            // mais nova primeiro
            ->limit(5)
            ->get(['id','title','amount','date','transaction_category_id']);

        $upcomingPayments = Transaction::with(['transactionCategory:id,name,type'])
            ->where('user_id', Auth::id())
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'despesa'))
            ->whereNotNull('date')
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->limit(10)
            ->get(['id','title','amount','date','transaction_category_id']);

        $upcomingIncomes = Transaction::with(['transactionCategory:id,name,type'])
            ->where('user_id', Auth::id())
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'entrada'))
            ->whereNotNull('date')
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->limit(10)
            ->get(['id','title','amount','date','transaction_category_id']);

        $mergedRows = $recentTransactions
            ->concat($upcomingPayments)
            ->concat($upcomingIncomes)
            ->unique('id');

        $calendarEvents = $mergedRows->map(function (Transaction $t) {
            $cat  = optional($t->transactionCategory);
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true)
                ? $cat->type
                : 'investimento';

            return [
                'id'    => $t->id,
                'title' => $t->title ?? $cat?->name,
                'start' => $t->date,
                'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                'extendedProps' => [
                    'amount'        => (float) $t->amount,
                    'amount_brl'    => brlPrice($t->amount),
                    'category_name' => $cat?->name,
                    'type'          => $type,
                ],
            ];
        })->values();

        return view('app.dashboard', compact(
            'accountsBalance', 'savingsBalance', 'total', 'categorySums',
            'totalIncome', 'balance', 'prevision',
            'recentTransactions', 'upcomingPayments', 'calendarEvents'
        ));
    }
}
