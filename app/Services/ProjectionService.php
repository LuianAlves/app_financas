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

class ProjectionService
{
    public function build(string $userId, string $start, string $end): array
    {
        $tz   = 'America/Sao_Paulo';
        $from = Carbon::parse($start, $tz)->startOfDay();
        $to   = Carbon::parse($end, $tz)->endOfDay();

        $opening = $this->openingBalance($userId);

        // 1) Lançamentos: únicos + recorrentes (monthly/yearly/custom)
        $occ = collect();
        $occ = $occ
            ->merge($this->expandUnique($userId, $from, $to))
            ->merge($this->expandRecurrentsMonthlyYearly($userId, $from, $to))
            ->merge($this->expandRecurrentsCustom($userId, $from, $to));

        // 2) Faturas de cartão (gera UMA saída no due_day por cartão/ciclo)
        $bills = $this->cardBillsFromOccurrences($userId, $from, $to, $occ);

        // 3) Consolidar extrato diário
        $days = $this->consolidateDays($from, $to, $opening, $occ, $bills);

        return [
            'opening_balance' => round($opening, 2),
            'total_in'        => round(array_sum(array_column($days, 'in')), 2),
            'total_out'       => round(array_sum(array_column($days, 'out')), 2),
            'ending_balance'  => round(end($days)['balance'] ?? $opening, 2),
            'days'            => array_values($days),
        ];
    }

    /** ===== regras ===== */

    protected function openingBalance(string $userId): float
    {
        // usa saldo atual das contas como "abertura"
        return (float) Account::where('user_id', $userId)->sum('current_balance');
    }

    protected function expandUnique(string $userId, Carbon $from, Carbon $to): Collection
    {
        $rows = Transaction::query()
            ->with('transactionCategory:id,name,type') // type: entrada|despesa|investimento
            ->where('user_id', $userId)
            ->where('recurrence_type', 'unique')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        return $rows->map(fn($t) => $this->mapTx($t, Carbon::parse($t->date)));
    }

    protected function expandRecurrentsMonthlyYearly(string $userId, Carbon $from, Carbon $to): Collection
    {
        // Regrinha: pega todos os recurrents (ligados a transaction) cujo recurrence_type seja monthly ou yearly.
        $recurrents = Recurrent::query()
            ->with(['transaction.transactionCategory:id,name,type'])
            ->where('user_id', $userId)
            ->whereHas('transaction', function($q){
                $q->whereIn('recurrence_type', ['monthly','yearly']);
            })
            ->get();

        $out = collect();
        foreach ($recurrents as $r) {
            $t   = $r->transaction;
            if (!$t) continue;

            $paymentDay = max(1, (int)$r->payment_day); // string na tabela -> força inteiro simples
            $amount     = (float) $r->amount;           // valor por ocorrência (fixo todo mês/ano)
            $startBase  = Carbon::parse($t->date)->startOfDay();

            $cursor = $from->copy()->day($paymentDay);
            // se a transaction começou depois, alinha início
            if ($cursor->lt($startBase)) $cursor = $startBase->copy()->day($paymentDay);

            if ($t->recurrence_type === 'monthly') {
                // todo mês no payment_day
                while ($cursor->lte($to)) {
                    $out->push($this->mapTxLike($t, $cursor, $amount, 'monthly'));
                    $cursor->addMonthNoOverflow()->day($paymentDay);
                }
            } else { // yearly
                // 1x ao ano no payment_day do mês original da transaction
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

    protected function expandRecurrentsCustom(string $userId, Carbon $from, Carbon $to): Collection
    {
        // PRIORIDADE: se houver custom_item_recurrents, usa o cronograma.
        // Se não houver, divide por parcelas = transaction.custom_occurrences (mensalmente).
        $recs = Recurrent::query()
            ->with(['transaction.transactionCategory:id,name,type'])
            ->where('user_id', $userId)
            ->whereHas('transaction', fn($q) => $q->where('recurrence_type', 'custom'))
            ->get();

        $out = collect();

        foreach ($recs as $r) {
            $t = $r->transaction;
            if (!$t) continue;

            $items = CustomItemRecurrents::where('recurrent_id', $r->id)->get();
            if ($items->count()) {
                foreach ($items as $ci) {
                    $dt = $this->dateFromRefs((int)$ci->payment_day, (int)$ci->reference_month, (int)$ci->reference_year);
                    if ($dt->betweenIncluded($from, $to)) {
                        $out->push($this->mapTxLike($t, $dt, (float)$ci->amount, 'custom', $ci->custom_occurrence_number ?? null));
                    }
                }
                continue;
            }

            // fallback: gerar parcelas mensais a partir de transaction.date
            $totalParc = max(1, (int)($t->custom_occurrences ?? 1));
            $paymentDay = (int)($r->payment_day ?? Carbon::parse($t->date)->day);
            $first = Carbon::parse($t->date)->startOfDay()->day($paymentDay);
            $parcValue = $this->inferCustomInstallmentAmount($t, $r, $totalParc);

            for ($i=1; $i <= $totalParc; $i++) {
                $dt = $first->copy()->addMonthsNoOverflow($i-1)->day($paymentDay);
                if ($dt->betweenIncluded($from, $to)) {
                    // ajusta última parcela para fechar centavos
                    $val = $i === $totalParc ? $this->fixLastInstallment($parcValue, $totalParc, $t->amount) : $parcValue;
                    $out->push($this->mapTxLike($t, $dt, $val, 'custom', $i));
                }
            }
        }

        return $out;
    }

    protected function inferCustomInstallmentAmount($t, $r, int $totalParc): float
    {
        // Se o Recurrent.amount estiver preenchido, assume que JÁ é o valor por parcela.
        if (!is_null($r->amount) && (float)$r->amount != 0.0) {
            return round((float)$r->amount, 2);
        }
        // Caso contrário, divide o total da transação em N parcelas.
        $base = (float) $t->amount;
        // Se categoria for 'entrada', mantém sinal +; se 'despesa'/'investimento', mantém sinal -.
        return round($base / $totalParc, 2);
    }

    protected function fixLastInstallment(float $parcValue, int $totalParc, $total): float
    {
        $sumNminus1 = round($parcValue * ($totalParc - 1), 2);
        $last = round(((float)$total) - $sumNminus1, 2);
        return $last;
    }

    protected function dateFromRefs(int $paymentDay, int $month, int $year): Carbon
    {
        $m = max(1, min(12, $month ?: 1));
        $d = max(1, min(28, $paymentDay ?: 1)); // evita overflow
        return Carbon::create($year ?: now()->year, $m, $d)->startOfDay();
    }

    /** ===== Fatura de cartão ===== */
    protected function cardBillsFromOccurrences(string $userId, Carbon $from, Carbon $to, Collection $occ): array
    {
        // Considera SOMENTE lançamentos de cartão de crédito (despesas) projetados nas ocorrências
        $cards = Card::where('user_id', $userId)->get(['id','cardholder_name','closing_day','due_day']);
        if ($cards->isEmpty()) return [];

        // janela de meses a considerar
        $firstMonth = $from->copy()->startOfMonth();
        $lastMonth  = $to->copy()->startOfMonth()->addMonth();

        $bills = [];

        foreach ($cards as $card) {
            $m = $firstMonth->copy();
            while ($m->lte($lastMonth)) {
                $closeDay = (int)($card->closing_day ?: $m->daysInMonth);
                $dueDay   = (int)($card->due_day ?: 1);

                // Ciclo: (close do mês anterior + 1) até (close do mês atual)
                $cycleStart = $m->copy()->subMonth()->day(min($closeDay, $m->copy()->subMonth()->daysInMonth))->addDay();
                $cycleEnd   = $m->copy()->day(min($closeDay, $m->daysInMonth));
                $dueDate    = $m->copy()->day(min($dueDay, $m->daysInMonth));

                // soma despesas do cartão nesse ciclo
                $sum = $occ->filter(function($o) use ($card, $cycleStart, $cycleEnd){
                    if (($o['type'] ?? null) !== 'card') return false;
                    if (($o['type_card'] ?? null) !== 'credit') return false;
                    if (($o['card_id'] ?? null) != $card->id) return false;
                    $dt = Carbon::parse($o['date']);
                    return $dt->betweenIncluded($cycleStart, $cycleEnd);
                })
                    ->sum(fn($o) => (float)$o['amount']); // valores negativos (despesa)

                $total = abs(min(0, $sum)); // só se houver despesa (negativo)

                if ($total > 0 && $dueDate->betweenIncluded($from, $to)) {
                    $bills[] = [
                        'id'        => "bill_{$card->id}_".$dueDate->format('Ym'),
                        'title'     => 'Fatura '.$card->cardholder_name.' (venc. '.$dueDate->format('d/m').')',
                        'amount'    => -$total, // saída
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

    /** ===== Consolidação (extrato diário) ===== */
    protected function consolidateDays(Carbon $from, Carbon $to, float $opening, Collection $occ, array $bills): array
    {
        // bucket diário
        $days = [];
        $cur = $from->copy();
        while ($cur->lte($to)) {
            $k = $cur->toDateString();
            $days[$k] = ['date'=>$k, 'in'=>0.0, 'out'=>0.0, 'net'=>0.0, 'balance'=>0.0, 'items'=>[]];
            $cur->addDay();
        }

        // joga ocorrências
        foreach ($occ as $o) {
            $k = $o['date'];
            if (!isset($days[$k])) continue;
            $amt = (float)$o['amount'];
            if ($amt >= 0) $days[$k]['in'] += $amt; else $days[$k]['out'] += abs($amt);
            $days[$k]['items'][] = $o;
        }

        // joga faturas
        foreach ($bills as $b) {
            $k = $b['date'];
            if (!isset($days[$k])) continue;
            $days[$k]['out'] += abs((float)$b['amount']);
            $days[$k]['items'][] = $b;
        }

        // saldo acumulado
        $run = $opening;
        foreach ($days as $k => &$d) {
            $d['in']  = round($d['in'], 2);
            $d['out'] = round($d['out'], 2);
            $d['net'] = round($d['in'] - $d['out'], 2);
            $run = round($run + $d['net'], 2);
            $d['balance'] = $run;
            // ordena itens: entradas primeiro, depois saídas, ambos por título
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
        // Sinal pelo tipo da categoria: entrada = +, despesa/investimento = -
        $catType = $t->transactionCategory?->type ?? 'despesa';
        $amt = (float)$t->amount;
        $amt = ($catType === 'entrada') ? abs($amt) : -abs($amt);

        return [
            'id'         => (string)$t->id,
            'title'      => $t->title ?? ($t->transactionCategory?->name ?? 'Lançamento'),
            'amount'     => round($amt, 2),
            'date'       => $date->toDateString(),
            'type'       => $t->type,       // pix|card|money
            'type_card'  => $t->type_card,  // credit|debit|null
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
}
