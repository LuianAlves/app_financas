<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChartController extends Controller
{
    public function pie(Request $req)
    {
        $userId = Auth::id(); // se usa owner/additionals, ajuste aqui
        [$start, $end] = $this->monthBounds($req->string('month')->toString());

        $level = $req->string('level', 'type')->toString();

        $type         = $req->string('type')->toString();                 // entrada|despesa|investimento
        $categoryId   = $req->string('category_id')->toString();          // UUID
        $pay          = $req->string('pay')->toString();                  // pix|card|money
        $cardType     = $req->string('card_type')->toString();            // credit|debit
        $instrumentId = $req->string('instrument_id')->toString();        // account_id ou card_id

        $breadcrumbs = [];

        // === NÍVEL 1: por tipo (entrada/despesa/investimento)
        if ($level === 'type') {
            $rows = Transaction::query()
                ->join('transaction_categories as c', 'c.id', '=', 'transactions.transaction_category_id')
                ->where('transactions.user_id', $userId)
                ->whereBetween('transactions.date', [$start, $end])
                ->selectRaw('c.type as label, SUM(transactions.amount) as value')
                ->groupBy('c.type')
                ->orderByDesc('value')
                ->get()
                ->map(function ($r) {
                    $color = [
                        'entrada'      => '#10b981',  // emerald-500
                        'despesa'      => '#ef4444',  // red-500
                        'investimento' => '#3b82f6',  // blue-500
                    ][$r->label] ?? '#18dec7';

                    return [
                        'id'    => Str::uuid()->toString(),
                        'label' => ucfirst($r->label),
                        'value' => (float)$r->value,
                        'color' => $color,
                        'next'  => ['level' => 'category', 'params' => ['type' => $r->label]],
                    ];
                });

            return response()->json([
                'level'       => 'type',
                'title'       => 'Tipos',
                'breadcrumbs' => $breadcrumbs,
                'items'       => $rows,
                'total'       => $rows->sum('value'),
            ]);
        }

        // === NÍVEL 2: categorias dentro do tipo
        if ($level === 'category' && $type) {
            $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];

            $rows = Transaction::query()
                ->join('transaction_categories as c', 'c.id', '=', 'transactions.transaction_category_id')
                ->where('transactions.user_id', $userId)
                ->where('c.type', $type)
                ->whereBetween('transactions.date', [$start, $end])
                ->selectRaw('c.id, c.name as label, COALESCE(c.color, "#18dec7") as color, SUM(transactions.amount) as value')
                ->groupBy('c.id', 'c.name', 'c.color')
                ->orderByDesc('value')
                ->get()
                ->map(function ($r) use ($type) {
                    return [
                        'id'    => $r->id,
                        'label' => $r->label,
                        'value' => (float)$r->value,
                        'color' => $r->color,
                        'next'  => ['level' => 'pay', 'params' => ['type' => $type, 'category_id' => $r->id]],
                    ];
                });

            return response()->json([
                'level'       => 'category',
                'title'       => 'Categorias (' . ucfirst($type) . ')',
                'breadcrumbs' => $breadcrumbs,
                'items'       => $rows,
                'total'       => $rows->sum('value'),
            ]);
        }

        // === NÍVEL 3: tipo de pagamento dentro da categoria (pix/card/money)
        if ($level === 'pay' && $categoryId) {
            $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
            $breadcrumbs[] = ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]];

            $rows = Transaction::query()
                ->where('transactions.user_id', $userId)
                ->where('transactions.transaction_category_id', $categoryId)
                ->whereBetween('transactions.date', [$start, $end])
                ->selectRaw('transactions.type as label, SUM(transactions.amount) as value')
                ->groupBy('transactions.type')
                ->orderByDesc('value')
                ->get()
                ->map(function ($r) use ($type, $categoryId) {
                    $label = $r->label;
                    $nice  = ['pix' => 'PIX', 'card' => 'Cartão', 'money' => 'Dinheiro'][$label] ?? strtoupper($label);
                    return [
                        'id'    => $label,
                        'label' => $nice,
                        'value' => (float)$r->value,
                        'color' => $label === 'card' ? '#6366f1' : ($label === 'pix' ? '#22c55e' : '#f59e0b'),
                        'next'  => [
                            'level'  => $label === 'card' ? 'card_type' : 'instrument',
                            'params' => ['type' => $type, 'category_id' => $categoryId, 'pay' => $label],
                        ],
                    ];
                });

            return response()->json([
                'level'       => 'pay',
                'title'       => 'Forma de pagamento',
                'breadcrumbs' => $breadcrumbs,
                'items'       => $rows,
                'total'       => $rows->sum('value'),
            ]);
        }

        // === NÍVEL 4a: cartão -> crédito x débito
        if ($level === 'card_type' && $categoryId) {
            $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
            $breadcrumbs[] = ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]];
            $breadcrumbs[] = ['label' => 'Cartão', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]];

            $rows = Transaction::query()
                ->where('transactions.user_id', $userId)
                ->where('transactions.transaction_category_id', $categoryId)
                ->where('transactions.type', 'card')
                ->whereBetween('transactions.date', [$start, $end])
                ->selectRaw('transactions.type_card as label, SUM(transactions.amount) as value')
                ->groupBy('transactions.type_card')
                ->orderByDesc('value')
                ->get()
                ->map(function ($r) use ($type, $categoryId) {
                    $lbl = $r->label ?: 'desconhecido';
                    $nice = ['credit' => 'Crédito', 'debit' => 'Débito'][$lbl] ?? ucfirst($lbl);
                    return [
                        'id'    => $lbl,
                        'label' => $nice,
                        'value' => (float)$r->value,
                        'color' => $lbl === 'credit' ? '#0ea5e9' : '#22d3ee',
                        'next'  => [
                            'level'  => 'instrument',
                            'params' => ['type' => $type, 'category_id' => $categoryId, 'pay' => 'card', 'card_type' => $lbl],
                        ],
                    ];
                });

            return response()->json([
                'level'       => 'card_type',
                'title'       => 'Crédito x Débito',
                'breadcrumbs' => $breadcrumbs,
                'items'       => $rows,
                'total'       => $rows->sum('value'),
            ]);
        }

        // === NÍVEL 4b/5: instrumento (qual conta/cartão)
        if ($level === 'instrument') {
            $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
            if ($type)        $breadcrumbs[] = ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]];
            if ($categoryId)  $breadcrumbs[] = ['label' => 'Pagamento', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]];

            if ($pay === 'card') {
                $q = Transaction::query()
                    ->join('cards as k', 'k.id', '=', 'transactions.card_id')
                    ->where('transactions.user_id', $userId)
                    ->when($categoryId, fn($qq) => $qq->where('transactions.transaction_category_id', $categoryId))
                    ->where('transactions.type', 'card')
                    ->when($cardType, fn($qq) => $qq->where('transactions.type_card', $cardType))
                    ->whereBetween('transactions.date', [$start, $end])
                    ->selectRaw('k.id, k.name as label, SUM(transactions.amount) as value')
                    ->groupBy('k.id', 'k.name')
                    ->orderByDesc('value')
                    ->get();

                $items = $q->map(fn($r) => [
                    'id'    => $r->id,
                    'label' => $r->label,
                    'value' => (float)$r->value,
                    'color' => '#a78bfa',
                    'next'  => null, // último nível
                ]);

                return response()->json([
                    'level'       => 'instrument',
                    'title'       => 'Cartões',
                    'breadcrumbs' => $breadcrumbs,
                    'items'       => $items,
                    'total'       => $items->sum('value'),
                ]);
            } else {
                $q = Transaction::query()
                    ->join('accounts as a', 'a.id', '=', 'transactions.account_id')
                    ->where('transactions.user_id', $userId)
                    ->when($categoryId, fn($qq) => $qq->where('transactions.transaction_category_id', $categoryId))
                    ->where('transactions.type', $pay) // pix ou money
                    ->whereBetween('transactions.date', [$start, $end])
                    ->selectRaw('a.id, a.name as label, SUM(transactions.amount) as value')
                    ->groupBy('a.id', 'a.name')
                    ->orderByDesc('value')
                    ->get();

                $items = $q->map(fn($r) => [
                    'id'    => $r->id,
                    'label' => $r->label,
                    'value' => (float)$r->value,
                    'color' => '#93c5fd',
                    'next'  => null,
                ]);

                return response()->json([
                    'level'       => 'instrument',
                    'title'       => 'Contas',
                    'breadcrumbs' => $breadcrumbs,
                    'items'       => $items,
                    'total'       => $items->sum('value'),
                ]);
            }
        }

        return response()->json(['items' => [], 'level' => $level, 'breadcrumbs' => []]);
    }

    private function monthBounds(?string $ym): array
    {
        $m = $ym && preg_match('/^\d{4}-\d{2}$/', $ym) ? Carbon::createFromFormat('Y-m', $ym) : now();
        $start = $m->copy()->startOfMonth()->toDateString();
        $end   = $m->copy()->endOfMonth()->toDateString();
        return [$start, $end];
    }
}
