<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CardCycle;
use App\Models\Saving;
use App\Models\SavingMovement;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Card;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Recurrent;
use App\Models\MonthlyItemRecurrents;
use App\Models\YearlyItemRecurrents;
use App\Models\CustomItemRecurrents;

use App\Helpers\RecurrenceDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public $transaction;
    public $recurrent;

    public function __construct(
        Transaction           $transaction,
        Recurrent             $recurrent,
        Invoice               $invoice,
        InvoiceItem           $invoiceItem,
        MonthlyItemRecurrents $monthlyItemRecurrents,
        YearlyItemRecurrents  $yearlyItemRecurrents,
        CustomItemRecurrents  $customItemRecurrents
    )
    {
        $this->transaction = $transaction;
        $this->recurrent = $recurrent;
        $this->invoice = $invoice;
        $this->invoiceItem = $invoiceItem;
        $this->monthlyItemRecurrents = $monthlyItemRecurrents;
        $this->yearlyItemRecurrents = $yearlyItemRecurrents;
        $this->customItemRecurrents = $customItemRecurrents;
    }

    public function index()
    {
        $transactions = $this->transaction->with('transactionCategory')->orderBy('date', 'asc')->get();

        $transactions->each(function ($transaction) {
            $transaction->amount = brlPrice($transaction->amount);

            if ($transaction->transactionCategory && $transaction->transactionCategory->type) {
                switch ($transaction->transactionCategory->type) {
                    case 'entrada':
                        $transaction->typeColor = 'success';
                        break;
                    case 'despesa':
                        $transaction->typeColor = 'danger';
                        break;
                    case 'investimento':
                        $transaction->typeColor = 'info';
                        break;
                }
            }

            $transaction->date = Carbon::parse($transaction->date)->locale('pt_BR')->isoFormat('DD/MMM.');
        });

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'transaction_category_id' => 'required|uuid|exists:transaction_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',

            'type' => 'required|in:pix,card,money',
            'account_id' => 'required_if:type,pix|nullable|uuid|exists:accounts,id',

            'type_card' => 'nullable|required_if:type,card|in:credit,debit',

            'card_id'   => [
                'exclude_if:alternate_cards,1',   // se alternar=1, ignora todas as regras abaixo
                'nullable',
                'required_if:type,card',          // obrigatório se for cartão (quando NÃO alterna)
                'uuid',
                'exists:cards,id',
            ],

            'alternate_cards'      => ['nullable','boolean'],
            'alternate_card_ids'   => ['required_if:alternate_cards,1','array'],
            'alternate_card_ids.*' => ['uuid','distinct','exists:cards,id'],

            'recurrence_type' => 'nullable|in:unique,monthly,yearly,custom',
            'termination'        => 'nullable|in:no_end,has_end',
            'custom_occurrences' => ['nullable','integer','min:1','required_if:termination,has_end'],
            'interval_value'     => 'required_if:recurrence_type,custom|integer|min:1',

            'saving_id' => [function($attr,$val,$fail) use ($request) {
                $cat = \App\Models\TransactionCategory::find($request->transaction_category_id);
                if ($cat && $cat->type === 'investimento' && empty($val)) {
                    $fail('saving_id é obrigatório para categoria investimento.');
                }
                if ($cat && $cat->type === 'investimento' && $request->type === 'card' && $request->type_card === 'credit') {
                    $fail('Investimento não pode ser no crédito.');
                }
            }],
        ]);

        $txDate       = Carbon::parse($request->date)->startOfDay();
        $isCard       = $request->type === 'card';
        $typeCard     = $isCard ? $request->type_card : null;
        $installments = (int) ($request->installments ?? 1);


        if ($isCard && $typeCard === 'credit' && $installments >= 1 && $request->recurrence_type === 'unique') {
            return $this->handleInstallments($request, $txDate, $installments);
        }

        $isRecurring = $request->recurrence_type !== 'unique';
        if ($isCard && $typeCard === 'credit' && $isRecurring) {
            return $this->handleRecurringCard($request, $txDate);
        }

        return $this->handleUniqueTransaction($request, $txDate, $typeCard, $isCard);
    }

    public function show(string $id)
    {
        $tx = Transaction::with(['transactionCategory', 'card'])->findOrFail($id);

        return response()->json($tx);
    }

    public function update(Request $req, string $id)
    {
        $tx = Transaction::findOrFail($id);

        $tx->update($req->all());

        return response()->json($tx->fresh());
    }

    public function destroy(string $id)
    {

        $invoiceItem = InvoiceItem::where('transaction_id', $id)->get();

        $invoiceItem->each(function ($item) {
            $item->delete();
        });

        Transaction::findOrFail($id)->delete();

        return response()->noContent();
    }

    protected function handleInstallments(Request $request, Carbon $txDate, int $installments)
    {
        $amountPerInstallment = round($request->amount / $installments, 2);

        $transaction = $this->transaction->create([
            'user_id' => Auth::id(),
            'card_id' => $request->card_id,
            'transaction_category_id' => $request->transaction_category_id,
            'saving_id' => $request->saving_id,
            'account_id' => $request->account_id,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $txDate,
            'type' => 'card',
            'type_card' => 'credit',
            'recurrence_type' => 'unique',
            'custom_occurrences' => $installments,
        ]);

        $card  = Card::findOrFail($request->card_id);
        $occur = $txDate->copy();

        for ($i = 1; $i <= $installments; $i++) {
            $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$card->closing_day);

            $invoice = Invoice::firstOrCreate(
                ['user_id' => Auth::id(), 'card_id' => $card->id, 'current_month' => $cycleMonth],
                ['paid' => false]
            );

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'title' => $request->title,
                'amount' => $amountPerInstallment,
                'date' => $occur->copy(),
                'transaction_category_id' => $request->transaction_category_id,
                'installments' => $installments,
                'current_installment' => $i,
                'is_projection' => true,
            ]);

            $occur->addMonthNoOverflow();
        }

        return response()->json([
            'message' => 'Compra parcelada registrada com sucesso',
            'transaction' => $transaction
        ]);
    }

    protected function handleRecurringCard(Request $request, Carbon $txDate)
    {
        $transaction = $this->transaction->create([
            'user_id' => Auth::id(),
            'card_id' => $request->card_id,
            'saving_id' => $request->saving_id,
            'account_id' => $request->account_id,
            'transaction_category_id' => $request->transaction_category_id,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $txDate,
            'type' => 'card',
            'type_card' => 'credit',
            'recurrence_type' => $request->recurrence_type,
        ]);

        $unit  = $request->recurrence_type === 'yearly' ? 'years' : ($request->recurrence_type === 'custom' ? 'days' : 'months');
        $value = $request->recurrence_type === 'custom' ? (int)($request->interval_value ?? 30) : 1;

        $includeSat = (bool)($request->include_sat ?? true);
        $includeSun = (bool)($request->include_sun ?? true);

        $recurrent = $this->recurrent->create([
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'payment_day' => $txDate->format('d'),
            'amount' => $transaction->amount,
            'start_date' => $txDate,
            'interval_unit' => $unit,
            'interval_value' => $value,
            'include_sat' => $includeSat,
            'include_sun' => $includeSun,
            'next_run_date' => $txDate,
            'active' => true,
            'alternate_cards' => (bool)$request->alternate_cards,
        ]);

        return $this->generateRecurringInvoices($request, $transaction, $recurrent, $txDate, $unit, $value, $includeSat, $includeSun);
    }

    protected function handleUniqueTransaction(Request $request, Carbon $txDate, ?string $typeCard, bool $isCard)
    {
        $recurrenceType = $request->recurrence_type ?? 'unique';
        $isRecurring    = $recurrenceType !== 'unique';
        $cat = TransactionCategory::find($request->transaction_category_id);

        $transaction = $this->transaction->create([
            'user_id'                  => Auth::id(),
            'saving_id' => $request->saving_id,
            'account_id' => $request->account_id,
            'card_id'                  => $request->card_id,
            'transaction_category_id'  => $request->transaction_category_id,
            'title'                    => $request->title,
            'description'              => $request->description,
            'amount'                   => $request->amount,
            'date'                     => $txDate,
            'type'                     => $request->type,    // pix | money | card
            'type_card'                => $typeCard,         // credit | debit | null
            'recurrence_type'          => $recurrenceType,   // unique | monthly | yearly | custom
            'custom_occurrences'       => $request->custom_occurrences ?? $request->installments,
        ]);

        if ($cat && $cat->type === 'investimento' && $recurrenceType === 'unique') {
            DB::transaction(function() use ($request, $transaction, $txDate) {
                SavingMovement::create([
                    'user_id'       => Auth::id(),
                    'saving_id'     => $request->saving_id,
                    'transaction_id'=> $transaction->id,
                    'account_id'    => $request->type === 'pix' ? $request->account_id : null,
                    'direction'     => 'deposit',
                    'amount'        => $transaction->amount,
                    'date'          => $txDate->toDateString(),
                    'notes'         => $transaction->title,
                ]);
                Saving::where('id',$request->saving_id)
                    ->increment('current_amount', $transaction->amount);
            });
        }

        if ($isRecurring && !($isCard && $typeCard === 'credit')) {
            $recurrent = $this->recurrent->create([
                'user_id'        => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'payment_day'    => $txDate->format('d'),
                'amount'         => $transaction->amount,

                // novos campos de recorrents já existem na tua migration extra
                'start_date'     => $txDate,
                'interval_unit'  => $recurrenceType === 'yearly' ? 'years' : ($recurrenceType === 'custom' ? 'days' : 'months'),
                'interval_value' => $recurrenceType === 'custom' ? (int)($request->interval_value ?? 1) : 1,
                'include_sat'    => (bool)($request->include_sat ?? true),
                'include_sun'    => (bool)($request->include_sun ?? true),
                'next_run_date'  => $txDate,
                'active'         => true,
            ]);

            $occ = (int)($request->custom_occurrences ?? 0);

            // SEM TÉRMINO → 1 linha modelo em monthly/yearly
            if (in_array($recurrenceType, ['monthly','yearly']) && $occ === 0) {
                if ($recurrenceType === 'monthly') {
                    $this->monthlyItemRecurrents->create([
                        'recurrent_id'    => $recurrent->id,
                        'payment_day'     => $txDate->format('d'),
                        'reference_month' => $txDate->format('m'),
                        'reference_year'  => $txDate->format('Y'),
                        'amount'          => $transaction->amount,
                        'status'          => false,
                    ]);
                } else { // yearly
                    $this->yearlyItemRecurrents->create([
                        'recurrent_id'   => $recurrent->id,
                        'payment_day'    => $txDate->format('d'),
                        'reference_year' => $txDate->format('Y'),
                        'amount'         => $transaction->amount,
                        'status'         => false,
                    ]);
                }
            }
            // COM TÉRMINO → gerar N datas em custom_item_recurrents
            elseif ($occ > 0) {
                $includeSat = (bool)($request->include_sat ?? true);
                $includeSun = (bool)($request->include_sun ?? true);
                $norm = function (Carbon $d) use ($includeSat,$includeSun) {
                    if (!$includeSat && $d->isSaturday()) $d->addDays(2);
                    if (!$includeSun && $d->isSunday())   $d->addDay();
                    return $d;
                };

                $step = match ($recurrenceType) {
                    'yearly'  => fn(Carbon $d, $i) => $norm($d->copy()->addYearsNoOverflow($i)),
                    'monthly' => fn(Carbon $d, $i) => $norm($d->copy()->addMonthsNoOverflow($i)),
                    'custom'  => fn(Carbon $d, $i) => $norm($d->copy()->addDays(($request->interval_value ?? 1) * $i)),
                };

                for ($i = 0; $i < $occ; $i++) {
                    $current = $step($txDate, $i);
                    $this->customItemRecurrents->create([
                        'recurrent_id'             => $recurrent->id,
                        'payment_day'              => $current->format('d'),
                        'reference_month'          => $current->format('m'),
                        'reference_year'           => $current->format('Y'),
                        'amount'                   => $transaction->amount,
                        'custom_occurrence_number' => $i + 1,
                        'status'                   => false,
                    ]);
                }
            }

            return response()->json([
                'message'     => 'Transação recorrente registrada com sucesso',
                'transaction' => $transaction,
            ]);
        }

        return response()->json([
            'message'     => 'Transação registrada com sucesso',
            'transaction' => $transaction,
        ]);
    }

    protected function generateRecurringInvoices(
        Request $request,
        Transaction $transaction,
        $recurrent,
        Carbon $txDate,
        string $unit,
        int $value,
        bool $includeSat,
        bool $includeSun
    ) {
        $normalize = function (Carbon $d) use ($includeSat, $includeSun) {
            if (!$includeSat && $d->isSaturday()) $d->addDays(2);
            if (!$includeSun && $d->isSunday())   $d->addDay();
            return $d;
        };

        $nextFn = function (Carbon $d) use ($unit, $value, $normalize) {
            return $normalize(match ($unit) {
                'days'  => $d->copy()->addDays($value),
                'years' => $d->copy()->addYearsNoOverflow($value),
                default => $d->copy()->addMonthsNoOverflow($value),
            });
        };

        $horizonEnd = $txDate->copy()->addMonths(12)->endOfMonth();
        $occur      = $normalize($txDate->copy());

        $useAlt = (bool)($request->alternate_cards)
            && collect($request->alternate_card_ids ?? [])->filter()->isNotEmpty();

        if ($useAlt) {
            $altIds = collect($request->alternate_card_ids)->filter()->unique()->values();

            foreach ($altIds as $idx => $cid) {
                DB::table('recurrent_cards')->insert([
                    'id'            => Str::uuid(),
                    'recurrent_id'  => $recurrent->id,
                    'card_id'       => $cid,
                    'position'      => $idx,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            $cards = Card::whereIn('id', $altIds)->get(['id','closing_day']);

            while ($occur->lte($horizonEnd)) {
                $choice = $cards->map(function ($c) use ($occur) {
                    return ['card' => $c, 'lastClose' => CardCycle::lastClose($occur, (int)$c->closing_day)];
                })
                    ->sortByDesc(fn ($x) => $x['lastClose']->timestamp)
                    ->values()
                    ->first();

                if (!$choice) break;

                $chosen     = $choice['card'];
                $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$chosen->closing_day);

                $invoice = Invoice::firstOrCreate(
                    ['user_id' => Auth::id(), 'card_id' => $chosen->id, 'current_month' => $cycleMonth],
                    ['paid' => false]
                );

                $this->createInvoiceItemIfNotExists($invoice->id, $transaction->id, $recurrent->id, $request, $occur);

                $occur = $nextFn($occur);
            }
        } else {
            if (!$request->card_id) {
                return response()->json(['error' => 'card_id é obrigatório quando não alterna cartões'], 422);
            }

            $card = Card::findOrFail($request->card_id);

            while ($occur->lte($horizonEnd)) {
                $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$card->closing_day);

                $invoice = Invoice::firstOrCreate(
                    ['user_id' => Auth::id(), 'card_id' => $card->id, 'current_month' => $cycleMonth],
                    ['paid' => false]
                );

                $this->createInvoiceItemIfNotExists($invoice->id, $transaction->id, $recurrent->id, $request, $occur);

                $occur = $nextFn($occur);
            }
        }

        return response()->json([
            'message'       => 'Recorrência registrada com sucesso',
            'transaction'   => $transaction,
            'recurrent_id'  => $recurrent->id,
        ]);
    }

    protected function createInvoiceItemIfNotExists($invoiceId, $transactionId, $recurrentId, Request $request, Carbon $date)
    {
        $exists = InvoiceItem::where('invoice_id', $invoiceId)
            ->where('recurrent_id', $recurrentId)
            ->whereDate('date', $date->toDateString())
            ->exists();

        if (!$exists) {
            InvoiceItem::create([
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'recurrent_id' => $recurrentId,
                'title' => $request->title,
                'amount' => $request->amount,
                'date' => $date->copy(),
                'transaction_category_id' => $request->transaction_category_id,
                'installments' => 1,
                'current_installment' => 1,
                'is_projection' => true,
            ]);
        }
    }

    public function projections(Request $req)
    {
        $from = Carbon::parse($req->get('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($req->get('to',   now()->copy()->addMonths(12)->endOfMonth()))->endOfDay();

        // Pegamos todos os recorrentes ativos do usuário
        $recs = Recurrent::with([
            'transaction:id,title,transaction_category_id,amount,type,type_card'
        ])
            ->where('user_id', Auth::id())
            ->where('active', true)
            ->get();

        $out = [];

        foreach ($recs as $rec) {
            // se for cartão de CRÉDITO recorrente, você já projeta via faturas/invoice_items → pula aqui
            if (optional($rec->transaction)->type === 'card' && optional($rec->transaction)->type_card === 'credit') {
                continue;
            }

            switch ($rec->interval_unit) {
                case 'days':
                    // custom (X dias) — inclui seu caso "15 dias sem término"
                    foreach ($this->projectDays($rec, $from, $to) as $occ) $out[] = $occ;
                    break;
                case 'months':
                    // mensal sem término: 1 “modelo” em monthly_item_recurrents,
                    // mas para a UI podemos expandir aqui também
                    foreach ($this->projectMonths($rec, $from, $to) as $occ) $out[] = $occ;
                    break;
                case 'years':
                    foreach ($this->projectYears($rec, $from, $to) as $occ) $out[] = $occ;
                    break;
            }
        }

        // Também pode mesclar aqui o que estiver em custom_item_recurrents (quando houver término)
        // e monthly_item_recurrents/yearly_item_recurrents “modelo” se quiser
        // (opcional; depende de como sua UI consome).

        return response()->json(collect($out)->sortBy('date')->values());
    }

    protected function normalizeWeekends(Carbon $d, $includeSat, $includeSun): Carbon
    {
        if (!$includeSat && $d->isSaturday()) $d->addDays(2);
        if (!$includeSun && $d->isSunday())   $d->addDay();
        return $d;
    }

    protected function projectDays(Recurrent $rec, Carbon $from, Carbon $to): \Generator
    {
        $start    = Carbon::parse($rec->start_date)->startOfDay();
        $interval = max(1, (int) $rec->interval_value);

        // pula direto para a primeira ocorrência >= $from
        if ($start->lt($from)) {
            $diffDays = $start->diffInDays($from);
            $steps = intdiv($diffDays + $interval - 1, $interval); // ceil
            $start->addDays($steps * $interval);
        }

        $cursor = $this->normalizeWeekends($start, (bool)$rec->include_sat, (bool)$rec->include_sun);

        while ($cursor->lte($to)) {
            yield [
                'date'           => $cursor->toDateString(),
                'amount'         => (float)$rec->amount,
                'transaction_id' => $rec->transaction_id,
                'recurrent_id'   => $rec->id,
                'title'          => optional($rec->transaction)->title,
                'type'           => optional($rec->transaction)->type,
            ];
            $cursor = $this->normalizeWeekends(
                $cursor->copy()->addDays($interval),
                (bool)$rec->include_sat,
                (bool)$rec->include_sun
            );
        }
    }

    protected function projectMonths(Recurrent $rec, Carbon $from, Carbon $to): \Generator
    {
        // usa payment_day (string '03') ou o dia de start_date
        $day   = (int) ($rec->payment_day ?: Carbon::parse($rec->start_date)->day);
        $start = Carbon::parse($rec->start_date)->startOfDay()->day($day);

        // primeira >= from
        while ($start->lt($from)) {
            $start = $start->copy()->addMonthsNoOverflow(1)->day($day);
        }

        $cursor = $this->normalizeWeekends($start, (bool)$rec->include_sat, (bool)$rec->include_sun);

        while ($cursor->lte($to)) {
            yield [
                'date'           => $cursor->toDateString(),
                'amount'         => (float)$rec->amount,
                'transaction_id' => $rec->transaction_id,
                'recurrent_id'   => $rec->id,
                'title'          => optional($rec->transaction)->title,
                'type'           => optional($rec->transaction)->type,
            ];
            $cursor = $this->normalizeWeekends(
                $cursor->copy()->addMonthsNoOverflow(1)->day($day),
                (bool)$rec->include_sat,
                (bool)$rec->include_sun
            );
        }
    }

    protected function projectYears(Recurrent $rec, Carbon $from, Carbon $to): \Generator
    {
        $day   = (int) ($rec->payment_day ?: Carbon::parse($rec->start_date)->day);
        $start = Carbon::parse($rec->start_date)->startOfDay()->day($day);

        while ($start->lt($from)) {
            $start = $start->copy()->addYearsNoOverflow(1)->day($day);
        }

        $cursor = $this->normalizeWeekends($start, (bool)$rec->include_sat, (bool)$rec->include_sun);

        while ($cursor->lte($to)) {
            yield [
                'date'           => $cursor->toDateString(),
                'amount'         => (float)$rec->amount,
                'transaction_id' => $rec->transaction_id,
                'recurrent_id'   => $rec->id,
                'title'          => optional($rec->transaction)->title,
                'type'           => optional($rec->transaction)->type,
            ];
            $cursor = $this->normalizeWeekends(
                $cursor->copy()->addYearsNoOverflow(1)->day($day),
                (bool)$rec->include_sat,
                (bool)$rec->include_sun
            );
        }
    }
}
