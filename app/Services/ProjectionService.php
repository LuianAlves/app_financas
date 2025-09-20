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
    protected array $paidIdx = [];

    public function build(?string $userId, string $start, string $end): array
    {
        $uid  = $userId ?: \Illuminate\Support\Facades\Auth::id();
        [$ownerId, $userIds] = $this->resolveOwnerAndAdditionals($uid);

        $tz   = 'America/Sao_Paulo';
        $from = \Carbon\Carbon::parse($start, $tz)->startOfDay();
        $to   = \Carbon\Carbon::parse($end,   $tz)->endOfDay();

        $this->paidIdx = $this->paymentsIndex($userIds);

        // saldo anterior correto (com fallback)
        $opening = $this->openingBalance($userIds, $from);

        $occ = collect()
            ->merge($this->expandAccountMovements($userIds, $from, $to))           // <<< AQUI
            ->merge($this->expandPaymentTransactions($userIds, $from, $to))
            ->merge($this->expandUnique($userIds, $from, $to))
            ->merge($this->expandRecurrentsMonthlyYearly($userIds, $from, $to))
            ->merge($this->expandRecurrentsCustom($userIds, $from, $to))
            ->merge($this->expandRecurrentsCustomDays($userIds, $from, $to));

        $occ = $occ
            ->reject(fn($o) => ($o['type'] ?? null) === 'card' && ($o['type_card'] ?? null) === 'credit')
            ->unique(fn($o) => (($o['id'] ?? $o['title']).'@'.$o['date']))
            ->values();

        $bills = $this->cardBillsFromInvoices($userIds, $from, $to);

        $days = $this->consolidateDays($from, $to, $opening, $occ, $bills);

        return [
            'opening_balance' => round($opening, 2),
            'total_in'        => round(array_sum(array_column($days, 'in')), 2),
            'total_out'       => round(array_sum(array_column($days, 'out')), 2),
            'ending_balance'  => round(end($days)['balance'] ?? $opening, 2),
            'days'            => array_values($days),
        ];
    }

    protected function paymentsIndex(array $userIds): array
    {
        $rows = DB::table('payment_transactions as pt')
            ->join('transactions as t', 't.id', '=', 'pt.transaction_id')
            ->whereIn('t.user_id', $userIds)
            ->get(['pt.transaction_id','pt.payment_date','pt.reference_year','pt.reference_month']);

        $byMonth = [];
        $byDate  = [];
        $anyMin  = [];

        foreach ($rows as $r) {
            if ($r->reference_year && $r->reference_month) {
                $ym = sprintf('%04d-%02d', (int)$r->reference_year, (int)$r->reference_month);
                $byMonth[$r->transaction_id][$ym] = true;
            }
            if ($r->payment_date) {
                $d = Carbon::parse($r->payment_date)->toDateString();
                $byDate[$r->transaction_id][$d] = true;
                $anyMin[$r->transaction_id] = isset($anyMin[$r->transaction_id])
                    ? min($anyMin[$r->transaction_id], $d)
                    : $d;
            }
        }

        return ['byMonth' => $byMonth, 'byDate' => $byDate, 'anyMin' => $anyMin];
    }

    protected function expandPaymentTransactions(array $userIds, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $rows = DB::table('payment_transactions as pt')
            ->join('transactions as t', 't.id', '=', 'pt.transaction_id')
            ->leftJoin('transaction_categories as tc', 'tc.id', '=', 't.transaction_category_id')
            ->whereIn('t.user_id', $userIds)
            ->whereBetween('pt.payment_date', [$from->toDateString(), $to->toDateString()])
            ->get([
                'pt.id as pid','pt.amount as pamount','pt.payment_date',
                't.id as tid','t.title','t.type','t.type_card','t.card_id',
                'tc.name as cat','tc.type as cat_type'
            ]);

        return collect($rows)->map(function($r){
            $amt = (float)$r->pamount;
            $amt = ($r->cat_type === 'entrada') ? abs($amt) : -abs($amt);

            return [
                'id'         => "pay_{$r->pid}",
                'title'      => $r->title ?: ($r->cat ?: 'Pagamento'),
                'amount'     => round($amt, 2),
                'date'       => Carbon::parse($r->payment_date)->toDateString(),
                'type'       => $r->type,
                'type_card'  => $r->type_card,
                'card_id'    => $r->card_id,
                'category'   => $r->cat ?: 'Pagamento',
                'is_invoice' => false,
            ];
        });
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

    // App/Services/ProjectionService.php
    protected function openingBalance(array $userIds, \Carbon\Carbon $from = null): float
    {
        if ($from) {
            $accIds = DB::table('accounts')->whereIn('user_id', $userIds)->pluck('id');
            if ($accIds->isEmpty()) return 0.0;

            return (float) DB::table('account_movements')
                ->whereIn('account_id', $accIds)
                ->where('occurred_at', '<', $from->copy()->startOfDay())
                ->sum('amount'); // sem fallback para current_balance
        }

        // sem $from → pode usar saldo atual como resumo geral
        return (float) \App\Models\Account::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->sum('current_balance');
    }

    protected function expandAccountMovements(array $userIds, \Carbon\Carbon $from, \Carbon\Carbon $to): \Illuminate\Support\Collection
    {
        $accIds = DB::table('accounts')->whereIn('user_id', $userIds)->pluck('id');
        if ($accIds->isEmpty()) return collect();

        $rows = DB::table('account_movements as am')
            ->leftJoin('accounts as a', 'a.id', '=', 'am.account_id') // conta do movimento
            // encontra a "outra ponta" da transferência (se houver)
            ->leftJoin('account_movements as other', function($j){
                $j->on('other.transfer_group_id', '=', 'am.transfer_group_id')
                    ->whereColumn('other.account_id', '!=', 'am.account_id');
            })
            ->leftJoin('accounts as a2', 'a2.id', '=', 'other.account_id') // conta contraparte
            ->whereIn('am.account_id', $accIds)
            ->whereBetween('am.occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            // evita duplicar com pagamentos/ faturas mostrados por outros blocos
            ->whereNull('am.payment_transaction_id')
            ->whereNull('am.invoice_id')
            ->orderBy('am.occurred_at')
            ->get([
                'am.id','am.type','am.amount','am.occurred_at','am.description',
                'am.account_id','am.transfer_group_id',
                'a.bank_name as acc_name',
                'a2.bank_name as counter_acc_name'
            ]);

        return collect($rows)->map(function($r){
            // título padrão por tipo
            $title = $r->description ?: match($r->type){
                'transfer_out' => 'Transferência para '.($r->counter_acc_name ?: 'outra conta'),
                'transfer_in'  => 'Transferência recebida de '.($r->counter_acc_name ?: 'outra conta'),
                'deposit'      => 'Depósito',
                'withdraw'     => 'Saque',
                'fee'          => 'Tarifa',
                'interest'     => 'Juros/Rendimento',
                'correction'   => 'Ajuste',
                'saving_out'   => 'Conta → Cofrinho',
                'saving_in'    => 'Cofrinho → Conta',
                'opening'      => 'Saldo inicial',
                default        => ucfirst(str_replace('_',' ',$r->type)),
            };

            return [
                'id'         => (string)$r->id,
                'title'      => $title,
                'amount'     => round((float)$r->amount, 2), // + crédito / - débito
                'date'       => \Carbon\Carbon::parse($r->occurred_at)->toDateString(),
                'type'       => 'ledger',
                'category'   => 'Movimentação de conta',
                'account_name'         => $r->acc_name,
                'counter_account_name' => $r->counter_acc_name,
                'is_invoice' => false,
            ];
        });
    }
    protected function expandUnique(array $userIds, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $rows = Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,name,type'])
            ->whereIn('transactions.user_id', $userIds)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$from->toDateString(), $to->toDateString()])
            ->get(['id','user_id','transaction_category_id','title','amount','date','type','type_card','card_id']);

        // se pagou (em qualquer data), não mostra no vencimento
        $rows = $rows->reject(function ($t) {
            return !empty($this->paidIdx['anyMin'][$t->id]);
        });

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
            ->whereHas('transaction', fn($q) => $q->whereIn('recurrence_type',['monthly','yearly']))
            // <<< NÃO trazer aqueles com itens custom gerados (com término)
            ->whereNotExists(function($q){
                $q->select(DB::raw(1))
                    ->from('custom_item_recurrents as cir')
                    ->whereColumn('cir.recurrent_id','recurrents.id');
            })
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
                    $ym = $cursor->format('Y-m');

                    if (empty($this->paidIdx['byMonth'][$t->id][$ym])) {
                        $out->push($this->mapTxLike($t, $cursor, $amount, 'monthly'));
                    }
                    // AVANÇA MÊS SEMPRE
                    $cursor->addMonthNoOverflow()->day($paymentDay);
                }
            } else { // yearly
                $monthAnchor = (int) $startBase->month;
                $cursor = Carbon::create($cursor->year, $monthAnchor, min($paymentDay, 28))->startOfDay();
                if ($cursor->lt($from)) $cursor->addYear();

                while ($cursor->lte($to)) {
                    $ym = $cursor->format('Y-m');

                    if (empty($this->paidIdx['byMonth'][$t->id][$ym])) {
                        $out->push($this->mapTxLike($t, $cursor, $amount, 'yearly')); // ← label correta
                    }
                    // AVANÇA ANO
                    $cursor->addYear();
                }
            }
        }
        return $out;
    }

    protected function expandRecurrentsCustom(array $userIds, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $recs = Recurrent::withoutGlobalScopes()
            ->with(['transaction' => function($q) use ($userIds) {
                $q->withoutGlobalScopes()
                    ->with(['transactionCategory:id,name,type'])
                    ->whereIn('transactions.user_id', $userIds)
                    ->select('id','user_id','transaction_category_id','title','amount','date','type','type_card','card_id','recurrence_type');
            }])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', fn($q) => $q->whereIn('recurrence_type', ['custom','monthly','yearly']))
            // ⬇️ somente quem TEM itens em custom_item_recurrents
            ->whereExists(function($q){
                $q->select(DB::raw(1))
                    ->from('custom_item_recurrents as cir')
                    ->whereColumn('cir.recurrent_id','recurrents.id');
            })
            ->get(['id','user_id','transaction_id']);

        $out = collect();

        foreach ($recs as $r) {
            $t = $r->transaction; if (!$t) continue;

            // 2.1) Itens explícitos
            $items = CustomItemRecurrents::where('recurrent_id',$r->id)
                ->get(['payment_day','reference_month','reference_year','amount','custom_occurrence_number','paid_at']);

            // índices de pagamento
            $paidByDate  = $this->paidIdx['byDate'][$t->id] ?? [];
            $paidByMonth = $this->paidIdx['byMonth'][$t->id] ?? [];

            foreach ($items as $ci) {
                $dt = $this->dateFromRefs((int)$ci->payment_day, (int)$ci->reference_month, (int)$ci->reference_year);

                // SE JÁ PAGO (tem paid_at) OU EXISTE PT NO MÊS/DATA → NÃO EMITE
                if (!empty($ci->paid_at)) continue;
                if (!empty($paidByDate[$dt->toDateString()])) continue;
                if (!empty($paidByMonth[$dt->format('Y-m')])) continue;

                if ($dt->betweenIncluded($from,$to)) {
                    $out->push($this->mapTxLike($t, $dt, (float)$ci->amount, 'custom', $ci->custom_occurrence_number));
                }
            }

            // ... mantém o bloco 2.2 (cada X dias sem término) como está,
            //     mas com o filtro de paidByDate igual ao da seção A (se aplicável).
            // (se quiser, replique o mesmo filtro dentro do while desse bloco)
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

            // se existirem itens explícitos, outro bloco cuida
            $hasItems = DB::table('custom_item_recurrents')->where('recurrent_id', $r->id)->exists();
            if ($hasItems) continue;

            if (trim((string)$r->interval_unit) !== 'days') continue;

            $startBase = Carbon::parse($t->date)->startOfDay();
            $start     = Carbon::parse($r->start_date ?: $startBase)->startOfDay();
            if ($start->lt($startBase)) $start = $startBase;

            $interval = max(1, (int)($r->interval_value ?? 1));
            $sat = (bool)$r->include_sat;
            $sun = (bool)$r->include_sun;

            $cursor = $this->firstAlignedDays($start, $from, $interval);
            $cursor = $this->normalizeW($cursor, $sat, $sun);

            $val = (float)($r->amount ?: $t->amount);

            // índice de pagamentos por data para este transaction_id
            $paidByDate = $this->paidIdx['byDate'][$t->id] ?? [];

            while ($cursor->lte($to)) {
                $iso = $cursor->toDateString();

                // SE JÁ HOUVER PAGAMENTO NESSA DATA → NÃO EMITE
                if (!empty($paidByDate[$iso])) {
                    $cursor = $this->normalizeW($cursor->copy()->addDays($interval), $sat, $sun);
                    continue;
                }

                $out->push($this->mapTxLike($t, $cursor, (float)$val, 'custom'));
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

    protected function cardBillsFromInvoices(array $userIds, Carbon $from, Carbon $to): array
    {
        $rows = DB::table('invoices as inv')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->leftJoin('invoice_payments as ip', 'ip.invoice_id', '=', 'inv.id') // pega paid_at
            ->whereIn('inv.user_id', $userIds)
            ->groupBy('inv.id','inv.card_id','inv.current_month','inv.paid','c.cardholder_name','c.due_day','ip.paid_at')
            ->get([
                'inv.id','inv.card_id','inv.current_month','inv.paid',
                'c.cardholder_name','c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total'),
                'ip.paid_at'
            ]);

        $bills = [];

        foreach ($rows as $r) {
            $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $due  = $base->copy()->day(min((int)$r->due_day ?: 1, $base->daysInMonth));
            $total = (float)$r->total;
            if ($total <= 0) continue;

            $eventDate = ($r->paid && $r->paid_at) ? Carbon::parse($r->paid_at) : $due;

            if ($eventDate->betweenIncluded($from, $to)) {
                $bills[] = [
                    'id'        => (string)$r->id,
                    'title'     => 'Fatura '.$r->cardholder_name.' (venc. '.$due->format('d/m').')',
                    'amount'    => -round($total, 2),
                    'date'      => $eventDate->toDateString(),
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
