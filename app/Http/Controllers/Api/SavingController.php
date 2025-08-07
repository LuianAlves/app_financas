<?php

namespace App\Http\Controllers\Api;

use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SavingController extends Controller
{
    protected $saving;

    public function __construct(Saving $saving)
    {
        $this->saving = $saving;
    }

    /**
     * Lista todos os cofrinhos do usuário autenticado.
     */
    public function index()
    {
        $savings = $this->saving::with(['account'])
            ->where('user_id', Auth::id())
            ->get();

        $savings->each(function ($saving) {
            $saving->name = strtoupper($saving->name);
            if ($saving->account) {
                $saving->account->bank_name = strtoupper($saving->account->bank_name);
            }
        });

        return response()->json($savings);
    }

    /**
     * Cria um novo cofrinho.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'current_amount' => 'required|numeric|min:0',
            'account_id'     => 'nullable|uuid|exists:accounts,id',
            'color_card'     => 'nullable|string|max:7',
        ]);

        $data['user_id'] = Auth::id();

        $saving = $this->saving->create($data);
        $saving->load('account');

        $saving->name = strtoupper($saving->name);
        if ($saving->account) {
            $saving->account->bank_name = strtoupper($saving->account->bank_name);
        }

        return response()->json($saving);
    }

    /**
     * Exibe um cofrinho específico.
     */
    public function show(Saving $saving)
    {
        $this->authorize('view', $saving);
        return response()->json($saving->load('account'));
    }

    /**
     * Atualiza os dados do cofrinho.
     */
    public function update(Request $request, Saving $saving)
    {
        $this->authorize('update', $saving);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'current_amount' => 'sometimes|numeric|min:0',
            'account_id'     => 'nullable|uuid|exists:accounts,id',
            'color_card'     => 'nullable|string|max:7',
        ]);

        $saving->update($data);
        $saving->load('account');

        $saving->name = strtoupper($saving->name);
        if ($saving->account) {
            $saving->account->bank_name = strtoupper($saving->account->bank_name);
        }

        return response()->json($saving);
    }

    /**
     * Remove um cofrinho.
     */
    public function destroy(Saving $saving)
    {
        $this->authorize('delete', $saving);
        $saving->delete();

        return response()->json(null, 204);
    }
}
