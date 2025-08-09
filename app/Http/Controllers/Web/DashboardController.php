<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $monthParam = $request->query('month'); // ex.: 2025-09
        $startOfMonth = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $endOfMonth = (clone $startOfMonth)->endOfMonth();
        $today = Carbon::today();

        $accountsBalance = Account::where('user_id', Auth::id())->sum('current_balance');
        $savingsBalance  = Saving::where('user_id', Auth::id())->sum('current_amount');

        $categorySums = Transaction::query()
            ->where('transactions.user_id', Auth::id())
            ->whereBetween('transactions.date', [$startOfMonth, $endOfMonth])
            ->join('transaction_categories as tc', 'tc.id', '=', 'transactions.transaction_category_id')
            // NORMALIZA type
            ->selectRaw("LOWER(TRIM(tc.type)) as category_type, SUM(transactions.amount) as total")
            ->groupBy('category_type')
            ->pluck('total', 'category_type');

        $totalIncome  = (float) ($categorySums['entrada'] ?? 0);
        $totalExpense = (float) ($categorySums['despesa'] ?? 0);
        // $totalInvest  = (float) ($categorySums['investimento'] ?? 0);

        // ----- Números do mês -----
        $balance = $totalIncome - $totalExpense; // saldo do mês
        $total   = $balance;                     // TOTAL APENAS DO MÊS

        // ----- Transações recentes (sempre as 5 últimas) -----
        $recentTransactions = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->where('user_id', Auth::id())
            ->orderByDesc('date')
            ->limit(5)
            ->get(['id','title','amount','date','transaction_category_id']);

        // ----- Próximas 10 DESPESAS (>= hoje) -----
        $upcomingPayments = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->where('user_id', Auth::id())
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'despesa'))
            ->whereNotNull('date')
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->limit(10)
            ->get(['id','title','amount','date','transaction_category_id']);

        // ----- Próximas 10 ENTRADAS (>= hoje) — pro calendário -----
        $upcomingIncomes = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->where('user_id', Auth::id())
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'entrada'))
            ->whereNotNull('date')
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->limit(10)
            ->get(['id','title','amount','date','transaction_category_id']);

        // ----- Calendário: recentes + despesas futuras + entradas futuras (sem duplicatas) -----
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
                'bg' => $t->transactionCategory->color,
                'icon' => $t->transactionCategory->icon,
                'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                'extendedProps' => [
                    'amount'        => (float) $t->amount,
                    'amount_brl'    => brlPrice($t->amount),
                    'category_name' => $cat?->name,
                    'type'          => $type,
                ],
            ];
        })->values();

        // ----- mês anterior (NORMALIZADO TAMBÉM) -----
        $prevStart = (clone $startOfMonth)->subMonth()->startOfMonth();
        $prevEnd   = (clone $prevStart)->endOfMonth();

        $prevSums = Transaction::query()
            ->where('transactions.user_id', Auth::id())
            ->whereBetween('transactions.date', [$prevStart, $prevEnd])
            ->join('transaction_categories as tc', 'tc.id', '=', 'transactions.transaction_category_id')
            ->selectRaw("LOWER(TRIM(tc.type)) as category_type, SUM(transactions.amount) as total")
            ->groupBy('category_type')
            ->pluck('total', 'category_type');

        $prevIncome  = (float)($prevSums['entrada'] ?? 0);
        $prevExpense = (float)($prevSums['despesa'] ?? 0);

        // % M/M (tratando divisão por zero)
        $incomeMoM  = $this->percentChange($totalIncome, $prevIncome);   // A receber
        $expenseMoM = $this->percentChange($totalExpense, $prevExpense); // A pagar

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
