<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    public $card;

    public function __construct(Card $card)
    {
        $this->card = $card;
    }

    public function index()
    {
        $cards = $this->card::with('account')->get();

        $cards->each(function ($card) {
            $card->cardholder_name = strtoupper($card->cardholder_name);
            $card->credit_limit = brlPrice($card->credit_limit);
            $card->account->bank_name = strtoupper($card->account->bank_name);
        });

        return response()->json($cards);
    }

    public function store(Request $request)
    {
        $card = $this->card->with('account')->create([
            'user_id' => Auth::id(),
            'account_id' => $request->account_id,
            'cardholder_name' => $request->cardholder_name,
            'last_four_digits' => $request->last_four_digits,
            'brand' => $request->brand,
            'color_card' => $request->color_card,
            'credit_limit' => $request->credit_limit,
            'closing_day' => $request->closing_day,
            'due_day' => $request->due_day,
        ]);

        $card->cardholder_name = strtoupper($card->cardholder_name);
        $card->credit_limit = brlPrice($card->credit_limit);
        $card->account->bank_name = strtoupper($card->account->bank_name);

        return response()->json($card);
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
            'name' => 'sometimes|string|max:255',
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
