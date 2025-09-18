<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SavingController extends Controller
{
    /* ======================== Helpers ======================== */

    /** Converte "1.234,56" ou "1234,56" em float 1234.56 */
    private function moneyToFloat($v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float)$v;
        $s = preg_replace('/[^\d,.\-]/', '', (string)$v);
        // remove separador de milhar (.)
        $s = preg_replace('/\.(?=\d{3}(\D|$))/', '', $s);
        // vírgula como decimal
        $s = str_replace(',', '.', $s);
        return (float)$s;
    }

    /** Interpreta taxa. "1" => 1% => 0.01 ; "0,8" => 0,8% => 0.008 */
    private function parseRate($v): float
    {
        $n = $this->moneyToFloat($v);
        return $n >= 1 ? $n / 100.0 : $n; // 1 => 0.01
    }

    /** Juros compostos: P * ((1 + r)^n - 1) */
    private function compoundYield(float $principal, float $rate, int $periods): float
    {
        if ($principal <= 0 || $rate <= 0 || $periods <= 0) return 0.0;
        return $principal * (pow(1 + $rate, $periods) - 1);
    }

    /** Retorna períodos inteiros decorridos conforme rate_period */
    private function elapsedPeriods(?string $startDate, ?string $period): int
    {
        if (!$startDate) return 0;
        $start = Carbon::parse($startDate);
        $now   = Carbon::now();
        if ($period === 'monthly') {
            return $start->diffInMonths($now);
        }
        if ($period === 'yearly') {
            return $start->diffInYears($now);
        }
        return 0;
    }

    /** Anexa yield e total em um Saving EAGER carregado */
    private function attachComputed(Saving $s): Saving
    {
        $P = $this->moneyToFloat($s->current_amount);
        $r = $this->parseRate($s->interest_rate);
        $n = $this->elapsedPeriods($s->start_date, $s->rate_period);

        $yield = $this->compoundYield($P, $r, $n);
        $total = $P + $yield;

        // atributos "virtuais" no JSON
        $s->yield_amount = round($yield, 2);
        $s->total_amount = round($total, 2);
        $s->periods_elapsed = $n;

        return $s;
    }

    /** Deixa textos em upper para consistência visual */
    private function upperLabels(Saving $s): Saving
    {
        $s->name = strtoupper($s->name);
        if ($s->account) {
            $s->account->bank_name = strtoupper($s->account->bank_name);
        }
        return $s;
    }

    /* ======================== End Helpers ======================== */

    public function index()
    {
        $savings = Saving::with('account')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($s) {
                $this->attachComputed($s);
                return $this->upperLabels($s);
            });

        return response()->json($savings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'current_amount' => 'required',
            'interest_rate'  => 'nullable',
            'rate_period'    => 'nullable|in:monthly,yearly',
            'start_date'     => 'nullable|date',
            'notes'          => 'nullable|string',
            'account_id'     => [
                'nullable','uuid',
                Rule::exists('accounts','id')->where('user_id', Auth::id()),
            ],
        ]);

        // normalizações
        $data['current_amount'] = $this->moneyToFloat($data['current_amount']);
        if (isset($data['interest_rate'])) {
            // Armazenamos como foi enviado (percentual ou fração), mas aqui já
            // podemos normalizar para o "input" típico do usuário.
            // Se preferir persistir sempre em fração, use parseRate() aqui:
            // $data['interest_rate'] = $this->parseRate($data['interest_rate']);
            $data['interest_rate'] = $this->moneyToFloat($data['interest_rate']);
        }

        $data['start_date'] = !empty($data['start_date']) ? substr($data['start_date'], 0, 10) : null;
        $data['purchase_value'] = $data['purchase_value'] ?? $data['current_amount'];
        $data['user_id'] = Auth::id();

        $saving = null;

        DB::transaction(function () use (&$saving, $data) {
            // Debitar apenas se houver conta vinculada
            if (!empty($data['account_id'])) {
                $account = Account::lockForUpdate()
                    ->where('user_id', Auth::id())
                    ->find($data['account_id']);

                if (!$account) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Conta não encontrada para este usuário.'
                    ]);
                }

                if ($account->current_balance < $data['current_amount']) {
                    throw ValidationException::withMessages([
                        'current_amount' => 'Saldo insuficiente na conta selecionada.'
                    ]);
                }

                $account->current_balance -= $data['current_amount'];
                $account->save();
            }

            $saving = Saving::create($data);
        });

        $saving->load('account');
        $this->upperLabels($saving);
        $this->attachComputed($saving);

        return response()->json($saving, 201);
    }

    public function show($id)
    {
        $saving = Saving::with('account')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $this->upperLabels($saving);
        $this->attachComputed($saving);

        return response()->json($saving);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'current_amount' => 'sometimes',
            'interest_rate'  => 'sometimes|nullable',
            'rate_period'    => 'sometimes|in:monthly,yearly|nullable',
            'start_date'     => 'sometimes|date|nullable',
            'notes'          => 'sometimes|string|nullable',
            'account_id'     => [
                'sometimes','nullable','uuid',
                Rule::exists('accounts','id')->where('user_id', Auth::id()),
            ],
        ]);

        // normalizações (apenas se vierem no request)
        if (array_key_exists('current_amount', $data)) {
            $data['current_amount'] = $this->moneyToFloat($data['current_amount']);
        }
        if (array_key_exists('interest_rate', $data)) {
            $data['interest_rate'] = $data['interest_rate'] === null
                ? null
                : $this->moneyToFloat($data['interest_rate']);
        }
        if (array_key_exists('start_date', $data) && $data['start_date']) {
            $data['start_date'] = substr($data['start_date'], 0, 10);
        }

        $saving = Saving::where('user_id', Auth::id())->findOrFail($id);

        DB::transaction(function () use ($saving, $data) {
            $oldAccount = $saving->account_id
                ? Account::lockForUpdate()->where('user_id', Auth::id())->find($saving->account_id)
                : null;

            $newAccount = array_key_exists('account_id', $data)
                ? ( $data['account_id']
                    ? Account::lockForUpdate()->where('user_id', Auth::id())->find($data['account_id'])
                    : null
                )
                : ($oldAccount ?: null);

            $oldAmount = $saving->current_amount;
            $newAmount = array_key_exists('current_amount', $data) ? $data['current_amount'] : $oldAmount;

            // Mudança de conta
            if ($newAccount && $oldAccount && $newAccount->id !== $oldAccount->id) {
                // devolve antigo
                if ($oldAccount) {
                    $oldAccount->current_balance += $oldAmount;
                    $oldAccount->save();
                }
                // debita novo
                if ($newAccount->current_balance < $newAmount) {
                    throw ValidationException::withMessages([
                        'current_amount' => 'Saldo insuficiente na nova conta.'
                    ]);
                }
                $newAccount->current_balance -= $newAmount;
                $newAccount->save();
            } elseif ($newAccount) {
                // Mesma conta: ajusta somente a diferença
                $delta = $newAmount - $oldAmount;
                if ($delta > 0) {
                    if ($newAccount->current_balance < $delta) {
                        throw ValidationException::withMessages([
                            'current_amount' => 'Saldo insuficiente para aumentar a aplicação.'
                        ]);
                    }
                    $newAccount->current_balance -= $delta;
                } elseif ($delta < 0) {
                    $newAccount->current_balance += abs($delta);
                }
                $newAccount->save();
            } else {
                // se não há conta nova nem antiga, apenas segue (cofrinho sem conta)
            }

            $saving->update($data);
        });

        $saving->load('account');
        $this->upperLabels($saving);
        $this->attachComputed($saving);

        return response()->json($saving);
    }

    public function destroy($id)
    {
        $saving = Saving::where('user_id', Auth::id())->findOrFail($id);

        DB::transaction(function () use ($saving) {
            if ($saving->account_id && $saving->current_amount > 0) {
                $account = Account::lockForUpdate()
                    ->where('user_id', Auth::id())
                    ->find($saving->account_id);
                if ($account) {
                    $account->current_balance += $saving->current_amount;
                    $account->save();
                }
            }
            $saving->delete();
        });

        return response()->json(null, 204);
    }
}
