<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public $account;

    public function __construct(Account $account) {
        $this->account = $account;
    }

    public function index()
    {
        $accounts = $this->account->all();

        return view('app.accounts.account_index', compact('accounts'));
    }

    public function create() {
        return view('app.accounts.account_create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'current_balance' => 'required|numeric',
            'type' => 'required|string|in:checking,savings,other'
        ]);

        $this->account->create([
            'user_id' => Auth::id(),
            'bank_name' => $request->bank_name,
            'current_balance' => $request->current_balance,
            'type' => $request->type,
            'created_at' => Carbon::now()
        ]);

        return redirect()->route('accounts.index');
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
