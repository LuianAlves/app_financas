<?php

namespace App\Http\Controllers\Api;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceItemController extends Controller
{
    public $invoiceItem;
    public function __construct(InvoiceItem $invoiceItem)
    {
        $this->invoiceItem = $invoiceItem;
    }

    public function index()
    {
        $invoiceItem = InvoiceItem::all();

        $invoiceItem->each(function($invoiceItem){
            $invoiceItem->descreption = strtoupper($invoiceItem->descreption);
            $invoiceItem->amount = brlPrice($invoiceItem->amount);
            $invoiceItem->date = strtoupper($invoiceItem->date);
            $invoiceItem->current_installment = $invoiceItem->current_installment . 'x';

        });
        return response()->json($invoiceItem);
    }

    public function store(Request $request)
    {
        $invoiceItem = $this->invoiceItem::with('account')->create([
            'user_id' => Auth::id(),
            'invoice_id' => $request->invoice_id,
            'card_id' => $request->card_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'installments' => $request->installments ?? 1,
            'current_installment' => $request->current_installment ?? 1,
            'transaction_category_id' => $request->transaction_category_id
        ]);

        $invoiceItem->each(function($invoiceItem){
            $invoiceItem->descreption = strtoupper($invoiceItem->descreption);
            $invoiceItem->amount = brlPrice($invoiceItem->amount);
            $invoiceItem->date = strtoupper($invoiceItem->date);
            $invoiceItem->current_installment = $invoiceItem->current_installment . 'x';

        });



        return response()->json($invoiceItem, 201);
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
