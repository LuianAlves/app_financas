<?php

namespace App\Http\Controllers\Api;

use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionCategoryController extends Controller
{
    public $transactionCategory;

    public function __construct(TransactionCategory $transactionCategory)
    {
        $this->transactionCategory = $transactionCategory;
    }

    public function index()
    {
        $transactionCategories = $this->transactionCategory->all();

        $transactionCategories->each(function ($transactionCategory) {
            $transactionCategory->monthly_limit = brlPrice($transactionCategory->monthly_limit);
            $transactionCategory->type = ucfirst($transactionCategory->type);
        });

        return response()->json($transactionCategories);
    }

    public function store(Request $request)
    {
        $limit = $request->input('has_limit') === '1'
            ? $request->input('monthly_limit', 0)
            : 0;

        $transactionCategory = $this->transactionCategory->create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'monthly_limit' => $limit,
            'color' => $request->color,
            'icon' => $request->icon
        ]);

        $transactionCategory->monthly_limit = brlPrice($transactionCategory->monthly_limit);
        $transactionCategory->type = ucfirst($transactionCategory->type);

        return response()->json($transactionCategory);
    }

    public function show(TransactionCategory $category)
    {
        $this->authorize('view', $category);
        return response()->json($category);
    }

    public function update(Request $request, TransactionCategory $category)
    {
        $this->authorize('update', $category);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'monthly_limit' => 'nullable|numeric',
            'color' => 'nullable|string|max:50'
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(TransactionCategory $category)
    {
        $this->authorize('delete', $category);
        $category->delete();

        return response()->json(null, 204);
    }
}
