<?php
// app/Services/ProjectionService.php

namespace App\Services;

use App\Models\Account;
use App\Models\Card;
use App\Models\Transaction;
use App\Models\Recurrent;
use App\Models\CustomItemRecurrents;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectionService
{
    public function build(?string $userId, string $start, string $end): array
    {
        $uid  = $userId ?: Auth::id();
        [$ownerId, $userIds] = $this->resolveOwnerAndAdditionals($uid);

        $tz   = 'America/Sao_Paulo';
        $from = Carbon::parse($start, $tz)->startOfDay();
        $to   = Carbon::parse($end,   $tz)->endOfDay();

        $opening = $this->openingBalance($userIds);

        // 1) Lançamentos: únicos + recorrentes (monthly/yearly/custom)
        $occ = collect()
            ->merge($this->expandUnique($userIds, $from, $to))
            ->merge($this->expandRecurrentsMonthlyYearly($userIds, $from, $to))
            ->merge($this->expandRecurrentsCustom($userIds, $from, $to))
            ->merge($this->expandRecurrentsCustomDays($userIds, $from, $to)); // << NOVO

        $occ = $occ->reject(fn($o) => ($o['type'] ?? null) === 'card' && ($o['type_card'] ?? null) === 'credit');

        // 2) Faturas de cartão
        $bills = $this->cardBillsFromInvoices($userIds, $from, $to);

        // 3) Extrato diário consolidado
        $days = $this->consolidateDays($from, $to, $opening, $occ, $bills);

        return [
            'opening_balance' => round($opening, 2),
            'total_in'        => round(array_sum(array_column($days, 'in')), 2),
            'total_out'       => round(array_sum(array_column($days, 'out')), 2),
            'ending_balance'  => round(end($days)['balance'] ?? $opening, 2),
            'days'            => array_values($days),
        ];
    }

    protected function cardBillsFromInvoices(array $userIds, Carbon $from, Carbon $to): array
    {
        // agrega total por invoice
        $rows = DB::table('invoices as inv')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->whereIn('inv.user_id', $userIds)
            ->groupBy('inv.id','inv.card_id','inv.current_month','inv.paid','c.cardholder_name','c.due_day')
            ->get([
                'inv.id',
                'inv.card_id',
                'inv.current_month',
                'inv.paid',
                'c.cardholder_name',
                'c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total')
            ]);

        $bills = [];

        foreach ($rows as $r) {
            // current_month = 'Y-m' → calcula due_date com due_day do cartão
            $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $due  = $base->copy()->day(min((int)$r->due_day ?: 1, $base->daysInMonth));

            // considera no período selecionado
            if ($due->betweenIncluded($from, $to) && $r->total > 0) {
                $bills[] = [
                    'id'        => (string)$r->id,
                    'title'     => 'Fatura '.$r->cardholder_name.' (venc. '.$due->format('d/m').')',
                    'amount'    => -round((float)$r->total, 2), // sai do saldo
                    'date'      => $due->toDateString(),
                    'type'      => 'invoice',
                    'type_card' => 'credit',
                    'card_id'   => (string)$r->card_id,
                    'category'  => 'Fatura Cartão',
                    'is_invoice'=> true,
                    'paid'      => (bool)$r->paid,
                ];
            }
        }

        return $bills;
    }

    private function resolveOwnerAndAdditionals(string $uid): array
    {
        $ownerId = DB::table('additional_users')->where('linked_user_id', $uid)->value('user_id') ?? $uid;

        $ids = DB::table('additional_users')
            ->where('user_id', $ownerId)
            ->pluck('linked_user_id')
            ->all();

        $ids[] = $ownerId;
        $ids = array_values(array_unique($ids));

        return [$ownerId, $ids];
    }

    protected function openingBalance(array $userIds): float
    {
        return (float) Account::withoutGlobalScopes()
            ->whereIn('accounts.user_id', $userIds)
            ->sum('current_balance');
    }

    protected function expandUnique(array $userIds, Carbon $from, Carbon $to): Collection
    {
        $rows = Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,name,type'])
            ->whereIn('transactions.user_id', $userIds)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$from->toDateString(), $to->toDateString()])
            ->get(['id','user_id','transaction_category_id','title','amount','date','type','type_card','card_id']);

        return $rows->map(fn($t) => $this->mapTx($t, Carbon::parse($t->date)));
    }

    protected function expandRecurrentsMonthlyYearly(array $userIds, Carbon $from, Carbon $to): Collection
    {
        $recurrents = Recurrent::withoutGlobalScopes()
            ->with(['transaction' => function($q) use ($userIds) {
                $q->withoutGlobalScopes()
                    ->with(['transactionCategory:id,name,type'])
                    ->whereIn('transactions.user_id', $userIds)
                    ->select('id','user_id','transaction_category_id','title','amount','date','type','type_card','card_id','recurrence_type');
            }])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->whereIn('recurrence_type', ['monthly','yearly']))
            ->get(['id','user_id','transaction_id','payment_day','amount']);

        $out = collect();
        foreach ($recurrents as $r) {
            $t = $r->transaction;
            if (!$t) continue;

            $paymentDay = max(1, (int)$r->payment_day);
            $amount     = (float) $r->amount;
            $startBase  = Carbon::parse($t->date)->startOfDay();

            $cursor = $from->copy()->day($paymentDay);
            if ($cursor->lt($startBase)) $cursor = $startBase->copy()->day($paymentDay);

            if ($t->recurrence_type === 'monthly') {
                while ($cursor->lte($to)) {
                    $out->push($this->mapTxLike($t, $cursor, $amount, 'monthly'));
                    $cursor->addMonthNoOverflow()->day($paymentDay);
                }
            } else {
                $monthAnchor = (int) $startBase->month;
                $cursor = Carbon::create($cursor->year, $monthAnchor, min($paymentDay, 28))->startOfDay();
                if ($cursor->lt($from)) $cursor->addYear();
                while ($cursor->lte($to)) {
                    $out->push($this->mapTxLike($t, $cursor, $amount, 'yearly'));
                    $cursor->addYear();
                }
            }
        }
        return $out;
    }

    protected function expandRecurrentsCustom(array $userIds, Carbon $from, Carbon $to): Collection
    {
        $recs = Recurrent::withoutGlobalScopes()
            ->with(['transaction' => function($q) use ($userIds) {
                $q->withoutGlobalScopes()
                    ->with(['transactionCategory:id,name,type'])
                    ->whereIn('transactions.user_id', $userIds)
                    ->select('id','user_id','transaction_category_id','title','amount','date','type','type_card','card_id','recurrence_type','custom_occurrences');
            }])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->where('recurrence_type', 'custom'))
            ->get([
                'id','user_id','transaction_id',
                'payment_day','amount',
                'start_date','interval_unit','interval_value','include_sat','include_sun' // <- adiciona
            ]);

        $out = collect();

        foreach ($recs as $r) {
            $t = $r->transaction;
            if (!$t) continue;

            $items = CustomItemRecurrents::where('recurrent_id', $r->id)->get(['payment_day','reference_month','reference_year','amount','custom_occurrence_number']);
            if ($items->count()) {
                foreach ($items as $ci) {
                    $dt = $this->dateFromRefs((int)$ci->payment_day, (int)$ci->reference_month, (int)$ci->reference_year);
                    if ($dt->betweenIncluded($from, $to)) {
                        $out->push($this->mapTxLike($t, $dt, (float)$ci->amount, 'custom', $ci->custom_occurrence_number));
                    }
                }
                continue;
            }

            $totalParc  = max(1, (int)($t->custom_occurrences ?? 1));
            $paymentDay = (int)($r->payment_day ?? Carbon::parse($t->date)->day);
            $first      = Carbon::parse($t->date)->startOfDay()->day($paymentDay);
            $parcValue  = $this->inferCustomInstallmentAmount($t, $r, $totalParc);

            for ($i=1; $i <= $totalParc; $i++) {
                $dt = $first->copy()->addMonthsNoOverflow($i-1)->day($paymentDay);
                if ($dt->betweenIncluded($from, $to)) {
                    $val = $i === $totalParc ? $this->fixLastInstallment($parcValue, $totalParc, $t->amount) : $parcValue;
                    $out->push($this->mapTxLike($t, $dt, $val, 'custom', $i));
                }
            }
        }

        return $out;
    }

    protected function expandRecurrentsCustomDays(array $userIds, Carbon $from, Carbon $to): Collection
    {
        // pega recorrentes custom cujo intervalo é em DIAS
        $recs = \App\Models\Recurrent::withoutGlobalScopes()
            ->with(['transaction' => function($q) use ($userIds) {
                $q->withoutGlobalScopes()
                    ->with(['transactionCategory:id,name,type'])
                    ->whereIn('transactions.user_id', $userIds)
                    ->select('id','user_id','transaction_category_id','title','amount','date','type','type_card','card_id','recurrence_type');
            }])
            ->whereIn('recurrents.user_id', $userIds)
            ->where('interval_unit', 'days')
            ->whereHas('transaction', function ($q) {
                $q->where('recurrence_type', 'custom')
                    ->where(function ($q2) {
                        $q2->where('transactions.type', '!=', 'card')
                            ->orWhereNull('transactions.type')
                            ->orWhere(function ($qq) {
                                $qq->where('transactions.type', 'card')
                                    ->where('transactions.type_card', '!=', 'credit');
                            });
                    })
                    ->where(function ($q3) {
                        $q3->whereNull('transactions.title')
                            ->orWhereRaw('LOWER(transactions.title) NOT IN (?, ?, ?)', [
                                'total fatura','fatura total','total da fatura'
                            ]);
                    });
            })
            ->get([
                'id','user_id','transaction_id',
                'start_date','interval_unit','interval_value',
                'include_sat','include_sun','amount'
            ]);

        $out = collect();

        foreach ($recs as $r) {
            $t = $r->transaction; if (!$t) continue;

            // se existirem itens explícitos, deixamos outro bloco cuidar para não duplicar
            $hasItems = DB::table('custom_item_recurrents')->where('recurrent_id', $r->id)->exists();
            if ($hasItems) continue;

            // só projetar se for realmente "custom sem término" em DIAS
            if (trim((string)$r->interval_unit) !== 'days') continue;

            $startBase = Carbon::parse($t->date)->startOfDay();
            $start     = Carbon::parse($r->start_date ?: $startBase)->startOfDay();
            if ($start->lt($startBase)) $start = $startBase; // âncora >= data da transação

            $interval = max(1, (int)($r->interval_value ?? 1));
            $sat = (bool)$r->include_sat;
            $sun = (bool)$r->include_sun;

            // primeira ocorrência dentro da janela (alinhada ao step) e normalizada p/ fds
            $cursor = $this->firstAlignedDays($start, $from, $interval);
            $cursor = $this->normalizeW($cursor, $sat, $sun);

            // valor: usa amount do recurrent se houver, senão da transação
            $val = (float)($r->amount ?: $t->amount);

            while ($cursor->lte($to)) {
                $out->push($this->mapTxLike($t, $cursor, (float)$val, 'custom')); // recurrence='custom' mantém sua UI
                $cursor = $this->normalizeW($cursor->copy()->addDays($interval), $sat, $sun);
            }
        }

        return $out;
    }

    protected function inferCustomInstallmentAmount($t, $r, int $totalParc): float
    {
        if (!is_null($r->amount) && (float)$r->amount != 0.0) {
            return round((float)$r->amount, 2);
        }
        return round((float)$t->amount / $totalParc, 2);
    }

    protected function fixLastInstallment(float $parcValue, int $totalParc, $total): float
    {
        $sumNminus1 = round($parcValue * ($totalParc - 1), 2);
        return round(((float)$total) - $sumNminus1, 2);
    }

    protected function dateFromRefs(int $paymentDay, int $month, int $year): Carbon
    {
        $m = max(1, min(12, $month ?: 1));
        $d = max(1, min(28, $paymentDay ?: 1));
        return Carbon::create($year ?: now()->year, $m, $d)->startOfDay();
    }

    /** ===== Fatura de cartão ===== */
    protected function cardBillsFromOccurrences(array $userIds, Carbon $from, Carbon $to, Collection $occ): array
    {
        $cards = Card::withoutGlobalScopes()
            ->whereIn('cards.user_id', $userIds)
            ->get(['id','cardholder_name','closing_day','due_day']);

        if ($cards->isEmpty()) return [];

        $firstMonth = $from->copy()->startOfMonth();
        $lastMonth  = $to->copy()->startOfMonth()->addMonth();

        $bills = [];
        foreach ($cards as $card) {
            $m = $firstMonth->copy();
            while ($m->lte($lastMonth)) {
                $closeDay = (int)($card->closing_day ?: $m->daysInMonth);
                $dueDay   = (int)($card->due_day ?: 1);

                $cycleStart = $m->copy()->subMonth()->day(min($closeDay, $m->copy()->subMonth()->daysInMonth))->addDay();
                $cycleEnd   = $m->copy()->day(min($closeDay, $m->daysInMonth));
                $dueDate    = $m->copy()->day(min($dueDay, $m->daysInMonth));

                $sum = $occ->filter(function($o) use ($card, $cycleStart, $cycleEnd){
                    if (($o['type'] ?? null) !== 'card') return false;
                    if (($o['type_card'] ?? null) !== 'credit') return false;
                    if (($o['card_id'] ?? null) != $card->id) return false;
                    $dt = Carbon::parse($o['date']);
                    return $dt->betweenIncluded($cycleStart, $cycleEnd);
                })
                    ->sum(fn($o) => (float)$o['amount']);

                $total = abs(min(0, $sum));

                if ($total > 0 && $dueDate->betweenIncluded($from, $to)) {
                    $bills[] = [
                        'id'        => "bill_{$card->id}_".$dueDate->format('Ym'),
                        'title'     => 'Fatura '.$card->cardholder_name.' (venc. '.$dueDate->format('d/m').')',
                        'amount'    => -$total,
                        'date'      => $dueDate->toDateString(),
                        'type'      => 'invoice',
                        'type_card' => 'credit',
                        'card_id'   => $card->id,
                        'category'  => 'Fatura Cartão',
                        'is_invoice'=> true,
                    ];
                }

                $m->addMonth();
            }
        }

        return $bills;
    }

    /** ===== Consolidação ===== */
    protected function consolidateDays(Carbon $from, Carbon $to, float $opening, Collection $occ, array $bills): array
    {
        $days = [];
        $cur = $from->copy();
        while ($cur->lte($to)) {
            $k = $cur->toDateString();
            $days[$k] = ['date'=>$k, 'in'=>0.0, 'out'=>0.0, 'net'=>0.0, 'balance'=>0.0, 'items'=>[]];
            $cur->addDay();
        }

        foreach ($occ as $o) {
            $k = $o['date'];
            if (!isset($days[$k])) continue;
            $amt = (float)$o['amount'];
            if ($amt >= 0) $days[$k]['in'] += $amt; else $days[$k]['out'] += abs($amt);
            $days[$k]['items'][] = $o;
        }

        foreach ($bills as $b) {
            $k = $b['date'];
            if (!isset($days[$k])) continue;
            $days[$k]['out'] += abs((float)$b['amount']);
            $days[$k]['items'][] = $b;
        }

        $run = $opening;
        foreach ($days as $k => &$d) {
            $d['in']  = round($d['in'], 2);
            $d['out'] = round($d['out'], 2);
            $d['net'] = round($d['in'] - $d['out'], 2);
            $run = round($run + $d['net'], 2);
            $d['balance'] = $run;

            usort($d['items'], function($a,$b){
                $sa = (float)$a['amount'] >= 0 ? 0 : 1;
                $sb = (float)$b['amount'] >= 0 ? 0 : 1;
                return $sa <=> $sb ?: strcmp($a['title'] ?? '', $b['title'] ?? '');
            });
        }
        unset($d);

        return $days;
    }

    /** ===== Helpers de mapeamento ===== */
    protected function mapTx($t, Carbon $date): array
    {
        $catType = $t->transactionCategory?->type ?? 'despesa';
        $amt = (float)$t->amount;
        $amt = ($catType === 'entrada') ? abs($amt) : -abs($amt);

        return [
            'id'         => (string)$t->id,
            'title'      => $t->title ?? ($t->transactionCategory?->name ?? 'Lançamento'),
            'amount'     => round($amt, 2),
            'date'       => $date->toDateString(),
            'type'       => $t->type,
            'type_card'  => $t->type_card,
            'card_id'    => $t->card_id,
            'category'   => $t->transactionCategory?->name,
            'is_invoice' => false,
        ];
    }

    protected function mapTxLike($t, Carbon $date, float $amount, string $rt, ?int $installment = null): array
    {
        $fake = clone $t;
        $fake->amount = $amount;
        $arr = $this->mapTx($fake, $date);
        $arr['recurrence'] = $rt;
        if ($installment) $arr['installment'] = $installment;
        return $arr;
    }

    private function normalizeW(Carbon $d, bool $sat, bool $sun): Carbon
    {
        if (!$sat && $d->isSaturday()) $d->addDays(2);
        if (!$sun && $d->isSunday())   $d->addDay();
        return $d;
    }

    /** Retorna a primeira data >= $from, alinhada ao step de $interval dias a partir de $start  */
    private function firstAlignedDays(Carbon $start, Carbon $from, int $interval): Carbon
    {
        $s = $start->copy();
        if ($s->lt($from)) {
            $diff  = $s->diffInDays($from);
            $steps = intdiv($diff + $interval - 1, $interval); // ceil
            $s->addDays($steps * $interval);
        }
        return $s;
    }
}
