<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function index()
    {
        return response()->json(
            Auth::user()->accounts()->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'current_balance' => 'required|numeric',
            'type' => 'required|string|in:checking,savings,other'
        ]);

        $account = Auth::user()->accounts()->create($data);

        return response()->json($account, 201);
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
