<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
    public function index()
    {

        $invoice = $this->invoice::with('account')->get();

        $invoice->each(function($invoice){
            $invoice->card = strtoupper($invoice->card->name);
            $invoice->current_month = strtoupper($invoice->current_month);
            $invoice->paid =  strtoupper($invoice->paid);
        });

        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $invoice = $this->invoice::with('account')->create([
            'user_id' => Auth::id(),
            'card_id' => $request->card_id,
            'current_month' => $request->month,
            'paid' => $request->paid,
        ]);

        $invoice->each(function($invoice){
            $invoice->card = strtoupper($invoice->card->name);
            $invoice->current_month = strtoupper($invoice->current_month);
            $invoice->paid =  strtoupper($invoice->paid);
        });

        return response()->json($invoice, 201);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        return response()->json($invoice);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'current_month' => 'required|string|max:10',
            'paid' => 'sometimes|boolean'
        ]);

        $invoice->update($data);

        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();

        return response()->json(null, 204);
    }
}
