<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        return response()->json(
            Invoice::whereIn('card_id', Auth::user()->cards()->pluck('id'))
                ->orderByDesc('closing_date')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'card_id' => 'required|uuid|exists:cards,id',
            'closing_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:closing_date',
            'total_amount' => 'required|numeric',
            'paid' => 'boolean'
        ]);

        $invoice = Invoice::create($data);

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
            'closing_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:closing_date',
            'total_amount' => 'sometimes|numeric',
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
