<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Saving;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $account = Account::where('user_id', Auth::id())->sum('current_balance');
        $savings = Saving::where('user_id', Auth::id())->sum('current_amount');

        $total = $account + $savings;

        return view('app.dashboard', compact('account', 'savings', 'total'));
    }
}
