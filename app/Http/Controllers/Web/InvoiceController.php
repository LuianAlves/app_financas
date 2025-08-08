<?php

namespace App\Http\Controllers\Web;


use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $cards = Card::all();

        return view('app.invoices.invoice.invoice_index', compact('cards'));
    }
}
