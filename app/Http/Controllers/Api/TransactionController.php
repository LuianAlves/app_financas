<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CardCycle;
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

//    public function store(Request $request)
//    {
//        $request->validate([
//            'title' => 'required|string|max:255',
//            'transaction_category_id' => 'required|uuid|exists:transaction_categories,id',
//            'amount' => 'required|numeric|min:0.01',
//            'date' => 'required|date',
//            'type' => 'required|in:pix,card,money',
//            'type_card' => 'nullable|in:credit,debit',
//            'recurrence_type' => 'nullable|in:unique,monthly,yearly,custom',
//            'interval_value' => 'nullable|integer|min:1',
//            'installments' => 'nullable|integer|min:1',
//            'account_id' => 'nullable|uuid|exists:accounts,id',
//            'card_id' => 'nullable|uuid|exists:cards,id',
//            'alternate_cards' => ['nullable','boolean'],
//            'alternate_card_ids' => ['required_if:alternate_cards,1','array'],
//            'alternate_card_ids.*' => ['exists:cards,id'],
//        ]);
//
//        $txDate = Carbon::parse($request->date)->startOfDay();
//        $isCard = $request->type === 'card';
//        $typeCard = $isCard ? $request->type_card : null;
//        $installments = (int) ($request->installments ?? 1);
//
//        // ============================
//        // Caso 1: Compra parcelada no crédito
//        // ============================
//        if ($isCard && $typeCard === 'credit' && $installments > 1 && $request->recurrence_type === 'unique') {
//            $amountPerInstallment = round($request->amount / $installments, 2);
//
//            $transaction = $this->transaction->create([
//                'user_id' => Auth::id(),
//                'card_id' => $request->card_id,
//                'transaction_category_id' => $request->transaction_category_id,
//                'title' => $request->title,
//                'description' => $request->description,
//                'amount' => $request->amount,
//                'date' => $txDate,
//                'type' => 'card',
//                'type_card' => 'credit',
//                'recurrence_type' => 'installments',
//                'custom_occurrences' => $installments,
//            ]);
//
//            $card = Card::findOrFail($request->card_id);
//            $occur = $txDate->copy();
//
//            for ($i = 1; $i <= $installments; $i++) {
//                $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$card->closing_day);
//
//                $invoice = Invoice::firstOrCreate(
//                    ['user_id' => Auth::id(), 'card_id' => $card->id, 'current_month' => $cycleMonth],
//                    ['paid' => false]
//                );
//
//                InvoiceItem::create([
//                    'invoice_id' => $invoice->id,
//                    'transaction_id' => $transaction->id,
//                    'title' => $request->title,
//                    'amount' => $amountPerInstallment,
//                    'date' => $occur->copy(),
//                    'transaction_category_id' => $request->transaction_category_id,
//                    'installments' => $installments,
//                    'current_installment' => $i,
//                    'is_projection' => true,
//                ]);
//
//                $occur->addMonthNoOverflow();
//            }
//
//            return response()->json([
//                'message' => 'Compra parcelada registrada com sucesso',
//                'transaction' => $transaction
//            ]);
//        }
//
//
//        // ============================
//        // Caso 2: Recorrências existentes (mantém sua lógica atual)
//        // ============================
//        $isUnique = $request->recurrence_type !== 'unique';
//        if ($isCard && $typeCard === 'credit' && $isUnique) {
//            $txDate = Carbon::parse($request->date)->startOfDay();
//
//            // 1) cria a transação “modelo”
//            $transaction = $this->transaction->create([
//                'user_id' => Auth::id(),
//                'card_id' => $request->card_id, // pode vir vazio se alternar
//                'transaction_category_id' => $request->transaction_category_id,
//                'title' => $request->title,
//                'description' => $request->description,
//                'amount' => $request->amount, // valor por ocorrência
//                'date' => $txDate,
//                'type' => 'card',
//                'type_card' => 'credit',
//                'recurrence_type' => $request->recurrence_type, // monthly|yearly|custom
//                'custom_occurrences' => null,
//            ]);
//
//            // 2) mapeia intervalo
//            $unit = 'months';
//            $value = 1;
//
//            if ($request->recurrence_type === 'yearly') {
//                $unit = 'years';
//                $value = 1;
//            }
//            if ($request->recurrence_type === 'custom') {
//                $unit = 'days';
//                $value = (int)($request->interval_value ?? 30);
//            }
//
//            $includeSat = (bool)($request->include_sat ?? true);
//            $includeSun = (bool)($request->include_sun ?? true);
//
//            // 3) cria o recurrent
//            $recurrent = $this->recurrent->create([
//                'user_id' => $transaction->user_id,
//                'transaction_id' => $transaction->id,
//                'payment_day' => $txDate->format('d'),
//                'amount' => $transaction->amount,
//                'start_date' => $txDate,
//                'interval_unit' => $unit,
//                'interval_value' => $value,
//                'include_sat' => $includeSat,
//                'include_sun' => $includeSun,
//                'next_run_date' => $txDate,
//                'active' => true,
//                'alternate_cards' => (bool)$request->alternate_cards,
//            ]);
//
//            // helpers de data
//            $normalize = function (Carbon $d) use ($includeSat, $includeSun) {
//                if (!$includeSat && $d->isSaturday()) $d->addDays(2);
//                if (!$includeSun && $d->isSunday()) $d->addDay();
//                return $d;
//            };
//
//            $nextFn = function (Carbon $d) use ($unit, $value, $normalize) {
//                return $normalize(match ($unit) {
//                    'days' => $d->copy()->addDays($value),
//                    'years' => $d->copy()->addYearsNoOverflow($value),
//                    default => $d->copy()->addMonthsNoOverflow($value),
//                });
//            };
//
//            $horizonEnd = $txDate->copy()->addMonths(12)->endOfMonth();
//            $occur = $normalize($txDate->copy());
//
//            // ===== Alternância entre cartões? =====
//            $alt = (bool)$request->alternate_cards;
//
//            $altIds = collect($request->alternate_card_ids ?? [])->filter()->unique()->values();
//
//            if ($alt && $altIds->isNotEmpty()) {
//                // salva vínculos
//                foreach ($altIds as $idx => $cid) {
//                    DB::table('recurrent_cards')->insert([
//                        'id' => Str::uuid(),
//                        'recurrent_id' => $recurrent->id,
//                        'card_id' => $cid,
//                        'position' => $idx,
//                        'created_at' => now(),
//                        'updated_at' => now(),
//                    ]);
//                }
//
//                // carrega cartões escolhidos (fechamento)
//                $cards = Card::whereIn('id', $altIds)->get(['id', 'closing_day']);
//
//                while ($occur->lte($horizonEnd)) {
//                    // escolhe o cartão cujo fechamento mais recente <= ocorrência
//                    // escolhe o cartão pelo fechamento mais recente <= ocorrência
//                    $choice = $cards->map(function ($c) use ($occur) {
//                        $lc = CardCycle::lastClose($occur, (int)$c->closing_day);
//                        return ['card' => $c, 'lastClose' => $lc];
//                    })
//                        ->sortByDesc(fn ($x) => $x['lastClose']->timestamp) // mais recente primeiro
//                        ->values()
//                        ->first();
//
//                    if (!$choice) {
//                        // sem cartões carregados — evita salvar tudo no mesmo
//                        break;
//                    }
//
//                    $chosen = $choice['card'];
//                    $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$chosen->closing_day);
//
//                    $invoice = Invoice::firstOrCreate(
//                        ['user_id' => Auth::id(), 'card_id' => $chosen->id, 'current_month' => $cycleMonth],
//                        ['paid' => false]
//                    );
//
//                    $exists = InvoiceItem::where('invoice_id', $invoice->id)
//                        ->where('recurrent_id', $recurrent->id)
//                        ->whereDate('date', $occur->toDateString())
//                        ->exists();
//
//                    if (!$exists) {
//                        InvoiceItem::create([
//                            'invoice_id' => $invoice->id,
//                            'recurrent_id' => $recurrent->id,
//                            'title' => $request->title,
//                            'amount' => $transaction->amount,
//                            'date' => $occur->copy(),
//                            'transaction_category_id' => $request->transaction_category_id,
//                            'installments' => 1,
//                            'current_installment' => 1,
//                            'is_projection' => true,
//                        ]);
//                    }
//
//                    $occur = $nextFn($occur);
//                }
//
//                // retorno
//                $transaction->amount = brlPrice($transaction->amount);
//                $transaction->date = $transaction->date->locale('pt_BR')->isoFormat('DD/MMM.');
//                return response()->json($transaction);
//            }
//
//            // ===== Sem alternância: um cartão só (o selecionado em card_id) =====
//            $card = Card::findOrFail($request->card_id);
//
//            while ($occur->lte($horizonEnd)) {
//                $cycleMonth = CardCycle::cycleMonthFor($occur, (int)$card->closing_day);
//
//                $invoice = Invoice::firstOrCreate(
//                    ['user_id' => Auth::id(), 'card_id' => $card->id, 'current_month' => $cycleMonth],
//                    ['paid' => false]
//                );
//
//                $exists = InvoiceItem::where('invoice_id', $invoice->id)
//                    ->where('recurrent_id', $recurrent->id)
//                    ->whereDate('date', $occur->toDateString())
//                    ->exists();
//
//                if (!$exists) {
//                    InvoiceItem::create([
//                        'invoice_id' => $invoice->id,
//                        'recurrent_id' => $recurrent->id,
//                        'title' => $request->title,
//                        'amount' => $transaction->amount,
//                        'date' => $occur->copy(),
//                        'transaction_category_id' => $request->transaction_category_id,
//                        'installments' => 1,
//                        'current_installment' => 1,
//                        'is_projection' => true,
//                    ]);
//                }
//
//                $occur = $nextFn($occur);
//            }
//
//            $transaction->amount = brlPrice($transaction->amount);
//            $transaction->date = $transaction->date->locale('pt_BR')->isoFormat('DD/MMM.');
//
//            return $this->handleRecurrentCard($request, $txDate);
//        }
//
//        // ============================
//        // Caso 3: Transações únicas (Pix, Dinheiro ou Débito)
//        // ============================
//        $transaction = $this->transaction->create([
//            'user_id' => Auth::id(),
//            'account_id' => $request->account_id,
//            'card_id' => $request->card_id,
//            'transaction_category_id' => $request->transaction_category_id,
//            'title' => $request->title,
//            'description' => $request->description,
//            'amount' => $request->amount,
//            'date' => $txDate,
//            'type' => $request->type,
//            'type_card' => $typeCard,
//            'recurrence_type' => 'unique',
//            'custom_occurrences' => null,
//        ]);
//
//        // Se for Pix/Dinheiro/Débito → debita saldo da conta
//        if (in_array($request->type, ['pix', 'money']) || ($isCard && $typeCard === 'debit')) {
//            if ($request->account_id) {
//                Account::where('id', $request->account_id)->decrement('current_balance', $request->amount);
//            }
//        }
//
//        return response()->json([
//            'message' => 'Transação registrada com sucesso',
//            'transaction' => $transaction
//        ]);
//    }

    public function store(Request $request)
    {
        // dd($request->all());

        $request->validate([
            'title' => 'required|string|max:255',
            'transaction_category_id' => 'required|uuid|exists:transaction_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'type' => 'required|in:pix,card,money',
            'type_card' => 'nullable|in:credit,debit',
            'recurrence_type' => 'nullable|in:unique,monthly,yearly,custom',
            'interval_value' => 'nullable|integer|min:1',
            'installments' => 'nullable|integer|min:1',
            'account_id' => 'nullable|uuid|exists:accounts,id',
            'card_id' => 'required_unless:alternate_cards,1|nullable|uuid|exists:cards,id',
            'alternate_cards' => ['nullable','boolean'],
            'alternate_card_ids' => ['required_if:alternate_cards,1','array'],
            'alternate_card_ids.*' => ['uuid','exists:cards,id'],
        ]);

        $txDate       = Carbon::parse($request->date)->startOfDay();
        $isCard       = $request->type === 'card';
        $typeCard     = $isCard ? $request->type_card : null;
        $installments = (int) ($request->installments ?? 1);

        // 📌 1) Parcelamento no crédito
        if ($isCard && $typeCard === 'credit' && $installments > 1 && $request->recurrence_type === 'unique') {
            return $this->handleInstallments($request, $txDate, $installments);
        }

        // 📌 2) Recorrência no crédito
        $isRecurring = $request->recurrence_type !== 'unique';
        if ($isCard && $typeCard === 'credit' && $isRecurring) {
            return $this->handleRecurringCard($request, $txDate);
        }

        // 📌 3) Transações únicas (Pix, dinheiro, débito)
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
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $txDate,
            'type' => 'card',
            'type_card' => 'credit',
            'recurrence_type' => 'unique', // enum válido
            'custom_occurrences' => $installments, // guarda o nº de parcelas
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
        // pega o tipo da categoria para saber se credita/debita conta
        $catType = optional(\App\Models\TransactionCategory::find($request->transaction_category_id))->type;
        // 'entrada' | 'despesa' | 'investimento'

        $recurrenceType = $request->recurrence_type ?? 'unique';
        $isRecurring    = $recurrenceType !== 'unique';

        // 1) Cria a transação base com o recurrence_type ORIGINAL do formulário
        $transaction = $this->transaction->create([
            'user_id'                  => Auth::id(),
            'account_id'               => $request->account_id,
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

        // 2) Se for RECORRENTE e NÃO for cartão de crédito → cria recorrente + itens legados
        if ($isRecurring && !($isCard && $typeCard === 'credit')) {

            // cria o registro "recurrent"
            $recurrent = $this->recurrent->create([
                'user_id'       => $transaction->user_id,
                'transaction_id'=> $transaction->id,
                'payment_day'   => $txDate->format('d'),
                'amount'        => $transaction->amount,
            ]);

            if ($transaction->recurrence_type === 'monthly') {
                $this->monthlyItemRecurrents->create([
                    'recurrent_id'    => $recurrent->id,
                    'payment_day'     => $txDate->format('d'),
                    'reference_month' => $txDate->format('m'),
                    'reference_year'  => $txDate->format('Y'),
                    'amount'          => $transaction->amount,
                    'status'          => false,
                ]);
            }

            if ($transaction->recurrence_type === 'yearly') {
                $this->yearlyItemRecurrents->create([
                    'recurrent_id'   => $recurrent->id,
                    'payment_day'    => $txDate->format('d'),
                    'reference_year' => $txDate->format('Y'),
                    'amount'         => $transaction->amount,
                    'status'         => false,
                ]);
            }

            if ($transaction->recurrence_type === 'custom') {
                $total     = (int)($transaction->custom_occurrences ?? 0);
                $startDate = $txDate->copy();

                for ($i = 0; $i < $total; $i++) {
                    $current = $startDate->copy()->addMonths($i); // mantém sua lógica original (mensal)

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

            // Recorrente: NÃO movimenta saldo agora. Retorna.
            return response()->json([
                'message'     => 'Transação recorrente registrada com sucesso',
                'transaction' => $transaction,
            ]);
        }

        // 3) Se for ÚNICA (pix/dinheiro/débito) → movimenta saldo da conta AGORA
        if (!$isRecurring && (in_array($request->type, ['pix', 'money']) || ($isCard && $typeCard === 'debit'))) {
            if ($request->account_id) {
                // se categoria é 'entrada' credita, se 'despesa'/'investimento' debita
                if ($catType === 'entrada') {
                    \App\Models\Account::where('id', $request->account_id)
                        ->increment('current_balance', $transaction->amount);
                } else {
                    \App\Models\Account::where('id', $request->account_id)
                        ->decrement('current_balance', $transaction->amount);
                }
            }
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
        // normaliza sábado/domingo
        $normalize = function (Carbon $d) use ($includeSat, $includeSun) {
            if (!$includeSat && $d->isSaturday()) $d->addDays(2);
            if (!$includeSun && $d->isSunday())   $d->addDay();
            return $d;
        };

        // próxima ocorrência
        $nextFn = function (Carbon $d) use ($unit, $value, $normalize) {
            return $normalize(match ($unit) {
                'days'  => $d->copy()->addDays($value),
                'years' => $d->copy()->addYearsNoOverflow($value),
                default => $d->copy()->addMonthsNoOverflow($value),
            });
        };

        $horizonEnd = $txDate->copy()->addMonths(12)->endOfMonth();
        $occur      = $normalize($txDate->copy());

        // decide alternância **pelo request**, não pelo model
        $useAlt = (bool)($request->alternate_cards)
            && collect($request->alternate_card_ids ?? [])->filter()->isNotEmpty();

        if ($useAlt) {
            $altIds = collect($request->alternate_card_ids)->filter()->unique()->values();

            // persiste vínculos (histórico/consulta futura)
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
                // escolhe o cartão cujo "último fechamento" é o mais recente <= ocorrência
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

                $this->createInvoiceItemIfNotExists($invoice->id, $recurrent->id, $request, $occur);

                $occur = $nextFn($occur);
            }
        } else {
            // cartão fixo
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

                $this->createInvoiceItemIfNotExists($invoice->id, $recurrent->id, $request, $occur);

                $occur = $nextFn($occur);
            }
        }

        return response()->json([
            'message'       => 'Recorrência registrada com sucesso',
            'transaction'   => $transaction,
            'recurrent_id'  => $recurrent->id,
        ]);
    }


    protected function createInvoiceItemIfNotExists($invoiceId, $recurrentId, Request $request, Carbon $date)
    {
        $exists = InvoiceItem::where('invoice_id', $invoiceId)
            ->where('recurrent_id', $recurrentId)
            ->whereDate('date', $date->toDateString())
            ->exists();

        if (!$exists) {
            InvoiceItem::create([
                'invoice_id' => $invoiceId,
                'recurrent_id' => $recurrentId,
                'title' => $request->title,
                'amount' => $request->amount, // recorrência = valor por ocorrência
                'date' => $date->copy(),
                'transaction_category_id' => $request->transaction_category_id,
                'installments' => 1,
                'current_installment' => 1,
                'is_projection' => true,
            ]);
        }
    }
}
