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

    public function index(Request $req)
    {
        $q = Transaction::with('transactionCategory')
            ->where('user_id', Auth::id());

        $norm = function (?string $s): ?string {
            if (!$s) return null;
            $s = trim($s);
            foreach (['Y-m-d','d/m/Y','d-m-Y'] as $fmt) {
                try { return \Carbon\Carbon::createFromFormat($fmt, $s)->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            try { return \Carbon\Carbon::parse($s)->toDateString(); } catch (\Throwable $e) { return null; }
        };
        $start = $norm($req->query('start'));
        $end   = $norm($req->query('end'));
        if ($start && $end && $start > $end) { [$start,$end] = [$end,$start]; }

        if ($start && $end)      $q->whereBetween('create_date', [$start, $end]);
        elseif ($start)          $q->whereDate('create_date', '>=', $start);
        elseif ($end)            $q->whereDate('create_date', '<=', $end);

        $type = $req->query('type');
        if (in_array($type, ['entrada','despesa','investimento'], true)) {
            $q->whereHas('transactionCategory', fn($qq) => $qq->where('type', $type));
        }

        $catIds = $req->query('category_ids', []);
        if (is_string($catIds)) $catIds = [$catIds];
        $catIds = array_values(array_filter($catIds));
        if (!empty($catIds)) {
            $q->whereIn('transaction_category_id', $catIds);
        }

        $transactions = $q->orderBy('create_date', 'asc')->get();

        $transactions->each(function ($t) {
            $t->amount = brlPrice($t->amount);
            if ($t->transactionCategory && $t->transactionCategory->type) {
                $t->typeColor = match ($t->transactionCategory->type) {
                    'entrada'      => 'success',
                    'despesa'      => 'danger',
                    'investimento' => 'info',
                    default        => null,
                };
            }
            $t->date = Carbon::parse($t->date)->locale('pt_BR')->isoFormat('DD/MMM.');
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

            'can_install'  => ['nullable','boolean'],
            'installments' => ['nullable','integer','min:1'],

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

        $isPix        = $request->type === 'pix';
        $isCard       = $request->type === 'card';
        $typeCard     = $isCard ? $request->type_card : null;
        $installments = (int) ($request->installments ?? 1);
        $canInstall   = (bool) ($request->can_install ?? false);

        // 1) PIX parcelado (somente quando marcar "parcelar" e tiver mais de 1 parcela, em recorrência ÚNICA)
        if ($isPix && $request->recurrence_type === 'unique' && $canInstall && $installments > 1) {
            return $this->handlePixInstallments($request, $txDate, $installments);
        }

        // 2) Cartão de CRÉDITO parcelado (única + parcelar)
        if ($isCard && $typeCard === 'credit' && $request->recurrence_type === 'unique' && $canInstall && $installments > 1) {
            return $this->handleInstallments($request, $txDate, $installments);
        }

        $isRecurring = $request->recurrence_type !== 'unique';

        // 3) Cartão de CRÉDITO recorrente (mensal/anual/custom)
        if ($isCard && $typeCard === 'credit' && $isRecurring) {
            return $this->handleRecurringCard($request, $txDate);
        }

        // 4) Demais casos (PIX/money único ou recorrente, débito, etc.)
        return $this->handleUniqueTransaction($request, $txDate, $typeCard, $isCard);
    }

    public function show(string $id)
    {
        $tx = Transaction::with(['transactionCategory', 'card'])->findOrFail($id);

        return response()->json($tx);
    }

    public function update(Request $request, string $id)
    {
        // Mesma validação do store()
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

            'can_install'  => ['nullable','boolean'],
            'installments' => ['nullable','integer','min:1'],

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

        $tx = Transaction::findOrFail($id);

        $txDate         = Carbon::parse($request->date)->startOfDay();
        $isPix          = $request->type === 'pix';
        $isCard         = $request->type === 'card';
        $typeCard       = $isCard ? $request->type_card : null;
        $installments   = (int) ($request->installments ?? 1);
        $canInstall     = (bool) ($request->can_install ?? false);
        $recurrenceType = $request->recurrence_type ?? 'unique';
        $isRecurring    = $recurrenceType !== 'unique';

        $response = null;

        DB::transaction(function () use (
            $request,
            $tx,
            $txDate,
            $isPix,
            $isCard,
            $typeCard,
            $installments,
            $canInstall,
            $recurrenceType,
            $isRecurring,
            &$response
        ) {
            // Limpa tudo que foi gerado com base na transação antiga
            $this->cleanupForUpdate($tx);

            // Remove a transação antiga (vamos recriar com a nova configuração)
            $tx->delete();

            // 1) PIX parcelado (Única + "parcelar" + > 1 parcela)
            if ($isPix && $recurrenceType === 'unique' && $canInstall && $installments > 1) {
                $response = $this->handlePixInstallments($request, $txDate, $installments);
                return;
            }

            // 2) Cartão de CRÉDITO parcelado (Única + "parcelar" + > 1 parcela)
            if ($isCard && $typeCard === 'credit' && $recurrenceType === 'unique' && $canInstall && $installments > 1) {
                $response = $this->handleInstallments($request, $txDate, $installments);
                return;
            }

            // 3) Cartão de CRÉDITO recorrente (mensal/anual/custom)
            if ($isCard && $typeCard === 'credit' && $isRecurring) {
                $response = $this->handleRecurringCard($request, $txDate);
                return;
            }

            // 4) Demais casos (PIX/dinheiro único ou recorrente, débito, etc.)
            $response = $this->handleUniqueTransaction($request, $txDate, $typeCard, $isCard);
        });

        return $response;
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
            'create_date' => $txDate,
        ]);

        $card  = Card::findOrFail($request->card_id);
        $occur = $txDate->copy();

        for ($i = 1; $i <= $installments; $i++) {
            $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$card->closing_day);

            // garante sequência (ex.: cria 2026-12 antes de 2027-01)
            $invoice = $this->ensureCardInvoicesUntil($card, $cycleMonth);

            InvoiceItem::create([
                'invoice_id'              => $invoice->id,
                'transaction_id'          => $transaction->id,
                'title'                   => $request->title,
                'amount'                  => $amountPerInstallment,
                'date'                    => $occur->copy(),
                'transaction_category_id' => $request->transaction_category_id,
                'installments'            => $installments,
                'current_installment'     => $i,
                'is_projection'           => true,
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
            'create_date' => $txDate,
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
            'alternate_cards' => (bool)$request->alternate_cards
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
            'create_date' => $txDate
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
                'transaction' => $transaction
            ]);
        }

        return response()->json([
            'message'     => 'Transação registrada com sucesso',
            'transaction' => $transaction
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

        $occLimit = 0;

        if ($request->termination === 'has_end') {
            $occLimit = (int) ($request->custom_occurrences ?? 0);
        }

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

            $counter = 0;

            while ($occur->lte($horizonEnd)) {
                if ($occLimit > 0 && $counter >= $occLimit) {
                    break;
                }

                $choice = $cards->map(function ($c) use ($occur) {
                    return ['card' => $c, 'lastClose' => CardCycle::lastClose($occur, (int)$c->closing_day)];
                })
                    ->sortByDesc(fn ($x) => $x['lastClose']->timestamp)
                    ->values()
                    ->first();

                if (!$choice) break;

                $chosen     = $choice['card'];
                $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$chosen->closing_day);

                $invoice = $this->ensureCardInvoicesUntil($chosen, $cycleMonth);

                $this->createInvoiceItemIfNotExists(
                    $invoice->id,
                    $transaction->id,
                    $recurrent->id,
                    $request,
                    $occur
                );

                $counter++;
                $occur = $nextFn($occur);
            }

        } else {
            if (!$request->card_id) {
                return response()->json(['error' => 'card_id é obrigatório quando não alterna cartões'], 422);
            }

            $card = Card::findOrFail($request->card_id);

            $counter = 0;

            while ($occur->lte($horizonEnd)) {
                if ($occLimit > 0 && $counter >= $occLimit) {
                    break;
                }

                $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$card->closing_day);

                $invoice = $this->ensureCardInvoicesUntil($card, $cycleMonth);

                $this->createInvoiceItemIfNotExists(
                    $invoice->id,
                    $transaction->id,
                    $recurrent->id,
                    $request,
                    $occur
                );

                $counter++;
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

    protected function handlePixInstallments(Request $request, Carbon $txDate, int $installments)
    {
        // valor de cada parcela (vai ter o mesmo "problema" de centavos do cartão: 1000/3 = 333.33)
        $perInstallment = round($request->amount / $installments, 2);

        // vamos transformar internamente em RECORRÊNCIA MENSAL com término
        // - amount = valor de cada parcela
        // - recurrence_type = 'monthly'
        // - custom_occurrences = número de parcelas

        $clone = $request->duplicate();
        $clone->merge([
            'amount'             => $perInstallment,
            'recurrence_type'    => 'monthly',
            'custom_occurrences' => $installments,
            // para PIX não mexemos em include_sat/include_sun; usa o que vier do form ou defaults
        ]);

        // tipo de cartão = null, não é cartão
        $typeCard = null;
        $isCard   = false;

        // reaproveita TODA a lógica de recorrência que você já tem
        return $this->handleUniqueTransaction($clone, $txDate, $typeCard, $isCard);
    }

    protected function ensureCardInvoicesUntil(Card $card, string $targetCycleMonth): Invoice
    {
        $userId     = Auth::id();
        $targetDate = Carbon::parse($targetCycleMonth)->startOfDay();

        // Última fatura existente para esse cartão
        $maxMonth = Invoice::where('user_id', $userId)
            ->where('card_id', $card->id)
            ->max('current_month');

        if ($maxMonth) {
            $maxDate = Carbon::parse($maxMonth)->startOfDay();

            // Só precisamos criar algo se o alvo estiver DEPOIS da última
            if ($targetDate->gt($maxDate)) {

                // Começa no mês seguinte ao último existente
                $cursor = $maxDate->copy()->addMonthNoOverflow();

                // Gera todas as faturas até o mês alvo (incluindo)
                while ($cursor->lte($targetDate)) {
                    $monthKey = CardCycle::cycleMonthFor(
                        $cursor->copy(),
                        (int) $card->closing_day
                    );

                    Invoice::firstOrCreate(
                        [
                            'user_id'       => $userId,
                            'card_id'       => $card->id,
                            'current_month' => $monthKey,
                        ],
                        [
                            'paid' => false,
                        ]
                    );

                    $cursor->addMonthNoOverflow();
                }
            }
        } else {
            // Não existe NENHUMA fatura para o cartão → garante pelo menos a do mês alvo
            $monthKey = CardCycle::cycleMonthFor(
                $targetDate->copy(),
                (int) $card->closing_day
            );

            Invoice::firstOrCreate(
                [
                    'user_id'       => $userId,
                    'card_id'       => $card->id,
                    'current_month' => $monthKey,
                ],
                [
                    'paid' => false,
                ]
            );
        }

        // Retorna SEMPRE a fatura do mês alvo (que agora com certeza existe)
        return Invoice::firstOrCreate(
            [
                'user_id'       => $userId,
                'card_id'       => $card->id,
                'current_month' => $targetCycleMonth,
            ],
            [
                'paid' => false,
            ]
        );
    }

    protected function cleanupForUpdate(Transaction $tx): void
    {
        // 1) Reverter movimentos de cofrinho ligados a essa transação
        $movements = SavingMovement::where('transaction_id', $tx->id)->get();

        foreach ($movements as $mv) {
            // Devolve o saldo no cofrinho
            Saving::where('id', $mv->saving_id)->decrement('current_amount', $mv->amount);
            $mv->delete();
        }

        // 2) Recorrentes ligados a essa transação (e tudo que depende deles)
        $recs = Recurrent::where('transaction_id', $tx->id)->get();

        foreach ($recs as $rec) {
            // Itens de fatura de cartão gerados pela recorrência
            InvoiceItem::where('recurrent_id', $rec->id)->delete();

            // Alternância de cartões
            DB::table('recurrent_cards')->where('recurrent_id', $rec->id)->delete();

            // Modelos e ocorrências de recorrência
            $this->monthlyItemRecurrents->where('recurrent_id', $rec->id)->delete();
            $this->yearlyItemRecurrents->where('recurrent_id', $rec->id)->delete();
            $this->customItemRecurrents->where('recurrent_id', $rec->id)->delete();

            $rec->delete();
        }

        // 3) Itens de fatura vinculados diretamente à transação (parcelado no crédito)
        InvoiceItem::where('transaction_id', $tx->id)->delete();

        // OBS: não removo as Invoice em si – elas podem continuar vazias,
        // mantendo a sequência de faturas do cartão.
    }
}
