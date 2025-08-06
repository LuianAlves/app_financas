<?php

namespace App\Http\Controllers\Api;

use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SavingController extends Controller
{
    public function index()
    {
        return response()->json(
            Auth::user()->savings()->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'current_amount' => 'required|numeric',
            'account_id' => 'nullable|uuid|exists:accounts,id'
        ]);

        $saving = Auth::user()->savings()->create($data);

        return response()->json($saving, 201);
    }

    public function show(Saving $saving)
    {
        $this->authorize('view', $saving);
        return response()->json($saving);
    }

    public function update(Request $request, Saving $saving)
    {
        $this->authorize('update', $saving);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'current_amount' => 'sometimes|numeric',
            'account_id' => 'nullable|uuid|exists:accounts,id'
        ]);

        $saving->update($data);

        return response()->json($saving);
    }

    public function destroy(Saving $saving)
    {
        $this->authorize('delete', $saving);
        $saving->delete();

        return response()->json(null, 204);
    }
}
