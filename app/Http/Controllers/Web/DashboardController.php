<?php

namespace App\Http\Controllers\Web;

use App\Helpers\CardCycle;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AdditionalUser;
use App\Models\Card;
use App\Models\CustomItemRecurrents;
use App\Models\InvoicePayment;
use App\Models\PaymentTransaction;
use App\Models\Recurrent;
use App\Models\Saving;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $monthParam   = $request->query('month');
        $startOfMonth = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();
        $endOfMonth   = (clone $startOfMonth)->endOfMonth();
        $today        = Carbon::today();

        $recentTransactions = Transaction::with(['transactionCategory:id,name,type,color,icon', 'card'])
            ->whereIn('transactions.user_id', $userIds)
            ->orderByDesc('transactions.date')
            ->limit(5)
            ->get(['transactions.id','transactions.type','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        $upcomingPayments = Transaction::with(['transactionCategory:id,name,type,color,icon'])
            ->leftJoin('payment_transactions as pt', 'pt.transaction_id', '=', 'transactions.id') // novo
            ->whereIn('transactions.user_id', $userIds)
            ->whereHas('transactionCategory', fn($q) => $q->where('type', 'despesa'))
            ->whereNotNull('transactions.date')
            ->whereDate('transactions.date', '>=', $today)
            ->whereNull('pt.id') // novo: só as que ainda não foram pagas
            ->where('type', '!=', 'card')
            ->orderBy('transactions.date')
            ->limit(5)
            ->get(['transactions.id','transactions.title','transactions.amount','transactions.date','transactions.transaction_category_id']);

        $upcomingInvoiceCards = $this->buildUpcomingInvoicesForList($userIds, $today, 50);

        $tx = $upcomingPayments->map(function ($t) {
            return [
                'kind'           => 'tx',
                'id'             => (string) $t->id,
                'date'           => (string) $t->date,
                'title'          => $t->title ?? optional($t->transactionCategory)->name,
                'amount'         => (float) $t->amount,
                'color'          => optional($t->transactionCategory)->color,
                'icon'           => optional($t->transactionCategory)->icon,
                'modal_id'       => (string) $t->id,
                'modal_amount'   => (float) $t->amount,
                'modal_date'     => (string) $t->date,
            ];
        });

        $invs = collect($upcomingInvoiceCards)
            ->filter(fn ($r) => \Carbon\Carbon::parse($r['due_date'])->gte($today))
            ->map(function ($r) {
                return [
                    'kind'           => 'inv',
                    'id'             => (string) $r['invoice_id'] ?? ($r['card_id'].'-'.$r['current_month']),
                    'date'           => (string) $r['due_date'],
                    'title'          => (string) $r['title'],
                    'amount'         => (float)  $r['total'],
                    'color'          => '#be123c',
                    'icon'           => 'fa-solid fa-credit-card',
                    'card_id'        => (string) $r['card_id'],
                    'current_month'  => (string) $r['current_month'],
                ];
            });

        $upcomingAny = $tx
            ->toBase()
            ->concat($invs)
            ->filter(fn ($x) => !empty($x['date']))
            ->sortBy('date')
            ->take(5)
            ->values();

        $winStart = (clone $startOfMonth)->startOfMonth();
        $winEnd   = (clone $winStart)->addMonthsNoOverflow(11)->endOfMonth();

        $calendarEvents = $this->buildWindowEvents($userIds, $winStart, $winEnd)->values();

        [$currentInvoices, $cardTip] = $this->buildInvoicesWidget($userIds, $today);

        $kpis = $this->kpisForMonth($userIds, $startOfMonth, $endOfMonth);
        $accountsBalance = $kpis['accountsBalance'];
        $savingsBalance  = Saving::whereIn('savings.user_id', $userIds)->sum('current_amount');
        $total           = $kpis['saldoPrevisto']; // ou $kpis['saldoReal']

        return view('app.dashboard', compact(
            'accountsBalance','savingsBalance','total',
            'recentTransactions','calendarEvents',
            'startOfMonth','endOfMonth',
            'currentInvoices','cardTip',
            'upcomingAny'
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

        $paidRows = PaymentTransaction::query()
            ->join('transactions as t', 't.id', '=', 'payment_transactions.transaction_id')
            ->whereIn('t.user_id', $userIds)
            ->whereBetween('payment_transactions.payment_date', [$winStart, $winEnd])
            ->get([
                'payment_transactions.id',
                'payment_transactions.title',
                'payment_transactions.amount',
                'payment_transactions.payment_date',
            ]);

        foreach ($paidRows as $p) {
            $events->push([
                'id'    => "pay_{$p->id}",
                'title' => $p->title ?: 'Pagamento',
                'start' => Carbon::parse($p->payment_date)->toDateString(),
                'bg'    => '#0ea5e9',
                'icon'  => 'fa-regular fa-circle-check',
                'color' => '#0ea5e9',
                'extendedProps' => [
                    'amount' => (float)$p->amount,
                    'amount_brl' => brlPrice($p->amount),
                    'category_name' => 'Pagamento',
                    'type' => 'payment',
                ],
            ]);
        }

        $ipRows = DB::table('invoice_payments as ip')
            ->join('invoices as inv', 'inv.id', '=', 'ip.invoice_id')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->whereIn('inv.user_id', $userIds)
            ->whereBetween('ip.paid_at', [$winStart, $winEnd])
            ->get([
                'ip.id','ip.amount','ip.paid_at',
                'inv.card_id','inv.current_month',
                'c.cardholder_name','c.last_four_digits'
            ]);

        foreach ($ipRows as $p) {
            $firstName = explode(' ', trim((string)$p->cardholder_name))[0];

            $events->push([
                'id'    => "invpay_{$p->id}",
                'title' => "Fatura {$firstName} {$p->last_four_digits}", // mantém o nome da fatura
                'start' => Carbon::parse($p->paid_at)->toDateString(),
                'bg'    => '#0ea5e9',
                'icon'  => 'fa-regular fa-circle-check',
                'color' => '#0ea5e9',
                'extendedProps' => [
                    'amount'        => abs((float)$p->amount), // positivo
                    'amount_brl'    => function_exists('brlPrice') ? brlPrice(abs((float)$p->amount)) : number_format(abs((float)$p->amount), 2, ',', '.'),
                    'category_name' => 'Pagamento fatura',
                    'type'          => 'payment',
                    'is_invoice'    => true,
                    'paid'          => true,
                    'card_id'       => (string)$p->card_id,
                    'current_month' => $p->current_month,
                ],
            ]);
        }

        $uniqueTx = Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,name,type,color,icon'])
            ->leftJoin('payment_transactions as pt', 'pt.transaction_id', '=', 'transactions.id')
            ->whereIn('transactions.user_id', $userIds)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$winStart, $winEnd])
            ->whereNull('pt.id')
            ->where(function ($q) {
                $q->where('transactions.type', '!=', 'card')
                    ->orWhereNull('transactions.type')
                    ->orWhere(function ($qq) {
                        $qq->where('transactions.type', 'card')
                            ->where('transactions.type_card', '!=', 'credit');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('transactions.title')
                    ->orWhereRaw('LOWER(transactions.title) NOT IN (?, ?, ?)', [
                        'total fatura', 'fatura total', 'total da fatura'
                    ]);
            })
            ->get([
                'transactions.id','transactions.title','transactions.amount',
                'transactions.date','transactions.transaction_category_id'
            ]);

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
                    'amount' => (float)$t->amount,
                    'amount_brl' => brlPrice($t->amount),
                    'category_name' => $cat?->name,
                    'type' => $type,
                    'transaction_id' => (string)$t->id,
                ],
            ]);
        }

        $recMY = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('recurrence_type', ['monthly','yearly'])
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
                                'total fatura', 'fatura total', 'total da fatura'
                            ]);
                    });
            })
            ->get(['recurrents.id','recurrents.user_id','recurrents.transaction_id','recurrents.payment_day','recurrents.amount']);

        foreach ($recMY as $r) {
            $t = $r->transaction; if (!$t) continue;

            // proteção extra
            if ($this->isInvoiceControlTitle($t->title) || $t->type === 'card') continue;

            $cat        = $t->transactionCategory;
            $type       = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';
            $startBase  = Carbon::parse($t->date)->startOfDay();
            $paymentDay = max(1, (int) $r->payment_day);
            $amount     = (float) $r->amount;

            if ($t->recurrence_type === 'monthly') {
                $m = $winStart->copy()->startOfMonth();
                while ($m->lte($winEnd)) {
                    $occ = $m->copy()->day(min($paymentDay, $m->daysInMonth));
                    if ($occ->gte($startBase)) $events->push($this->ev($r->id,'m',$t,$cat,$type,$occ,$amount));
                    $m->addMonth();
                }
            } else { // yearly
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

        $recC = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
            ->whereIn('recurrents.user_id', $userIds)
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
                                'total fatura', 'fatura total', 'total da fatura'
                            ]);
                    });
            })
            ->get(['recurrents.id','recurrents.user_id','recurrents.transaction_id']);

        foreach ($recC as $r) {
            $t = $r->transaction; if (!$t) continue;

            if ($this->isInvoiceControlTitle($t->title) || $t->type === 'card') continue;

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

        // ===== 3b) RECORRENTES CUSTOM (X dias) SEM TÉRMINO (não têm itens em custom_item_recurrents)
        $recD = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,name,type,color,icon'])
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
            ->get(['recurrents.*']); // pega start_date, interval_value, include_sat/sun, amount, etc.

        foreach ($recD as $r) {
            $t = $r->transaction; if (!$t) continue;

            // se já existem itens explícitos p/ esse recurrent → pula (evita duplicar com bloco $recC)
            $hasItems = DB::table('custom_item_recurrents')->where('recurrent_id', $r->id)->exists();
            if ($hasItems) continue;

            $cat  = $t->transactionCategory;
            $type = in_array($cat?->type, ['entrada','despesa','investimento'], true) ? $cat->type : 'investimento';

            $start    = Carbon::parse($r->start_date)->startOfDay();
            $interval = max(1, (int)$r->interval_value);

            // primeira >= janela e alinhada ao step
            $cursor = $this->firstAlignedDays($start, $winStart, $interval);
            $cursor = $this->normalizeW($cursor, (bool)$r->include_sat, (bool)$r->include_sun);

            while ($cursor->lte($winEnd)) {
                if ($cursor->gte($start)) {
                    $events->push($this->ev($r->id, 'd', $t, $cat, $type, $cursor, (float)$r->amount));
                }
                $cursor = $this->normalizeW(
                    $cursor->copy()->addDays($interval),
                    (bool)$r->include_sat,
                    (bool)$r->include_sun
                );
            }
        }

        // ===== 4) FATURAS DE CARTÃO (um evento por invoice, na data de vencimento)
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
                'c.last_four_digits',
                'c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total'),
            ]);

        foreach ($rows as $r) {
            $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $due  = $base->copy()->day(min((int)($r->due_day ?: 1), $base->daysInMonth));
            $total = (float) $r->total;

            // pula faturas pagas: não aparecem no calendário
            if ((bool)$r->paid) continue;

            $firstName = explode(' ', trim((string)$r->cardholder_name))[0];

            if ($total > 0 && $due->betweenIncluded($winStart, $winEnd)) {
                $events->push([
                    'id'    => (string)$r->id,
                    'title' => "Fatura {$firstName} {$r->last_four_digits}",
                    'start' => $due->toDateString(),
                    'bg'    => '#be123c',
                    'icon'  => 'fa-solid fa-credit-card',
                    'color' => '#ef4444',
                    'extendedProps' => [
                        'amount'        => -abs($total), // despesa (negativo)
                        'amount_brl'    => function_exists('brlPrice') ? brlPrice(abs($total)) : number_format(abs($total), 2, ',', '.'),
                        'category_name' => 'Fatura Cartão',
                        'type'          => 'despesa',
                        'is_invoice'    => true,
                        'paid'          => false,
                        'card_id'       => (string)$r->card_id,
                        'current_month' => $r->current_month,
                    ],
                ]);
            }
        }

        return $events;
    }

    private function ev($rid, $kind, $t, $cat, $type, Carbon $occ, float $amount, ?int $n = null): array
    {
        return [
            'id'    => "rec_{$kind}_{$rid}_".$occ->format('Ymd').($n ? "_$n" : ''),
            'title' => $t->title ?? $cat?->name,
            'start' => $occ->toDateString(),
            'bg'    => $cat?->color,
            'icon'  => $cat?->icon,
            'color' => $type === 'despesa' ? '#ef4444' : ($type === 'entrada' ? '#22c55e' : '#0ea5e9'),
            'extendedProps' => [
                'amount' => $type === 'entrada' ? abs($amount) : -abs($amount),
                'amount_brl' => brlPrice($amount),
                'category_name' => $cat?->name,
                'type' => $type,
                'transaction_id' => (string)$t->id, // <- importante
            ],
        ];
    }

    /** Títulos que representam “controle de fatura” (não devem aparecer no calendário) */
    private function isInvoiceControlTitle(?string $title): bool
    {
        $t = mb_strtolower(trim((string)$title));
        return in_array($t, ['total fatura','fatura total','total da fatura'], true);
    }

    private function buildInvoicesWidget(Collection $userIds, Carbon $today): array
    {
        // Carrega cartões do “grupo”
        $cards = Card::withoutGlobalScopes()
            ->whereIn('cards.user_id', $userIds)
            ->get(['id','cardholder_name','last_four_digits','closing_day','due_day','credit_limit','color_card']); // troque o nome se necessário

        $result = [];
        foreach ($cards as $card) {
            // ciclo do mês corrente
            $cycleMonth = CardCycle::cycleMonthFor($today, (int)$card->closing_day);

            $row = $this->invoiceRow($userIds, $card->id, $cycleMonth);

            // se está pago ou não existe → pega o próximo ciclo
            if (!$row || $row->paid) {
                $nextMonth = Carbon::createFromFormat('Y-m', $cycleMonth)->addMonth()->format('Y-m');
                $row = $this->invoiceRow($userIds, $card->id, $nextMonth);
                $cycleMonth = $nextMonth;
            }

            if (!$row) {
                // sem fatura e sem itens → ainda assim mostramos “zerada” para UX
                $due = Carbon::createFromFormat('Y-m', $cycleMonth)->startOfMonth()
                    ->day(min((int)($card->due_day ?: 1), Carbon::createFromFormat('Y-m', $cycleMonth)->daysInMonth));
                $result[] = [
                    'card_id'         => (string)$card->id,
                    'title'           => trim($card->cardholder_name).' '.$card->last_four_digits,
                    'total'           => 0.00,
                    'total_brl'       => function_exists('brlPrice') ? brlPrice(0) : 'R$ 0,00',
                    'due_date'        => $due->toDateString(),
                    'due_label'       => $due->locale('pt_BR')->isoFormat('DD/MMM'),
                    'paid'            => false,
                    'current_month'   => $cycleMonth,
                    'color_card'      => $card->color_card,
                    'available_limit' => $this->availableLimit($card, 0),
                ];
                continue;
            }

            $base = Carbon::createFromFormat('Y-m', $cycleMonth)->startOfMonth();
            $due  = $base->copy()->day(min((int)($card->due_day ?: 1), $base->daysInMonth));
            $total = (float)$row->total;

            $result[] = [
                'card_id'         => (string)$card->id,
                'title'           => trim($card->cardholder_name).' '.$card->last_four_digits,
                'total'           => round($total, 2),
                'total_brl'       => function_exists('brlPrice') ? brlPrice($total) : number_format($total, 2, ',', '.'),
                'due_date'        => $due->toDateString(),
                'due_label'       => $due->locale('pt_BR')->isoFormat('DD/MMM'),
                'paid'            => (bool)$row->paid,
                'current_month'   => $cycleMonth,
                'color_card'      => $card->color_card,
                'available_limit' => $this->availableLimit($card, $total),
            ];
        }

        // sugestão de qual cartão usar:
        $tip = $this->suggestCardToUse($cards, $today);

        return [$result, $tip];
    }

    private function invoiceRow(Collection $userIds, string $cardId, string $cycleMonth): ?object
    {
        return DB::table('invoices as inv')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->whereIn('inv.user_id', $userIds)
            ->where('inv.card_id', $cardId)
            ->where('inv.current_month', $cycleMonth)
            ->groupBy('inv.id','inv.paid')
            ->select('inv.id','inv.paid', DB::raw('COALESCE(SUM(it.amount),0) as total'))
            ->first();
    }

    private function availableLimit(Card $card, float $openTotal): ?float
    {
        // ajuste o campo se necessário (ex.: $card->limit_total)
        if (!isset($card->credit_limit)) return null;
        return round((float)$card->credit_limit - max(0,$openTotal), 2);
    }

    private function suggestCardToUse(Collection $cards, Carbon $today): ?array
    {
        if ($cards->isEmpty()) return null;

        // Calcula último e próximo fechamento para cada cartão
        $data = $cards->map(function ($c) use ($today) {
            // Se tiver helpers CardCycle::lastClose/nextClose use-os; senão a lógica abaixo funciona igual
            $cm  = Carbon::create($today->year, $today->month, 1);
            $closeThisMonth = $cm->copy()->day(min((int)$c->closing_day, $cm->daysInMonth));

            $lastClose = $today->gte($closeThisMonth)
                ? $closeThisMonth
                : $cm->copy()->subMonth()->day(min((int)$c->closing_day, $cm->copy()->subMonth()->daysInMonth));

            $nextClose = $today->lt($closeThisMonth)
                ? $closeThisMonth
                : $cm->copy()->addMonth()->day(min((int)$c->closing_day, $cm->copy()->addMonth()->daysInMonth));

            return [
                'card'        => $c,
                'id'          => (string)$c->id,
                'last4'       => $c->last_four_digits,
                'color_card'  => $c->color_card,
                'last_close'  => $lastClose,
                'next_close'  => $nextClose,
                'last_ts'     => $lastClose->timestamp,
                'next_ts'     => $nextClose->timestamp,
            ];
        });

        // Cartão atual = o que teve o último fechamento mais recente (<= hoje)
        $current = $data->sortByDesc('last_ts')->first();
        if (!$current) return null;

        // Entre os OUTROS cartões, qual tem o próximo fechamento mais cedo?
        $others = $data->filter(fn ($x) => $x['id'] !== $current['id']);
        $switch = $others->sortBy('next_ts')->first(); // pode ser null se só existir 1 cartão

        // "Usar até" = próximo fechamento mais cedo entre os OUTROS; se não houver outros, usa o do próprio
        $useUntil = $switch['next_close'] ?? $current['next_close'];

        $useUntilLabel = strtoupper($useUntil->locale('pt_BR')->isoFormat('DD/MMM'));

        // Monta label: “Use o 2068 até 02/SET. Em seguida, use o 6277.”
        $label = "Utilize o cartão {$current['last4']} até {$useUntilLabel}.";
        if ($switch) {
            $label .= " Em seguida, use o {$switch['last4']}.";
        }

        return [
            'label'       => $label,
            'color'       => $current['color_card'] ?? '#000', // para colorir o ícone na view
            // extras úteis se quiser exibir/depurar
            'current' => [
                'id'         => $current['id'],
                'last4'      => $current['last4'],
                'last_close' => $current['last_close']->toDateString(),
                'next_close' => $current['next_close']->toDateString(),
                'use_until'  => $useUntil->toDateString(),
            ],
            'next' => $switch ? [
                'id'         => $switch['id'],
                'last4'      => $switch['last4'],
                'next_close' => $switch['next_close']->toDateString(),
            ] : null,
        ];
    }

    private function buildUpcomingInvoicesForList(Collection $userIds, Carbon $today, int $limit = 5): \Illuminate\Support\Collection
    {
        $rows = DB::table('invoices as inv')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->whereIn('inv.user_id', $userIds)
            ->where('inv.paid', false)
            ->groupBy('inv.id','inv.card_id','inv.current_month','c.cardholder_name','c.last_four_digits','c.due_day')
            ->select(
                'inv.id','inv.card_id','inv.current_month',
                'c.cardholder_name','c.last_four_digits','c.due_day',
                DB::raw('COALESCE(SUM(it.amount),0) as total')
            )
            ->get();

        $list = collect();
        foreach ($rows as $r) {
            $base   = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $dueDay = (int) ($r->due_day ?: 1);
            $due    = $base->copy()->day(min($dueDay, $base->daysInMonth));
            $total  = (float) $r->total;
            if ($total <= 0) continue; // ignora zeradas

            $list->push([
                'invoice_id'    => (string)$r->id,
                'card_id'       => (string)$r->card_id,
                'current_month' => $r->current_month,
                'title'         => 'Fatura '.trim($r->cardholder_name).' '.$r->last_four_digits,
                'due_date'      => $due->toDateString(),
                'total'         => round($total, 2),
                'total_brl'     => function_exists('brlPrice') ? brlPrice($total) : number_format($total, 2, ',', '.'),
                'overdue'       => $due->lt($today),
            ]);
        }

        // Ordena: vencidas primeiro (mais urgentes), depois próximas por data
        return $list
            ->sortBy([
                ['overdue', 'desc'],
                ['due_date', 'asc'],
            ])
            ->take($limit)
            ->values();
    }

//    private function kpisForMonth(Collection $userIds, Carbon $startOfMonth, Carbon $endOfMonth): array
//    {
//        $accountsBalance = (float) Account::whereIn('accounts.user_id', $userIds)->sum('current_balance');
//
//        $receivable = (float) Transaction::query()
//            ->join('transaction_categories as tc', 'tc.id','=','transactions.transaction_category_id')
//            ->leftJoin('payment_transactions as pt', 'pt.transaction_id','=','transactions.id')
//            ->whereIn('transactions.user_id', $userIds)
//            ->whereBetween('transactions.date', [$startOfMonth, $endOfMonth])
//            ->whereRaw('LOWER(TRIM(tc.type)) = ?', ['entrada'])
//            ->whereNull('pt.id')                      // ainda não confirmado
//            ->sum('transactions.amount');
//
//        $payableTx = (float) Transaction::query()
//            ->join('transaction_categories as tc', 'tc.id','=','transactions.transaction_category_id')
//            ->leftJoin('payment_transactions as pt', 'pt.transaction_id','=','transactions.id')
//            ->whereIn('transactions.user_id', $userIds)
//            ->whereBetween('transactions.date', [$startOfMonth, $endOfMonth])
//            ->whereRaw('LOWER(TRIM(tc.type)) = ?', ['despesa'])
//            ->where(function ($q) {
//                $q->where('transactions.type','!=','card')
//                    ->orWhereNull('transactions.type')
//                    ->orWhere(fn($qq)=>$qq->where('transactions.type','card')->where('transactions.type_card','!=','credit'));
//            })
//            ->whereNull('pt.id')                      // ainda não confirmado
//            ->sum('transactions.amount');
//
//        $openInvoices = (float) DB::table('invoices as inv')
//            ->leftJoin('invoice_items as it','it.invoice_id','=','inv.id')
//            ->join('cards as c','c.id','=','inv.card_id')
//            ->whereIn('inv.user_id', $userIds)
//            ->where('inv.paid', false)
//            ->select('inv.current_month','c.due_day', DB::raw('COALESCE(SUM(it.amount),0) as total'))
//            ->groupBy('inv.id','inv.current_month','c.due_day')
//            ->get()
//            ->reduce(function ($sum,$row) use ($startOfMonth,$endOfMonth){
//                $base = Carbon::createFromFormat('Y-m',$row->current_month)->startOfMonth();
//                $due  = $base->copy()->day(min((int)($row->due_day ?: 1), $base->daysInMonth));
//                if ($due->betweenIncluded($startOfMonth,$endOfMonth)) $sum += (float)$row->total;
//                return $sum;
//            }, 0.0);
//
//        $aPagar = abs($payableTx) + abs($openInvoices);
//
//        $saldoReal     = $accountsBalance;                     // agora (sem projeção)
//        $saldoPrevisto = $accountsBalance + $receivable - $aPagar;
//
//        return [
//            'accountsBalance' => round($accountsBalance, 2),
//            'aReceber'        => round($receivable, 2),
//            'aPagar'          => round($aPagar, 2),
//            'saldoReal'       => round($saldoReal, 2),
//            'saldoPrevisto'   => round($saldoPrevisto, 2),
//        ];
//    }

    private function kpisForMonth(Collection $userIds, Carbon $startOfMonth, Carbon $endOfMonth): array {
        return $this->kpisForRange($userIds, $startOfMonth, $endOfMonth);
    }

    public function kpis(Request $request)
    {
        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::where('user_id', $ownerId)
            ->pluck('linked_user_id')->push($ownerId)->unique()->values();

        $monthParam = $request->query('month', now()->format('Y-m'));
        $cumulative = (int) $request->query('cumulative', 1); // 1 = projeção (padrão), 0 = apenas o mês

        $startOfMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        $endOfMonth   = (clone $startOfMonth)->endOfMonth();

        // Se cumulative=1: de HOJE até fim do mês alvo (projeção)
        // Se cumulative=0: do início ao fim do mês alvo (visão mensal)
        $rangeStart = $cumulative ? Carbon::today()->startOfDay() : $startOfMonth;

        $kpis = $this->kpisForRange($userIds, $rangeStart, $endOfMonth);

        // resposta padronizada para o JS
        return response()->json([
            'accountsBalance'     => $kpis['accountsBalance'],
            'savingsBalance'      => $kpis['savingsBalance'],        // novo
            'aReceber'            => $kpis['aReceber'],
            'aPagar'              => $kpis['aPagar'],
            'saldoPrevisto'       => $kpis['saldoPrevisto'],
            'accountsBalance_brl' => brlPrice($kpis['accountsBalance']),
            'savingsBalance_brl'  => brlPrice($kpis['savingsBalance']), // novo
            'aReceber_brl'        => brlPrice($kpis['aReceber']),
            'aPagar_brl'          => brlPrice($kpis['aPagar']),
            'saldoPrevisto_brl'   => brlPrice($kpis['saldoPrevisto']),
        ]);

    }

    public function paymentTransaction(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

        $data = $request->validate([
            'amount'       => ['required','numeric'],
            'payment_date' => ['required','date'],
            'month'        => ['nullable','date_format:Y-m'], // <- vem do JS
        ]);

        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::where('user_id', $ownerId)
            ->pluck('linked_user_id')->push($ownerId)->unique()->values();

        DB::transaction(function () use ($transaction, $data, $userIds) {
            $account = null;

            if (!empty($data['account_id'])) {
                $account = Account::whereIn('user_id', $userIds)
                    ->where('id', $data['account_id'])
                    ->first();
            }

            if (!$account) {
                if (!empty($transaction->account_id)) {
                    $account = Account::whereIn('user_id', $userIds)
                        ->where('id', $transaction->account_id)
                        ->first();
                }
            }

            if (!$account) {
                $account = Account::whereIn('user_id', $userIds)->orderBy('id')->first();
            }

            if ($transaction->recurrence_type === 'unique') {
                $already = PaymentTransaction::where('transaction_id', $transaction->id)->exists();

                if ($already) {
                    return response()->json(['ok' => false, 'message' => 'Transação única já foi paga.'], 409);
                }
            }


            $pt = PaymentTransaction::create([
                'transaction_id'  => $transaction->id,
                'title'           => $transaction->title,
                'amount'          => (float)$data['amount'],
                'payment_date'    => $data['payment_date'],
                'reference_month' => Carbon::parse($transaction->date)->format('m'),
                'reference_year'  => Carbon::parse($transaction->date)->format('Y'),
                'account_id'      => $account?->id,
            ]);

            if ($account) {
                $type = optional($transaction->transactionCategory)->type; // 'despesa' | 'entrada' | 'investimento'...

                if ($type === 'despesa') {
                    // pagar despesa => debita
                    $account->decrement('current_balance', abs((float)$pt->amount));
                } else {
                    // receber entrada (ou investimento voltando para conta) => credita
                    $account->increment('current_balance', abs((float)$pt->amount));
                }
            }

            if (in_array($transaction->recurrence_type, ['monthly','yearly','custom'], true)) {
                $next = Carbon::parse($transaction->date)->addMonthNoOverflow();
                $transaction->update(['date' => $next]);
            }
        });

        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::where('user_id', $ownerId)
            ->pluck('linked_user_id')->push($ownerId)->unique()->values();

        $month      = $data['month'] ?? now()->format('Y-m');
        $startMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endMonth   = (clone $startMonth)->endOfMonth();

        $kpis = $this->kpisForMonth($userIds, $startMonth, $endMonth);

        return response()->json($this->kpisJson($kpis));
    }

    private function kpisJson(array $k): array
    {
        $saldo = $k['saldoMes'] ?? ($k['saldoPrevisto'] ?? ($k['saldoReal'] ?? 0));

        return [
            'ok'                  => true,
            'accountsBalance'     => (float)($k['accountsBalance'] ?? 0),
            'aReceber'            => (float)($k['aReceber'] ?? 0),
            'aPagar'              => (float)($k['aPagar'] ?? 0),
            'saldoMes'            => (float)$saldo,
            'accountsBalance_brl' => brlPrice($k['accountsBalance'] ?? 0),
            'aReceber_brl'        => brlPrice($k['aReceber'] ?? 0),
            'aPagar_brl'          => brlPrice($k['aPagar'] ?? 0),
            'saldoMes_brl'        => brlPrice($saldo),
        ];
    }

    private function kpisForRange(Collection $userIds, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $accountsBalance = (float) Account::whereIn('accounts.user_id', $userIds)
            ->sum('current_balance');

        // === NOVO: entradas/saídas projetadas por ocorrências
        ['aReceber' => $receivable, 'aPagar' => $payableTx] =
            $this->sumProjectedEntriesAndExpenses($userIds, $rangeStart, $rangeEnd);

        // Faturas abertas (mantém como estava)
        $openInvoices = (float) DB::table('invoices as inv')
            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'inv.id')
            ->join('cards as c', 'c.id', '=', 'inv.card_id')
            ->whereIn('inv.user_id', $userIds)
            ->where('inv.paid', false)
            ->select('inv.current_month','c.due_day', DB::raw('COALESCE(SUM(it.amount),0) as total'))
            ->groupBy('inv.id','inv.current_month','c.due_day')
            ->get()
            ->reduce(function ($sum, $row) use ($rangeStart, $rangeEnd) {
                $base = Carbon::createFromFormat('Y-m', $row->current_month)->startOfMonth();
                $due  = $base->copy()->day(min((int)($row->due_day ?: 1), $base->daysInMonth));
                if ($due->betweenIncluded($rangeStart, $rangeEnd)) $sum += (float)$row->total;
                return $sum;
            }, 0.0);

        $aPagar = abs($payableTx) + abs($openInvoices);
        $saldoPrevisto = $accountsBalance + $receivable - $aPagar;

        $savingsBalance = (float) Saving::whereIn('savings.user_id', $userIds)->sum('current_amount');

        return [
            'accountsBalance' => round($accountsBalance, 2),
            'savingsBalance'  => round($savingsBalance, 2), // <- novo
            'aReceber'        => round($receivable, 2),
            'aPagar'          => round($aPagar, 2),
            'saldoPrevisto'   => round($saldoPrevisto, 2),
        ];
    }

    private function paymentsIndex(Collection $userIds): array
    {
        // carrega todos os pagamentos das transações do grupo
        $rows = DB::table('payment_transactions as pt')
            ->join('transactions as t', 't.id', '=', 'pt.transaction_id')
            ->whereIn('t.user_id', $userIds)
            ->get(['pt.transaction_id','pt.reference_year','pt.reference_month']);

        $idx = [];
        foreach ($rows as $r) {
            $ym = sprintf('%04d-%02d', (int)$r->reference_year, (int)$r->reference_month);
            $idx[$r->transaction_id][$ym] = true; // já pago/recebido naquela competência
        }
        return $idx;
    }

    private function normType(?string $t): string
    {
        return trim(mb_strtolower((string) $t));
    }

    private function sumProjectedEntriesAndExpenses(Collection $userIds, Carbon $start, Carbon $end): array
    {
        $paid = $this->paymentsIndex($userIds);

        $sumReceber = 0.0;
        $sumPagar   = 0.0;

        // ✅ Trabalhe no mês selecionado (1º dia até fim do mês)
        $monthStart = $end->copy()->startOfMonth();

        // ===== ÚNICAS (no mês selecionado)
        $uniqueTx = Transaction::withoutGlobalScopes()
            ->with(['transactionCategory:id,type'])
            ->whereIn('transactions.user_id', $userIds)
            ->where('recurrence_type', 'unique')
            ->whereBetween('transactions.date', [$monthStart, $end])   // << aqui
            ->where(function ($q) {
                $q->where('transactions.type', '!=', 'card')
                    ->orWhereNull('transactions.type')
                    ->orWhere(function ($qq) {
                        $qq->where('transactions.type', 'card')
                            ->where('transactions.type_card', '!=', 'credit');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('transactions.title')
                    ->orWhereRaw('LOWER(transactions.title) NOT IN (?, ?, ?)', [
                        'total fatura','fatura total','total da fatura'
                    ]);
            })
            ->get(['id','amount','date','transaction_category_id']);

        foreach ($uniqueTx as $t) {
            $type = strtolower($t->transactionCategory?->type ?? '');
            $ym   = Carbon::parse($t->date)->format('Y-m');
            if (!empty($paid[$t->id][$ym])) continue; // já liquidada

            if ($type === 'entrada')     $sumReceber += abs((float)$t->amount);
            elseif ($type === 'despesa') $sumPagar   += abs((float)$t->amount);
        }

        // ===== RECORRENTES MONTHLY / YEARLY (ocorrências do mês selecionado)
        $recMY = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,type'])
            ->whereIn('recurrents.user_id', $userIds)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('recurrence_type', ['monthly','yearly'])
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
            ->get(['id','transaction_id','payment_day','amount']);

        foreach ($recMY as $r) {
            $t      = $r->transaction; if (!$t) continue;
            $type   = strtolower($t->transactionCategory?->type ?? '');
            $amount = (float)$r->amount;

            if ($t->recurrence_type === 'monthly') {
                // Só o mês-alvo
                $occ = $monthStart->copy()->day(min((int)$r->payment_day, $monthStart->daysInMonth));

                if ($occ->betweenIncluded($monthStart, $end) && $occ->gte(Carbon::parse($t->date))) {
                    $ym = $occ->format('Y-m');
                    if (empty($paid[$t->id][$ym])) {
                        if ($type === 'entrada')     $sumReceber += abs($amount);
                        elseif ($type === 'despesa') $sumPagar   += abs($amount);
                    }
                }
            } else { // yearly
                $anchorMonth = (int) Carbon::parse($t->date)->month;
                // A ocorrência anual deste mês-alvo
                if ($monthStart->month === $anchorMonth) {
                    $daysIn = $monthStart->daysInMonth;
                    $occ = Carbon::create($monthStart->year, $anchorMonth, min((int)$r->payment_day, $daysIn));

                    if ($occ->betweenIncluded($monthStart, $end) && $occ->gte(Carbon::parse($t->date))) {
                        $ym = $occ->format('Y-m');
                        if (empty($paid[$t->id][$ym])) {
                            if ($type === 'entrada')     $sumReceber += abs($amount);
                            elseif ($type === 'despesa') $sumPagar   += abs($amount);
                        }
                    }
                }
            }
        }

        // ===== RECORRENTES CUSTOM (itens)
        $recC = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,type'])
            ->whereIn('recurrents.user_id', $userIds)
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
            ->get(['id','transaction_id']);

        foreach ($recC as $r) {
            $t    = $r->transaction; if (!$t) continue;
            $type = $this->normType($t->transactionCategory?->type ?? '');

            $items = CustomItemRecurrents::where('recurrent_id', $r->id)
                ->get(['payment_day','reference_month','reference_year','amount']);

            foreach ($items as $ci) {
                $daysIn = Carbon::create($ci->reference_year, $ci->reference_month, 1)->daysInMonth;
                $occ = Carbon::create($ci->reference_year, $ci->reference_month, min((int)$ci->payment_day, $daysIn));
                if (!$occ->betweenIncluded($start, $end)) continue;

                $ym = $occ->format('Y-m');
                if (!empty($paid[$t->id][$ym])) continue;

                $amount = (float)$ci->amount;
                if ($type === 'entrada')  $sumReceber += abs($amount);
                elseif ($type === 'despesa') $sumPagar   += abs($amount);
            }
        }

        // ===== RECORRENTES CUSTOM (X dias) SEM TÉRMINO (sem itens)
        $recD = Recurrent::withoutGlobalScopes()
            ->with(['transaction.transactionCategory:id,type'])
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
            ->get(['recurrents.*']);

        foreach ($recD as $r) {
            // se tiver itens explícitos, deixa o bloco anterior cuidar
            if (DB::table('custom_item_recurrents')->where('recurrent_id', $r->id)->exists()) continue;

            $t    = $r->transaction; if (!$t) continue;
            $type = $this->normType($t->transactionCategory?->type ?? '');
            if (!in_array($type, ['entrada','despesa','investimento'], true)) $type = 'investimento';

            $start    = Carbon::parse($r->start_date)->startOfDay();
            $interval = max(1, (int)$r->interval_value);

            // janela do mês alvo apenas
            $monthStart = $end->copy()->startOfMonth();
            $cursor = $this->firstAlignedDays($start, $monthStart, $interval);
            $cursor = $this->normalizeW($cursor, (bool)$r->include_sat, (bool)$r->include_sun);

            while ($cursor->lte($end)) {
                if ($cursor->lt($monthStart)) { // segurança
                    $cursor->addDays($interval); continue;
                }

                $ym = $cursor->format('Y-m');
                if (empty($paid[$t->id][$ym])) {
                    $amt = abs((float)$r->amount);
                    if     ($type === 'entrada')     $sumReceber += $amt;
                    elseif ($type === 'despesa')     $sumPagar   += $amt;
                    else /* investimento */          $sumPagar   += $amt; // investimento entra como saída prevista
                }

                $cursor = $this->normalizeW(
                    $cursor->copy()->addDays($interval),
                    (bool)$r->include_sat,
                    (bool)$r->include_sun
                );
            }
        }

        return ['aReceber' => $sumReceber, 'aPagar' => $sumPagar];
    }

    private function normalizeW(Carbon $d, bool $sat, bool $sun): Carbon
    {
        if (!$sat && $d->isSaturday()) $d->addDays(2);
        if (!$sun && $d->isSunday())   $d->addDay();
        return $d;
    }

    /** primeira ocorrência alinhada ao intervalo >= $from  */
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
