<?php

namespace App\Http\Controllers\Web;

use App\Models\Account;
use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CardController extends    Controller
{
    public function __construct(Card $card)
    {
        $this->card = $card;
    }

    public function index()
    {

        $accounts = Account::where('user_id', Auth::id())->get();
        return view('app.cards.card_index', compact('accounts'));

    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'credit_limit' => 'required|numeric',
            'closing_day'  => 'required|integer|min:1|max:31',
            'due_day'      => 'required|integer|min:1|max:31',
            'account_id'   => 'nullable|uuid|exists:accounts,id',
        ]);

        $data['user_id'] = Auth::id();
        $data['created_at'] = Carbon::now();

        $card = $this->card->create($data);
        $card->load('account');

        return response()->json([
            'name'         => $card->name,
            'credit_limit' => $card->credit_limit,
            'closing_day'  => $card->closing_day,
            'due_day'      => $card->due_day,
            'account_name' => optional($card->account)->bank_name,
        ]);
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

    public function indexJson()
    {
        $cards = $this->card->with('account')->where('user_id', Auth::id())->get();

        return response()->json($cards->map(function ($card) {
            return [
                'name'         => $card->name,
                'credit_limit' => $card->credit_limit,
                'closing_day'  => $card->closing_day,
                'due_day'      => $card->due_day,
                'account_name' => optional($card->account)->bank_name,
            ];
        }));
    }

}
