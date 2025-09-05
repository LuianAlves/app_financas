<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\AdditionalUser;
use App\Models\Card;
use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function index()
    {
        $accounts = $this->account->with('savings')->get();

        $accounts->each(function ($account) {

            if (isset($account->savings[0]) && isset($account->savings[0]->current_amount)) {
                $account->total = $account->savings[0]->current_amount + $account->current_balance;

                $account->current_balance = brlPrice($account->current_balance);
                $account->savings[0]->current_amount = brlPrice($account->savings[0]->current_amount);
                $account->saving_amount = $account->savings[0]->current_amount;

                $account->total = brlPrice($account->total);
                $account->bank_name = strtoupper($account->bank_name);
            } else {
                $account->total = brlPrice($account->current_balance);

                $account->current_balance = brlPrice($account->current_balance);
                $account->saving_amount = brlPrice(0);
                $account->bank_name = strtoupper($account->bank_name);
            }

        });

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $account = $this->account->create([
            'user_id' => Auth::id(),
            'bank_name' => $request->bank_name,
            'current_balance' => $request->current_balance,
            'type' => $request->type,
            'created_at' => Carbon::now()
        ]);

        return response()->json([
            'bank_name' => $account->bank_name,
            'current_balance' => $account->current_balance,
            'account_type' => $account->account_type,
        ]);
    }

    public function show($id)
    {
        $account = $this->account->with('savings')->find($id);

        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'bank_name' => 'sometimes|string|max:255',
            'current_balance' => 'sometimes|numeric',
            'type' => 'sometimes|string'
        ]);

        $account = $this->account->find($id);

        $account->update($data);

        return response()->json($account);
    }

    public function destroy($id)
    {
        $account = $this->account->with('savings')->find($id);

        $cards = Card::where('account_id', $account->id)->get();

        if($cards) {
            $cards->each(function ($card) {
                $card->delete();
            });
        }

        $savings = Saving::where('account_id', $account->id)->get();

        if($savings) {
            $savings->each(function ($saving) {
                $saving->delete();
            });
        }

        $account->delete();

        return response()->json(null, 204);
    }

    public function transfer(Request $request)
    {
        // dono do grupo (se o logado for adicional, retorna o owner)
        $ownerId = AdditionalUser::ownerIdFor(); // ou ownerIdFor(Auth::id())
        $userIds = AdditionalUser::query()
            ->where('user_id', $ownerId)
            ->pluck('linked_user_id')
            ->push($ownerId)
            ->unique()
            ->values()
            ->all(); // array puro para whereIn/Rule::exists

        $data = $request->validate([
            'from_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(function ($q) use ($userIds) {
                    $q->whereIn('user_id', $userIds);
                }),
            ],
            'to_id' => [
                'required',
                'different:from_id',
                Rule::exists('accounts', 'id')->where(function ($q) use ($userIds) {
                    $q->whereIn('user_id', $userIds);
                }),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data, $userIds) {
            // trava linhas e garante que são do mesmo grupo (owner + adicionais)
            $from = Account::whereKey($data['from_id'])
                ->whereIn('user_id', $userIds)
                ->lockForUpdate()
                ->firstOrFail();

            $to = Account::whereKey($data['to_id'])
                ->whereIn('user_id', $userIds)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = round((float)$data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Valor inválido.']);
            }

            if ((float)$from->current_balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'Saldo insuficiente na conta de origem.']);
            }

            // atualiza saldos (2 casas)
            $from->current_balance = round(((float)$from->current_balance) - $amount, 2);
            $to->current_balance   = round(((float)$to->current_balance)   + $amount, 2);

            $from->save();
            $to->save();

            return response()->json([
                'ok' => true,
                'from' => [
                    'id' => $from->id,
                    'bank_name' => $from->bank_name,
                    'current_balance' => $from->current_balance,
                ],
                'to' => [
                    'id' => $to->id,
                    'bank_name' => $to->bank_name,
                    'current_balance' => $to->current_balance,
                ],
            ]);
        });
    }
}
