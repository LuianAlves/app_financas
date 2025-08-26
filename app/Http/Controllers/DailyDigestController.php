<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyDigestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tz = 'America/Sao_Paulo';
        $today = now($tz)->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $afterTomorrow = $tomorrow->copy()->addDay();

        // ========= TRANSAÇÕES (sem cartão/credit e sem “total fatura”)
        $txBase = $user->transactions()->with('transactionCategory')
            ->where(function ($q) {
                $q->where('transactions.type','!=','card')
                    ->orWhereNull('transactions.type')
                    ->orWhere(fn($qq)=>$qq->where('transactions.type','card')->where('transactions.type_card','!=','credit'));
            })
            ->where(function ($q) {
                $q->whereNull('transactions.title')
                    ->orWhereRaw('LOWER(transactions.title) NOT IN (?,?,?)', ['total fatura','fatura total','total da fatura']);
            });

        // HOJE
        $todayBase = (clone $txBase)->whereDate('date', $today->toDateString());
        $todayIn   = (clone $todayBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','entrada'))->get();
        $todayOut  = (clone $todayBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','despesa'))->get();
        $todayInv  = (clone $todayBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','investimento'))->get();

        // AMANHÃ
        $tomBase = (clone $txBase)->whereDate('date', $tomorrow->toDateString());
        $tomIn   = (clone $tomBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','entrada'))->get();
        $tomOut  = (clone $tomBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','despesa'))->get();
        $tomInv  = (clone $tomBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','investimento'))->get();

        // ========= FATURAS (um evento por invoice no vencimento) — para HOJE/AMANHÃ
        $invoicesAgg = DB::table('invoices as inv')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->where('inv.user_id', $user->id)
            ->where('inv.paid', false)
            ->groupBy('inv.id','inv.card_id','inv.current_month','c.cardholder_name','c.last_four_digits','c.due_day')
            ->select(
                'inv.id','inv.card_id','inv.current_month',
                'c.cardholder_name','c.last_four_digits','c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total')
            )->get()
            ->map(function ($r) {
                $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
                $due  = $base->copy()->day(min((int)($r->due_day ?: 1), $base->daysInMonth));
                $first = explode(' ', trim((string)$r->cardholder_name))[0];
                return [
                    'invoice_id'    => (string)$r->id,
                    'card_id'       => (string)$r->card_id,
                    'current_month' => $r->current_month,
                    'title'         => "Fatura {$first} {$r->last_four_digits}",
                    'due_date'      => $due->toDateString(),
                    'total'         => (float)$r->total,
                ];
            })
            ->filter(fn($x)=>$x['total']>0)
            ->values();

        $invoicesToday    = $invoicesAgg->where('due_date', $today->toDateString())->values();
        $invoicesTomorrow = $invoicesAgg->where('due_date', $tomorrow->toDateString())->values();

        // ========= PRÓXIMOS (expande recorrentes + únicas + custom + faturas)
        // janela larga o suficiente para repetir mensais; depois pegamos só 5
        $winStart = $afterTomorrow->copy();
        $winEnd   = $afterTomorrow->copy()->addMonthsNoOverflow(12); // suficiente para gerar várias ocorrências

        $events = $this->buildWindowEventsLite($user->id, $winStart, $winEnd);

        // tira “pagamentos” (somente lançamentos e faturas), ordena, pega 5
        $nextFive = collect($events)
            ->filter(fn($e)=>($e['extendedProps']['type'] ?? null) !== 'payment')
            ->filter(fn($e)=>Carbon::parse($e['start'])->gte($afterTomorrow))
            ->sortBy('start')
            ->take(5)
            ->values();

        return view('app.digest.index', compact(
            'today','tomorrow',
            'todayIn','todayOut','todayInv',
            'tomIn','tomOut','tomInv',
            'invoicesToday','invoicesTomorrow',
            'nextFive'
        ));
    }

    /**
     * Versão “lite” do dashboard: gera eventos (únicas + recorrentes + custom + faturas) numa janela.
     * Mantém ícones/cores e soma de faturas por invoice (na data de vencimento).
     */
    private function buildWindowEventsLite(string $userId, Carbon $winStart, Carbon $winEnd): \Illuminate\Support\Collection
    {
        $events = collect();

        // ÚNICAS (não-cartão/credit e sem títulos de controle de fatura)
        $uniqueTx = \App\Models\Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,name,type,color,icon'])
            ->leftJoin('payment_transactions as pt', 'pt.transaction_id', '=', 'transactions.id')
            ->where('transactions.user_id', $userId)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$winStart, $winEnd])
            ->whereNull('pt.id')
            ->where(function ($q) {
                $q->where('transactions.type','!=','card')
                    ->orWhereNull('transactions.type')
                    ->orWhere(fn($qq)=>$qq->where('transactions.type','card')->where('transactions.type_card','!=','credit'));
            })
            ->where(function ($q) {
                $q->whereNull('transactions.title')
                    ->orWhereRaw('LOWER(transactions.title) NOT IN (?,?,?)', ['total fatura','fatura total','total da fatura']);
            })
            ->get([
                'transactions.id','transactions.title','transactions.amount',
                'transactions.date','transactions.transaction_category_id'
            ]);

        foreach ($uniqueTx as $t) {
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $events->push([
                'id'    => (string)$t->id,
                'title' => $t->title ?? $cat?->name,
                'start' => (string)$t->date,
                'bg'    => $cat?->color,
                'icon'  => $cat?->icon,
                'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                'extendedProps' => [
                    'amount'        => (float)$t->amount,
                    'amount_brl'    => function_exists('brlPrice') ? brlPrice($t->amount) : number_format($t->amount,2,',','.'),
                    'category_name' => $cat?->name,
                    'type'          => $type,
                    'transaction_id'=> (string)$t->id,
                ],
            ]);
        }

        // RECORRENTES MONTHLY/YEARLY (sem itens custom)
        $recMY = \App\Models\Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->where('recurrents.user_id', $userId)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('recurrence_type',['monthly','yearly'])
                    ->where(function ($q2) {
                        $q2->where('transactions.type','!=','card')
                            ->orWhereNull('transactions.type')
                            ->orWhere(fn($qq)=>$qq->where('transactions.type','card')->where('transactions.type_card','!=','credit'));
                    })
                    ->where(function ($q3) {
                        $q3->whereNull('transactions.title')
                            ->orWhereRaw('LOWER(transactions.title) NOT IN (?,?,?)', ['total fatura','fatura total','total da fatura']);
                    });
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('custom_item_recurrents as cir')
                    ->whereColumn('cir.recurrent_id', 'recurrents.id');
            })
            ->get(['recurrents.id','recurrents.transaction_id','recurrents.payment_day','recurrents.amount']);

        foreach ($recMY as $r) {
            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $amount = (float)$r->amount;
            $anchor = Carbon::parse($t->date)->startOfDay();
            $pd = max(1, (int)$r->payment_day);

            if ($t->recurrence_type === 'monthly') {
                $m = $winStart->copy()->startOfMonth();
                while ($m->lte($winEnd)) {
                    $occ = $m->copy()->day(min($pd, $m->daysInMonth));
                    if ($occ->betweenIncluded($winStart,$winEnd) && $occ->gte($anchor)) {
                        $events->push([
                            'id'    => "rec_m_{$r->id}_".$occ->format('Ymd'),
                            'title' => $t->title ?? $cat?->name,
                            'start' => $occ->toDateString(),
                            'bg'    => $cat?->color,
                            'icon'  => $cat?->icon,
                            'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                            'extendedProps' => [
                                'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                                'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount,2,',','.'),
                                'category_name' => $cat?->name,
                                'type'          => $type,
                                'transaction_id'=> (string)$t->id,
                            ],
                        ]);
                    }
                    $m->addMonthNoOverflow();
                }
            } else {
                $anchorMonth = (int) $anchor->month;
                for ($y = $winStart->year; $y <= $winEnd->year; $y++) {
                    $daysIn = Carbon::create($y, $anchorMonth, 1)->daysInMonth;
                    $occ = Carbon::create($y, $anchorMonth, min($pd, $daysIn));
                    if ($occ->betweenIncluded($winStart,$winEnd) && $occ->gte($anchor)) {
                        $events->push([
                            'id'    => "rec_y_{$r->id}_".$occ->format('Ymd'),
                            'title' => $t->title ?? $cat?->name,
                            'start' => $occ->toDateString(),
                            'bg'    => $cat?->color,
                            'icon'  => $cat?->icon,
                            'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                            'extendedProps' => [
                                'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                                'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount,2,',','.'),
                                'category_name' => $cat?->name,
                                'type'          => $type,
                                'transaction_id'=> (string)$t->id,
                            ],
                        ]);
                    }
                }
            }
        }

        // CUSTOM COM ITENS (terminados)
        $recCustom = \App\Models\Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->where('recurrents.user_id', $userId)
            ->whereHas('transaction', fn($q)=>$q->whereIn('recurrence_type',['custom','monthly','yearly']))
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('custom_item_recurrents as cir')
                    ->whereColumn('cir.recurrent_id','recurrents.id');
            })
            ->get(['recurrents.id','recurrents.transaction_id']);

        foreach ($recCustom as $r) {
            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';

            $items = \App\Models\CustomItemRecurrents::where('recurrent_id',$r->id)
                ->get(['payment_day','reference_month','reference_year','amount','custom_occurrence_number']);

            foreach ($items as $ci) {
                $days = Carbon::create($ci->reference_year, $ci->reference_month, 1)->daysInMonth;
                $occ  = Carbon::create($ci->reference_year, $ci->reference_month, min((int)$ci->payment_day, $days));
                if (!$occ->betweenIncluded($winStart,$winEnd)) continue;

                $amount = (float)$ci->amount;
                $events->push([
                    'id'    => "rec_c_{$r->id}_".$occ->format('Ymd')."_".$ci->custom_occurrence_number,
                    'title' => $t->title ?? $cat?->name,
                    'start' => $occ->toDateString(),
                    'bg'    => $cat?->color,
                    'icon'  => $cat?->icon,
                    'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                    'extendedProps' => [
                        'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                        'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount,2,',','.'),
                        'category_name' => $cat?->name,
                        'type'          => $type,
                        'transaction_id'=> (string)$t->id,
                    ],
                ]);
            }
        }

        // CUSTOM “A CADA X DIAS” (sem itens)
        $recDays = \App\Models\Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->where('recurrents.user_id', $userId)
            ->where('interval_unit','days')
            ->whereHas('transaction', function ($q) {
                $q->where('recurrence_type','custom')
                    ->where(function ($q2) {
                        $q2->where('transactions.type','!=','card')
                            ->orWhereNull('transactions.type')
                            ->orWhere(fn($qq)=>$qq->where('transactions.type','card')->where('transactions.type_card','!=','credit'));
                    })
                    ->where(function ($q3) {
                        $q3->whereNull('transactions.title')
                            ->orWhereRaw('LOWER(transactions.title) NOT IN (?,?,?)', ['total fatura','fatura total','total da fatura']);
                    });
            })
            ->get(['recurrents.*']);

        foreach ($recDays as $r) {
            if (DB::table('custom_item_recurrents')->where('recurrent_id', $r->id)->exists()) continue;

            $t = $r->transaction; if (!$t) continue;
            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';

            $startBase = Carbon::parse($t->date)->startOfDay();
            $start     = Carbon::parse($r->start_date ?: $startBase)->startOfDay();
            if ($start->lt($startBase)) $start = $startBase;

            $interval = max(1, (int)$r->interval_value);
            $cursor = $this->firstAlignedDays($start, $winStart, $interval);
            $cursor = $this->normalizeW($cursor, (bool)$r->include_sat, (bool)$r->include_sun);

            $amount = (float)($r->amount ?: $t->amount);

            while ($cursor->lte($winEnd)) {
                $events->push([
                    'id'    => "rec_d_{$r->id}_".$cursor->format('Ymd'),
                    'title' => $t->title ?? $cat?->name,
                    'start' => $cursor->toDateString(),
                    'bg'    => $cat?->color,
                    'icon'  => $cat?->icon,
                    'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
                    'extendedProps' => [
                        'amount'        => $type === 'entrada' ? abs($amount) : -abs($amount),
                        'amount_brl'    => function_exists('brlPrice') ? brlPrice($amount) : number_format($amount,2,',','.'),
                        'category_name' => $cat?->name,
                        'type'          => $type,
                        'transaction_id'=> (string)$t->id,
                    ],
                ]);
                $cursor = $this->normalizeW($cursor->copy()->addDays($interval), (bool)$r->include_sat, (bool)$r->include_sun);
            }
        }

        // FATURAS (um por invoice, na data de vencimento)
        $rows = DB::table('invoices as inv')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->where('inv.user_id', $userId)
            ->where('inv.paid', false)
            ->groupBy('inv.id','inv.card_id','inv.current_month','c.cardholder_name','c.last_four_digits','c.due_day')
            ->select(
                'inv.id','inv.card_id','inv.current_month',
                'c.cardholder_name','c.last_four_digits','c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total')
            )->get();

        foreach ($rows as $r) {
            $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $due  = $base->copy()->day(min((int)($r->due_day ?: 1), $base->daysInMonth));
            $total = (float)$r->total;
            if ($total <= 0 || !$due->betweenIncluded($winStart,$winEnd)) continue;

            $first = explode(' ', trim((string)$r->cardholder_name))[0];

            $events->push([
                'id'    => (string)$r->id,
                'title' => "Fatura {$first} {$r->last_four_digits}",
                'start' => $due->toDateString(),
                'bg'    => '#be123c',
                'icon'  => 'fa-solid fa-credit-card',
                'color' => '#ef4444',
                'extendedProps' => [
                    'amount'        => -abs($total),
                    'amount_brl'    => function_exists('brlPrice') ? brlPrice($total) : number_format($total,2,',','.'),
                    'category_name' => 'Fatura Cartão',
                    'type'          => 'despesa',
                    'is_invoice'    => true,
                    'paid'          => false,
                    'card_id'       => (string)$r->card_id,
                    'current_month' => $r->current_month,
                ],
            ]);
        }

        return $events->values();
    }

    private function normalizeW(Carbon $d, bool $sat, bool $sun): Carbon
    {
        if (!$sat && $d->isSaturday()) $d->addDays(2);
        if (!$sun && $d->isSunday())   $d->addDay();
        return $d;
    }

    /** primeira ocorrência >= $from alinhada ao step de $interval dias */
    private function firstAlignedDays(Carbon $start, Carbon $from, int $interval): Carbon
    {
        $s = $start->copy();
        if ($s->lt($from)) {
            $diff  = $s->diffInDays($from);
            $steps = intdiv($diff + $interval - 1, $interval);
            $s->addDays($steps * $interval);
        }
        return $s;
    }
}
