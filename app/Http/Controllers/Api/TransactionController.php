<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
    public function index()
    {
        $transactions = Auth::user()->transactions()
            ->with(['category', 'card'])
            ->latest('date')
            ->get();

        $transactions->each(function ($t) {
            $t->amount = brlPrice($t->amount); // helper de formatação
        });

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'type' => 'required|in:pix,card,money',
            'type_card' => 'nullable|in:credit,debit',
            'transaction_category_id' => 'required|uuid|exists:transaction_categories,id',
            'card_id' => 'nullable|uuid|exists:cards,id',
            'recurrence_type' => 'nullable|in:unique,monthly,yearly,custom',
            'recurrence_custom' => 'nullable|integer|min:1',
            'installments' => 'nullable|integer|min:1',
        ]);

        $data['user_id'] = Auth::id();

        $transaction = Transaction::create($data);
        $transaction->load('category', 'card');

        return response()->json($transaction, 201);
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
