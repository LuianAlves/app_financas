<?php

namespace App\Http\Controllers;

use App\Models\CardTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CardTransactionController extends Controller
{
    public function index()
    {
        return response()->json(
            CardTransaction::whereIn('card_id', Auth::user()->cards()->pluck('id'))
                ->latest('date')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'card_id' => 'required|uuid|exists:cards,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'installments' => 'nullable|integer|min:1|max:60',
            'current_installment' => 'nullable|integer|min:1|max:60',
            'category_id' => 'nullable|uuid|exists:categories,id'
        ]);

        $transaction = CardTransaction::create($data);

        return response()->json($transaction, 201);
    }

    public function show(CardTransaction $cardTransaction)
    {
        $this->authorize('view', $cardTransaction);
        return response()->json($cardTransaction);
    }

    public function update(Request $request, CardTransaction $cardTransaction)
    {
        $this->authorize('update', $cardTransaction);

        $data = $request->validate([
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric',
            'date' => 'sometimes|date',
            'installments' => 'nullable|integer|min:1|max:60',
            'current_installment' => 'nullable|integer|min:1|max:60',
            'category_id' => 'nullable|uuid|exists:categories,id'
        ]);

        $cardTransaction->update($data);

        return response()->json($cardTransaction);
    }

    public function destroy(CardTransaction $cardTransaction)
    {
        $this->authorize('delete', $cardTransaction);
        $cardTransaction->delete();

        return response()->json(null, 204);
    }
}
