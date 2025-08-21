<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Saving;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SavingController extends Controller
{
    /** Normaliza "1.234,56" ou "1234.56" para float 1234.56 */
    private function norm($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_string($v)) {
            $v = str_replace([' ', '.'], '', $v); // remove separador de milhar
            $v = str_replace(',', '.', $v);       // vírgula -> ponto
        }
        return (float) $v;
    }

    public function index()
    {
        $savings = Saving::with('account')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        $savings->each(function ($saving) {
            $saving->name = strtoupper($saving->name);
            if ($saving->account) {
                $saving->account->bank_name = strtoupper($saving->account->bank_name);
            }
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
        $data['current_amount'] = $this->norm($data['current_amount']);
        if (array_key_exists('purchase_value', $data)) {
            $data['purchase_value'] = $this->norm($data['purchase_value']);
        }
        if (array_key_exists('interest_rate', $data)) {
            $data['interest_rate'] = $this->norm($data['interest_rate']);
        }
        $data['start_date'] = !empty($data['start_date']) ? substr($data['start_date'], 0, 10) : null;

        // se não vier purchase_value, usar o mesmo do current_amount
        if (!isset($data['purchase_value'])) {
            $data['purchase_value'] = $data['current_amount'];
        }

        $data['user_id'] = Auth::id();

        $saving = null;

        DB::transaction(function () use (&$saving, $data) {
            /** @var Account $account */
            $account = Account::lockForUpdate()->find($data['account_id']);

            if ($account->current_balance < $data['current_amount']) {
                throw ValidationException::withMessages([
                    'current_amount' => 'Saldo insuficiente na conta selecionada.'
                ]);
            }

            // Debita o valor aplicado
            $account->current_balance -= $data['current_amount'];
            $account->save();

            $saving = Saving::create($data);
        });

        $saving->load('account');
        $saving->name = strtoupper($saving->name);
        if ($saving->account) {
            $saving->account->bank_name = strtoupper($saving->account->bank_name);
        }

        return response()->json($saving, 201);
    }

    public function show(Saving $saving)
    {
        $this->authorize('view', $saving);
        return response()->json($saving->load('account'));
    }

    public function update(Request $request, Saving $saving)
    {
        $this->authorize('update', $saving);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'current_amount' => 'sometimes',
            'interest_rate'  => 'sometimes|nullable',
            'rate_period'    => 'sometimes|in:monthly,yearly|nullable',
            'start_date'     => 'sometimes|date|nullable',
            'notes'          => 'sometimes|string|nullable',
            'account_id'     => [
                'sometimes','uuid',
                Rule::exists('accounts','id')->where('user_id', Auth::id()),
            ],
        ]);

        // normalizações dos campos presentes
        foreach (['current_amount','purchase_value','interest_rate'] as $f) {
            if (array_key_exists($f, $data)) {
                $data[$f] = $this->norm($data[$f]);
            }
        }
        if (array_key_exists('start_date', $data) && $data['start_date']) {
            $data['start_date'] = substr($data['start_date'], 0, 10);
        }

        DB::transaction(function () use ($saving, $data) {
            $oldAccount = $saving->account_id ? Account::lockForUpdate()->find($saving->account_id) : null;
            $newAccount = array_key_exists('account_id', $data)
                ? Account::lockForUpdate()->find($data['account_id'])
                : ($oldAccount ?: null);

            $oldAmount = $saving->current_amount;
            $newAmount = array_key_exists('current_amount', $data) ? $data['current_amount'] : $oldAmount;

            // Mudança de conta
            if ($newAccount && $oldAccount && $newAccount->id !== $oldAccount->id) {
                if ($oldAccount) {
                    $oldAccount->current_balance += $oldAmount;
                    $oldAccount->save();
                }
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
            }

            $saving->update($data);
        });

        $saving->load('account');
        $saving->name = strtoupper($saving->name);
        if ($saving->account) {
            $saving->account->bank_name = strtoupper($saving->account->bank_name);
        }

        return response()->json($saving);
    }

    public function destroy(Saving $saving)
    {
        $this->authorize('delete', $saving);

        DB::transaction(function () use ($saving) {
            if ($saving->account_id && $saving->current_amount > 0) {
                $account = Account::lockForUpdate()->find($saving->account_id);
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
