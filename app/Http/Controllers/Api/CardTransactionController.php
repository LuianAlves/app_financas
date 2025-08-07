<?php

namespace App\Http\Controllers\Api;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceItemController extends Controller
{
    public function index()
    {
        return response()->json(
            InvoiceItem::whereIn('card_id', Auth::user()->cards()->pluck('id'))
                ->latest('date')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'card_id' => 'required|uuid|exists:cards,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'installments' => 'nullable|integer|min:1|max:60',
            'current_installment' => 'nullable|integer|min:1|max:60',
            'category_id' => 'nullable|uuid|exists:categories,id'
        ]);

        $transaction = InvoiceItem::create($data);

        return response()->json($transaction, 201);
    }

    public function show(InvoiceItem $invoiceItem)
    {
        $this->authorize('view', $invoiceItem);
        return response()->json($invoiceItem);
    }

    public function update(Request $request, InvoiceItem $invoiceItem)
    {
        $this->authorize('update', $invoiceItem);

        $data = $request->validate([
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric',
            'date' => 'sometimes|date',
            'installments' => 'nullable|integer|min:1|max:60',
            'current_installment' => 'nullable|integer|min:1|max:60',
            'category_id' => 'nullable|uuid|exists:categories,id'
        ]);

        $invoiceItem->update($data);

        return response()->json($invoiceItem);
    }

    public function destroy(InvoiceItem $invoiceItem)
    {
        $this->authorize('delete', $invoiceItem);
        $invoiceItem->delete();

        return response()->json(null, 204);
    }
}
