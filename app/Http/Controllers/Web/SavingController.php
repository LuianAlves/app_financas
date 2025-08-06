<?php

namespace App\Http\Controllers\Web;

use App\Models\Account;
use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SavingController extends Controller
{
    public function index()
    {
        $accounts = Account::all();

        return view('app.savings.saving_index', compact('accounts'));
    }
}
