<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AdditionalUser;
use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::query()
            ->where('user_id', $ownerId)
            ->pluck('linked_user_id')
            ->push($ownerId)
            ->unique()
            ->values();

        $monthParam   = $request->query('month'); // ex.: 2025-09
        $startOfMonth = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();
        $endOfMonth   = (clone $startOfMonth)->endOfMonth();
        $today        = Carbon::today();

        $accountsBalance = Account::whereIn('accounts.user_id', $userIds)->sum('current_balance');
        $savingsBalance  = Saving::whereIn('savings.user_id', $userIds)->sum('current_amount');

        $categorySums = Transaction::query()
            ->join('transaction_categories as tc', 'tc.id', '=', 'transactions.transaction_category_id')
            ->whereIn('transactions.user_id', $userIds)
            ->whereBetween('transactions.date', [$startOfMonth, $endOfMonth])
            ->selectRaw('LOWER(TRIM(tc.type)) as category_type, SUM(transactions.amount) as total')
            ->groupBy('tc.type')
            ->pluck('total', 'category_type');

        $totalIncome  = (float) ($categorySums['entrada'] ?? 0);
        $totalExpense = (float) ($categorySums['despesa'] ?? 0);

        $balance = $totalIncome - $totalExpense;
        $total   = $balance;

        $recentTransactions = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->orderByDesc('transactions.date')
            ->limit(5)
            ->get([
                'transactions.id','transactions.title','transactions.amount',
                'transactions.date','transactions.transaction_category_id'
            ]);

        $upcomingPayments = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'despesa'))
            ->whereNotNull('transactions.date')
            ->whereDate('transactions.date', '>=', $today)
            ->orderBy('transactions.date')
            ->limit(10)
            ->get([
                'transactions.id','transactions.title','transactions.amount',
                'transactions.date','transactions.transaction_category_id'
            ]);

        $upcomingIncomes = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'entrada'))
            ->whereNotNull('transactions.date')
            ->whereDate('transactions.date', '>=', $today)
            ->orderBy('transactions.date')
            ->limit(10)
            ->get([
                'transactions.id','transactions.title','transactions.amount',
                'transactions.date','transactions.transaction_category_id'
            ]);

        $mergedRows = $recentTransactions
            ->concat($upcomingPayments)
            ->concat($upcomingIncomes)
            ->unique('id');

        $calendarEvents = $mergedRows->map(function (Transaction $t) {
            $cat  = optional($t->transactionCategory);
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';

            return [
                'id'    => $t->id,
                'title' => $t->title ?? $cat?->name,
                'start' => $t->date,
                'bg'    => $cat?->color,
                'icon'  => $cat?->icon,
                'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                'extendedProps' => [
                    'amount'        => (float) $t->amount,
                    'amount_brl'    => function_exists('brlPrice') ? brlPrice($t->amount) : number_format((float)$t->amount, 2, ',', '.'),
                    'category_name' => $cat?->name,
                    'type'          => $type,
                ],
            ];
        })->values();

        $prevStart = (clone $startOfMonth)->subMonth()->startOfMonth();
        $prevEnd   = (clone $prevStart)->endOfMonth();

        $prevSums = Transaction::query()
            ->join('transaction_categories as tc', 'tc.id', '=', 'transactions.transaction_category_id')
            ->whereIn('transactions.user_id', $userIds)
            ->whereBetween('transactions.date', [$prevStart, $prevEnd])
            ->selectRaw('LOWER(TRIM(tc.type)) as category_type, SUM(transactions.amount) as total')
            ->groupBy('tc.type')
            ->pluck('total', 'category_type');

        $prevIncome  = (float) ($prevSums['entrada'] ?? 0);
        $prevExpense = (float) ($prevSums['despesa'] ?? 0);

        $incomeMoM  = $this->percentChange($totalIncome,  $prevIncome);
        $expenseMoM = $this->percentChange($totalExpense, $prevExpense);

        return view('app.dashboard', compact(
            'accountsBalance', 'savingsBalance', 'total', 'categorySums',
            'totalIncome', 'balance',
            'recentTransactions', 'upcomingPayments', 'calendarEvents',
            'startOfMonth', 'endOfMonth',
            'incomeMoM', 'expenseMoM'
        ));
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            if ($current == 0.0) return 0.0;
            return null; // evita “infinito”; na view mostramos "—"
        }
        return (($current - $previous) / $previous) * 100.0;
    }
}
