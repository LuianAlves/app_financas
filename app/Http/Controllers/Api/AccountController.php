<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function index()
    {
        $accounts = $this->account->with('savings')->get();

        $accounts->each(function ($account) {


            if (isset($account->savings[0]) && isset($account->savings[0]->current_amount)) {
                $account->total = $account->savings[0]->current_amount + $account->current_balance;

                $account->current_balance = brlPrice($account->current_balance);
                $account->savings[0]->current_amount = brlPrice($account->savings[0]->current_amount);
                $account->saving_amount = $account->savings[0]->current_amount;

                $account->total = brlPrice($account->total);
            } else {
                $account->total = brlPrice($account->current_balance);

                $account->current_balance = brlPrice($account->current_balance);
                $account->saving_amount = brlPrice(0);
            }

        });

        return response()->json($accounts);
    }

    public function create()
    {
        return view('app.accounts.account_create');
    }

    public function store(Request $request)
    {
//        $request->validate([
//            'bank_name' => 'required|string|max:255',
//            'current_balance' => 'required|numeric',
//            'type' => 'required|string|in:checking,savings,other'
//        ]);

        $account = $this->account->create([
            'user_id' => Auth::id(),
            'bank_name' => $request->bank_name,
            'current_balance' => $request->current_balance,
            'type' => $request->type,
            'created_at' => Carbon::now()
        ]);

        return response()->json([
            'bank_name' => $account->bank_name,
            'current_balance' => $account->current_balance,
            'account_type' => $account->account_type,
        ]);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);
        return response()->json($account);
    }

    public function update(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $data = $request->validate([
            'bank_name' => 'sometimes|string|max:255',
            'current_balance' => 'sometimes|numeric',
            'type' => 'sometimes|string|in:checking,savings,other'
        ]);

        $account->update($data);

        return response()->json($account);
    }

    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);
        $account->delete();

        return response()->json(null, 204);
    }
}
