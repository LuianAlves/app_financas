<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CardController extends    Controller
{
    public function index()
    {
        return response()->json(
            Auth::user()->cards()->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nickname' => 'required|string|max:255',
            'credit_limit' => 'required|numeric',
            'closing_day' => 'required|integer|min:1|max:31',
            'due_day' => 'required|integer|min:1|max:31',
            'account_id' => 'nullable|uuid|exists:accounts,id',
        ]);

        $card = Auth::user()->cards()->create($data);

        return response()->json($card, 201);
    }

    public function show(Card $card)
    {
        $this->authorize('view', $card);
        return response()->json($card);
    }

    public function update(Request $request, Card $card)
    {
        $this->authorize('update', $card);

        $data = $request->validate([
            'nickname' => 'sometimes|string|max:255',
            'credit_limit' => 'sometimes|numeric',
            'closing_day' => 'sometimes|integer|min:1|max:31',
            'due_day' => 'sometimes|integer|min:1|max:31',
            'account_id' => 'nullable|uuid|exists:accounts,id',
        ]);

        $card->update($data);

        return response()->json($card);
    }

    public function destroy(Card $card)
    {
        $this->authorize('delete', $card);
        $card->delete();

        return response()->json(null, 204);
    }
}
