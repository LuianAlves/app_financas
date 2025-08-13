<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AdditionalUser;
use App\Models\CustomItemRecurrents;
use App\Models\Recurrent;
use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        // ===== dono + adicionais =====
        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::query()
            ->where('user_id', $ownerId)
            ->pluck('linked_user_id')
            ->push($ownerId)
            ->unique()
            ->values();

        // ===== mês atual para KPIs =====
        $monthParam   = $request->query('month');
        $startOfMonth = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();
        $endOfMonth   = (clone $startOfMonth)->endOfMonth();
        $today        = Carbon::today();

        // ===== saldos =====
        $accountsBalance = Account::whereIn('accounts.user_id', $userIds)->sum('current_balance');
        $savingsBalance  = Saving::whereIn('savings.user_id', $userIds)->sum('current_amount');

        // ===== totais por tipo (mês atual) =====
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

        // ===== listas (últimas / próximas) =====
        $recentTransactions = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->orderByDesc('transactions.date')
            ->limit(5)
            ->get(['transactions.id','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        $upcomingPayments = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'despesa'))
            ->whereNotNull('transactions.date')
            ->whereDate('transactions.date', '>=', $today)
            ->orderBy('transactions.date')
            ->limit(10)
            ->get(['transactions.id','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        $upcomingIncomes = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'entrada'))
            ->whereNotNull('transactions.date')
            ->whereDate('transactions.date', '>=', $today)
            ->orderBy('transactions.date')
            ->limit(10)
            ->get(['transactions.id','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        // ===========================================================
        // ============ CALENDÁRIO: PRÓXIMOS 12 MESES ================
        // janela: do início do mês atual até +11 meses
        $winStart = (clone $startOfMonth)->startOfMonth();
        $winEnd   = (clone $winStart)->addMonthsNoOverflow(11)->endOfMonth();

        $events = collect();

        // 1) Transações "únicas" dentro da janela
        $uniqueTx = Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$winStart, $winEnd])
            ->where(function($q){
                $q->where('transactions.type', '!=', 'card')
                    ->orWhereNull('transactions.type_card')
                    ->orWhere('transactions.type_card', '!=', 'credit');
            })
            ->get(['transactions.id','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        foreach ($uniqueTx as $t) {
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $events->push([
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
            ]);
        }

        // 2) Recorrentes MONTHLY / YEARLY
        $recMY = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->whereIn('recurrence_type', ['monthly','yearly']))
            ->get(['recurrents.id','recurrents.user_id','recurrents.transaction_id','recurrents.payment_day','recurrents.amount']);

        foreach ($recMY as $r) {
            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $startBase = Carbon::parse($t->date)->startOfDay();
            $paymentDay = max(1, (int) $r->payment_day);
            $amount = (float) $r->amount; // valor por ocorrência

            if ($t->recurrence_type === 'monthly') {
                $m = $winStart->copy()->startOfMonth();
                while ($m->lte($winEnd)) {
                    $occ = $m->copy()->day(min($paymentDay, $m->daysInMonth));

                    if ($occ->gte($startBase)) {
                        $events->push([
                            'id'    => "rec_m_{$r->id}_".$occ->format('Ymd'),
                            'title' => $t->title ?? $cat?->name,
                            'start' => $occ->toDateString(),
                            'bg'    => $cat?->color,
                            'icon'  => $cat?->icon,
                            'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                            'extendedProps' => [
                                'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                                'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount, 2, ',', '.'),
                                'category_name' => $cat?->name,
                                'type'          => $type,
                            ],
                        ]);
                    }
                    $m->addMonth();
                }
            } else { // yearly
                $anchorMonth = (int) $startBase->month;
                $y = $winStart->year;
                $yEnd = $winEnd->year;
                for (; $y <= $yEnd; $y++) {
                    $daysIn = Carbon::create($y, $anchorMonth, 1)->daysInMonth;
                    $occ = Carbon::create($y, $anchorMonth, min($paymentDay, $daysIn));
                    if ($occ->betweenIncluded($winStart, $winEnd) && $occ->gte($startBase)) {
                        $events->push([
                            'id'    => "rec_y_{$r->id}_".$occ->format('Ymd'),
                            'title' => $t->title ?? $cat?->name,
                            'start' => $occ->toDateString(),
                            'bg'    => $cat?->color,
                            'icon'  => $cat?->icon,
                            'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                            'extendedProps' => [
                                'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                                'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount, 2, ',', '.'),
                                'category_name' => $cat?->name,
                                'type'          => $type,
                            ],
                        ]);
                    }
                }
            }
        }

        // 3) Recorrentes CUSTOM (com cronograma)
        $recCustom = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->where('recurrence_type', 'custom'))
            ->get(['recurrents.id','recurrents.user_id','recurrents.transaction_id']);

        foreach ($recCustom as $r) {
            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';

            $items = CustomItemRecurrents::where('recurrent_id', $r->id)->get(['payment_day','reference_month','reference_year','amount','custom_occurrence_number']);
            foreach ($items as $ci) {
                $daysIn = Carbon::create($ci->reference_year, $ci->reference_month, 1)->daysInMonth;
                $occ = Carbon::create($ci->reference_year, $ci->reference_month, min((int)$ci->payment_day, $daysIn));
                if (!$occ->betweenIncluded($winStart, $winEnd)) continue;

                $amount = (float) $ci->amount;
                $events->push([
                    'id'    => "rec_c_{$r->id}_".$occ->format('Ymd')."_".$ci->custom_occurrence_number,
                    'title' => $t->title ?? $cat?->name,
                    'start' => $occ->toDateString(),
                    'bg'    => $cat?->color,
                    'icon'  => $cat?->icon,
                    'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                    'extendedProps' => [
                        'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                        'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount, 2, ',', '.'),
                        'category_name' => $cat?->name,
                        'type'          => $type,
                    ],
                ]);
            }
        }

        $calendarEvents = $events->values();

        // ===== mês anterior (para % M/M dos KPIs) =====
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

    public function calendarEvents(Request $request)
    {
        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::where('user_id', $ownerId)
            ->pluck('linked_user_id')->push($ownerId)->unique()->values();

        $start  = $request->query('start', now()->format('Y-m'));
        $months = max(1, min((int)$request->query('months', 2), 24));

        $winStart = Carbon::createFromFormat('Y-m', $start)->startOfMonth();
        $winEnd   = (clone $winStart)->addMonthsNoOverflow($months - 1)->endOfMonth();

        return response()->json($this->buildWindowEvents($userIds, $winStart, $winEnd)->values());
    }

    private function buildWindowEvents(Collection $userIds, Carbon $winStart, Carbon $winEnd): Collection
    {
        $events = collect();

        // 1) Únicas
        $uniqueTx = Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,name,type,color,icon'])
            ->whereIn('transactions.user_id', $userIds)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$winStart, $winEnd])
            ->get(['transactions.id','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        foreach ($uniqueTx as $t) {
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $events->push([
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
            ]);
        }

        // 2) MONTHLY / YEARLY (mesma lógica que já usamos)
        $recMY = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->whereIn('recurrence_type', ['monthly','yearly']))
            ->get(['recurrents.id','recurrents.user_id','recurrents.transaction_id','recurrents.payment_day','recurrents.amount']);

        foreach ($recMY as $r) {
            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $startBase  = Carbon::parse($t->date)->startOfDay();
            $paymentDay = max(1, (int)$r->payment_day);
            $amount     = (float)$r->amount;

            if ($t->recurrence_type === 'monthly') {
                $m = $winStart->copy()->startOfMonth();
                while ($m->lte($winEnd)) {
                    $occ = $m->copy()->day(min($paymentDay, $m->daysInMonth()));
                    if ($occ->gte($startBase)) $events->push($this->ev($r->id,'m',$t,$cat,$type,$occ,$amount));
                    $m->addMonth();
                }
            } else {
                $anchorMonth = (int) $startBase->month;
                for ($y = $winStart->year; $y <= $winEnd->year; $y++) {
                    $daysIn = Carbon::create($y, $anchorMonth, 1)->daysInMonth;
                    $occ = Carbon::create($y, $anchorMonth, min($paymentDay, $daysIn));
                    if ($occ->betweenIncluded($winStart, $winEnd) && $occ->gte($startBase)) {
                        $events->push($this->ev($r->id,'y',$t,$cat,$type,$occ,$amount));
                    }
                }
            }
        }

        // 3) CUSTOM (com cronograma)
        $recC = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->where('recurrence_type', 'custom'))
            ->get(['recurrents.id','recurrents.user_id','recurrents.transaction_id']);

        foreach ($recC as $r) {
            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';

            $items = CustomItemRecurrents::where('recurrent_id', $r->id)
                ->get(['payment_day','reference_month','reference_year','amount','custom_occurrence_number']);

            foreach ($items as $ci) {
                $daysIn = Carbon::create($ci->reference_year, $ci->reference_month, 1)->daysInMonth;
                $occ = Carbon::create($ci->reference_year, $ci->reference_month, min((int)$ci->payment_day, $daysIn));
                if (!$occ->betweenIncluded($winStart, $winEnd)) continue;

                $events->push($this->ev($r->id,'c',$t,$cat,$type,$occ,(float)$ci->amount,$ci->custom_occurrence_number));
            }
        }

        // 4) FATURAS DE CARTÃO (um evento por invoice, na data de vencimento)
        $rows = DB::table('invoices as inv')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->whereIn('inv.user_id', $userIds)
            ->groupBy(
                'inv.id','inv.card_id','inv.current_month','inv.paid',
                'c.cardholder_name','c.last_four_digits','c.due_day'
            )
            ->get([
                'inv.id',
                'inv.card_id',
                'inv.current_month',
                'inv.paid',
                'c.cardholder_name',
                'c.last_four_digits',          // <— vamos usar no título
                'c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total'),
            ]);

        foreach ($rows as $r) {
            // current_month ('Y-m') -> calcula a data de vencimento usando o due_day do cartão
            $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $due  = $base->copy()->day(min((int)($r->due_day ?: 1), $base->daysInMonth));
            $total = (float)$r->total;
            $firstName = explode(' ', trim($r->cardholder_name))[0];

            // Só cria evento se há valor (>0) e se o vencimento está dentro da janela
            if ($total > 0 && $due->betweenIncluded($winStart, $winEnd)) {
                $events->push([
                    'id'    => (string)$r->id, // id da invoice
                    'title'    => "Fatura {$firstName} {$r->last_four_digits}",
                    'start' => $due->toDateString(),
                    // visual do cartão/fatura
                    'bg'    => '#be123c',                 // cor do “badge” (opcional)
                    'icon'  => 'fa-solid fa-credit-card', // ícone
                    'color' => '#ef4444',                 // cor do valor na listinha
                    'extendedProps' => [
                        'amount'       => abs($total),                 // sai do saldo → negativo
                        'amount_brl'   => brlPrice(abs($total)),       // formatado
                        'category_name'=> 'Fatura Cartão',
                        'type'         => 'despesa',                    // para teu JS pintar vermelho
                        'is_invoice'   => true,
                        'paid'         => (bool)$r->paid,
                        'card_id'      => (string)$r->card_id,
                    ],
                ]);
            }
        }

        return $events;
    }

    private function ev($rid,$kind,$t,$cat,$type,Carbon $occ,float $amount,?int $n=null): array
    {
        return [
            'id'    => "rec_{$kind}_{$rid}_".$occ->format('Ymd').($n ? "_$n" : ''),
            'title' => $t->title ?? $cat?->name,
            'start' => $occ->toDateString(),
            'bg'    => $cat?->color,
            'icon'  => $cat?->icon,
            'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
            'extendedProps' => [
                'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount, 2, ',', '.'),
                'category_name' => $cat?->name,
                'type'          => $type,
            ],
        ];
    }
}
