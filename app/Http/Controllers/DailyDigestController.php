<?php

// app/Http/Controllers/DailyDigestController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DailyDigestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tz = 'America/Sao_Paulo';
        $today = now($tz)->startOfDay();
        $tomorrow = (clone $today)->addDay();

        $txBase = $user->transactions()->with('transactionCategory');

        // HOJE
        $todayBase = (clone $txBase)->whereDate('date', $today->toDateString());
        $todayIn   = (clone $todayBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','entrada'))->get();
        $todayOut  = (clone $todayBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','despesa'))->get();
        $todayInv  = (clone $todayBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','investimento'))->get();
        // // Se tiver Investment:
        // $todayInv = class_exists(\App\Models\Investment::class)
        //     ? $user->investments()->whereDate('date', $today->toDateString())->get()
        //     : $todayInv;

        // AMANHÃ
        $tomBase = (clone $txBase)->whereDate('date', $tomorrow->toDateString());
        $tomIn   = (clone $tomBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','entrada'))->get();
        $tomOut  = (clone $tomBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','despesa'))->get();
        $tomInv  = (clone $tomBase)->whereHas('transactionCategory', fn($q)=>$q->where('type','investimento'))->get();
        // // Investment:
        // $tomInv = class_exists(\App\Models\Investment::class)
        //     ? $user->investments()->whereDate('date', $tomorrow->toDateString())->get()
        //     : $tomInv;

        // PRÓXIMOS (exclui hoje e amanhã)
        $afterTomorrow = (clone $tomorrow)->addDay();
        $nextFive = (clone $txBase)
            ->whereDate('date', '>=', $afterTomorrow->toDateString())
            ->orderBy('date')
            ->limit(5)
            ->get();

        return view('app.digest.index', compact(
            'today', 'tomorrow',
            'todayIn','todayOut','todayInv',
            'tomIn','tomOut','tomInv',
            'nextFive'
        ));
    }
}

