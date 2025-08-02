<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        return response()->json(
            Auth::user()->transactions()->latest('date')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'account_id' => 'nullable|uuid|exists:accounts,id'
        ]);

        $transaction = Auth::user()->transactions()->create($data);

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
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric',
            'date' => 'sometimes|date',
            'type' => 'sometimes|in:income,expense',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'account_id' => 'nullable|uuid|exists:accounts,id'
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
