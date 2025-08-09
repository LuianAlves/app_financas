<?php

namespace App\Http\Controllers\Api;

use AllowDynamicProperties;
use App\Models\Card;
use App\Models\CustomItemRecurrents;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MonthlyItemRecurrents;
use App\Models\Recurrent;
use App\Models\Transaction;
use App\Models\YearlyItemRecurrents;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

#[AllowDynamicProperties] class TransactionController extends Controller
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

            $transaction->date = Carbon::parse($transaction->date)->locale('pt_BR')->isoFormat('DD/MMM.');
        });

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $isCard = $request->card_id !== null;
        $typeCard = $isCard ? $request->type_card : null;

        if ($isCard && $typeCard === 'credit') {
            $request->recurrence_type = null;

            $txDate = Carbon::parse($request->date);

            $card = Card::findOrFail($request->card_id);
            $closingDay = $card->closing_day; // ex: 2

            $cycleMonth = $txDate->day > $closingDay
                ? $txDate->copy()->addMonth()->format('Y-m')
                : $txDate->copy()->format('Y-m');

            $totalInstallments = (int)$request->installments;
            $perInstallment = $request->amount / $totalInstallments;

            $firstCycleDate = Carbon::createFromFormat('Y-m-d', $cycleMonth . '-01');

            for ($i = 0; $i < $totalInstallments; $i++) {
                $cycleDate = $firstCycleDate->copy()->addMonths($i);
                $monthKey = $cycleDate->format('Y-m');

                $invoice = Invoice::firstOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'card_id' => $card->id,
                        'current_month' => $monthKey,
                    ],
                    ['paid' => false]
                );

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'title' => $request->title,
                    'amount' => $perInstallment,
                    'date' => $txDate->copy()->addMonths($i),
                    'transaction_category_id' => $request->transaction_category_id,
                    'installments' => $totalInstallments,
                    'current_installment' => $i + 1,
                ]);
            }
        }

        $transaction = $this->transaction->create([
            'user_id' => Auth::id(),
            'card_id' => $request->card_id ?? null,
            'transaction_category_id' => $request->transaction_category_id,
            'title' => $request->title ?? null,
            'description' => $request->description ?? null,
            'amount' => $request->amount,
            'date' => Carbon::parse($request->date),
            'type' => $request->type,
            'type_card' => $typeCard ?? null,
            'recurrence_type' => $request->recurrence_type,
            'custom_occurrences' => $request->custom_occurrences ?? $request->installments,
        ]);

        if ($transaction->recurrence_type !== 'unique' && !$isCard) {
            $recurrent = $this->recurrent->create([
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'payment_day' => Carbon::parse($transaction->date)->format('d'),
                'amount' => $transaction->amount
            ]);

            if ($transaction->recurrence_type === 'monthly') {
                $this->monthlyItemRecurrents->create([
                    'recurrent_id' => $recurrent->id,
                    'payment_day' => Carbon::parse($transaction->date)->format('d'),
                    'reference_month' => Carbon::parse($transaction->date)->format('m'),
                    'reference_year' => Carbon::parse($transaction->date)->format('Y'),
                    'amount' => $transaction->amount,
                    'status' => false
                ]);
            }

            if ($transaction->recurrence_type === 'yearly') {
                $this->yearlyItemRecurrents->create([
                    'recurrent_id' => $recurrent->id,
                    'payment_day' => Carbon::parse($transaction->date)->format('d'),
                    'reference_year' => Carbon::parse($transaction->date)->format('Y'),
                    'amount' => $transaction->amount,
                    'status' => false
                ]);
            }

            if ($transaction->recurrence_type === 'custom') {
                $total = (int)$transaction->custom_occurrences;
                $startDate = Carbon::parse($transaction->date);

                for ($i = 0; $i < $total; $i++) {
                    $current = $startDate->copy()->addMonths($i);

                    $this->customItemRecurrents->create([
                        'recurrent_id' => $recurrent->id,
                        'payment_day' => $current->format('d'),
                        'reference_month' => $current->format('m'),
                        'reference_year' => $current->format('Y'),
                        'amount' => $transaction->amount,
                        'custom_occurrence_number' => $i + 1,
                        'status' => false,
                    ]);
                }
            }
        }


        $transaction->amount = brlPrice($transaction->amount);

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

        $transaction->date = Carbon::parse($transaction->date)->locale('pt_BR')->isoFormat('DD/MMM.');

        return response()->json($transaction);
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        return response()->json($transaction);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'amount' => 'sometimes|numeric',
            'date' => 'sometimes|date',
            'type' => 'sometimes|in:pix,card,money',
            'type_card' => 'nullable|in:credit,debit',
            'transaction_category_id' => 'sometimes|uuid|exists:transaction_categories,id',
            'card_id' => 'nullable|uuid|exists:cards,id',
            'recurrence_type' => 'nullable|in:unique,monthly,yearly,custom',
            'recurrence_custom' => 'nullable|integer|min:1',
            'installments' => 'nullable|integer|min:1',
        ]);

        $transaction->update($data);

        return response()->json($transaction);
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);
        $transaction->delete();

        return response()->json(null, 204);
    }
}
