<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            Auth::user()->categories()->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'monthly_limit' => 'nullable|numeric',
            'color' => 'nullable|string|max:50'
        ]);

        $category = Auth::user()->categories()->create($data);

        return response()->json($category, 201);
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
