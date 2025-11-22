<?php

namespace App\Http\Controllers\Api;

use App\Models\Saving;
use App\Models\Account;
use App\Services\InvestmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SavingController extends Controller
{
    public function __construct(
        protected InvestmentService $investmentService
    ) {}

    /* ============================================================
     *  Helper — converter entrada monetária para float
     * ============================================================ */
    private function moneyToFloat($v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float)$v;

        $s = preg_replace('/[^\d,.\-]/', '', (string)$v);
        $s = preg_replace('/\.(?=\d{3}(\D|$))/', '', $s); // remover milhar
        $s = str_replace(',', '.', $s);
        return (float)$s;
    }

    /* ============================================================
     *  LISTAR INVESTIMENTOS
     * ============================================================ */
    public function index()
    {
        $savings = Saving::with(['account', 'lots', 'lots.pendingYields'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($savings);
    }

    /* ============================================================
     *  CRIAR INVESTIMENTO
     * ============================================================ */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'current_amount' => 'nullable', // aceita null
            'cdi_percent'    => 'required|numeric|min:0.50|max:5.00',
            'start_date'     => 'nullable|date',
            'notes'          => 'nullable|string',
            'account_id'     => [
                'nullable', 'uuid',
                Rule::exists('accounts', 'id')->where('user_id', Auth::id()),
            ],
        ]);

        $data['current_amount'] = $this->moneyToFloat($data['current_amount'] ?? 0);
        $data['user_id'] = Auth::id();

        $startDate = !empty($data['start_date'])
            ? Carbon::parse($data['start_date'])
            : Carbon::now();

        // 🟢 Criar o investimento inicialmente SEM saldo
        $saving = Saving::create([
            'user_id'        => $data['user_id'],
            'account_id'     => $data['account_id'] ?? null,
            'name'           => strtoupper($data['name']),
            'current_amount' => 0, // saldo real vem da soma das cotas
            'start_date'     => $startDate,
            'notes'          => $data['notes'] ?? null,
            'cdi_percent'    => (float) $data['cdi_percent'],
        ]);

        // 🟢 Criar cota inicial (se houver aporte inicial)
        if ($data['current_amount'] > 0) {
            $this->investmentService->deposit(
                saving: $saving,
                amount: $data['current_amount'],
                date: $startDate,
                account: $data['account_id'] ? Account::find($data['account_id']) : null,
                notes: 'Aporte Inicial'
            );
        }

        $saving->load(['account', 'lots']);

        return response()->json($saving, 201);
    }

    /* ============================================================
     *  DETALHES DO INVESTIMENTO
     * ============================================================ */
    public function show($id)
    {
        $saving = Saving::with([
            'account',
            'lots',
            'lots.pendingYields',
            'lots.movements',
        ])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($saving);
    }

    /* ============================================================
     *  ATUALIZAR INVESTIMENTO
     * ============================================================ */
    public function update(Request $request, $id)
    {
        $saving = Saving::where('user_id', Auth::id())->findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'notes'       => 'sometimes|string|nullable',
            'cdi_percent' => 'sometimes|numeric|min:0.50|max:5.00',
            'account_id'  => [
                'sometimes', 'nullable', 'uuid',
                Rule::exists('accounts', 'id')->where('user_id', Auth::id()),
            ],
        ]);

        if (!empty($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }

        $saving->update($data);

        $saving->load(['account', 'lots']);

        return response()->json($saving);
    }

    /* ============================================================
     *  EXCLUIR INVESTIMENTO
     * ============================================================ */
    public function destroy($id)
    {
        $saving = Saving::where('user_id', Auth::id())->findOrFail($id);

        // ❗ Se quiser garantir que não exclui investimentos com saldo, posso adicionar validação aqui

        $saving->delete();

        return response()->json(null, 204);
    }
}
