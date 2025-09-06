<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AdditionalUser;

use App\Models\Transaction;
use App\Models\Recurrent;
use App\Models\CustomItemRecurrents;
use App\Models\PaymentTransaction;
use App\Models\Account;
use App\Models\Card;

class ChartController extends Controller
{
    public function pie(Request $req)
    {
        $userId = Auth::id();
        $ownerId = AdditionalUser::ownerIdFor();
        $userIds = AdditionalUser::query()
            ->where('user_id', $ownerId)
            ->pluck('linked_user_id')
            ->push($ownerId)
            ->unique()
            ->values();

        [$start, $end] = $this->monthBounds($req->string('month')->toString());
        $monthNum = Carbon::parse($start)->format('m');
        $yearNum = Carbon::parse($start)->format('Y');

        $ym = $req->string('month')->toString();
        $currentMonth = $ym && preg_match('/^\d{4}-\d{2}$/', $ym) ? $ym : now()->format('Y-m');

        $mode = $req->string('mode', 'tx')->toString(); // tx | invoice
        $level = $req->string('level', $mode === 'invoice' ? 'invoice_cards' : 'type')->toString();

        // params comuns
        $type = $req->string('type')->toString();
        $categoryId = $req->string('category_id')->toString();
        $pay = $req->string('pay')->toString();
        $cardType = $req->string('card_type')->toString();
        $invoiceId = $req->string('invoice_id')->toString();

        $today = Carbon::today();
        $rangeStart = Carbon::parse($start);
        $rangeEnd = Carbon::parse($end);
        $isProjection = $rangeEnd->gte($today); // mês corrente ou futuro
        if ($isProjection && $today->betweenIncluded($rangeStart, $rangeEnd)) {
            $rangeStart = $today->copy()->startOfDay(); // projeção: hoje→fim do mês alvo
        }

        // =======================
        // MODO: TRANSAÇÕES
        // =======================
        if ($mode === 'tx') {
            $breadcrumbs = [];


            if ($level === 'type') {

                if ($isProjection) {
                    $events = $this->projectEventsForRange($userIds, $rangeStart, $rangeEnd, $currentMonth);

                    $sum = ['entrada' => 0.0, 'despesa' => 0.0, 'investimento' => 0.0];
                    foreach ($events as $e) {
                        // ignorar "payment" (quitação) — só lançamentos previstos e faturas
                        if (!in_array($e['type'], ['entrada', 'despesa', 'investimento'], true)) continue;
                        $sum[$e['type']] += abs((float)$e['amount']);
                    }

                    $rows = collect([
                        ['label' => 'Entrada', 'key' => 'entrada', 'color' => '#a6e3a1'],
                        ['label' => 'Despesa', 'key' => 'despesa', 'color' => '#f38ba8'],
                        ['label' => 'Investimento', 'key' => 'investimento', 'color' => '#89b4fa'],
                    ])->map(fn($r) => [
                        'id' => $r['key'],
                        'label' => $r['label'],
                        'value' => round($sum[$r['key']] ?? 0, 2),
                        'color' => $r['color'],
                        'next' => ['level' => 'category', 'params' => ['type' => $r['key']]],
                    ])->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'type', 'title' => 'Tipos (projeção)',
                        'breadcrumbs' => [], 'items' => $rows->values(), 'total' => $rows->sum('value'),
                    ]);
                } else {
                    // 2.1 Transações do mês (despesa sem 'card')
                    $txBase = DB::table('transactions as t')
                        ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                        ->whereIn('t.user_id', $userIds)
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->whereRaw("NOT (c.type = 'despesa' AND t.type = 'card')")
                        ->selectRaw('c.type as label, SUM(t.amount) as value')
                        ->groupBy('c.type')
                        ->pluck('value', 'label'); // ['entrada'=>..., 'despesa'=>..., 'investimento'=>...]

                    // 2.2 Pagamentos do mês (para transações que NÃO caem no mês corrente)
                    $payAdd = DB::table('payment_transactions as p')
                        ->join('transactions as t', 't.id', '=', 'p.transaction_id')
                        ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                        ->whereIn('t.user_id', $userIds)
                        ->whereBetween('p.payment_date', [$start, $end])   // <<< troquei
                        ->whereRaw("NOT (c.type = 'despesa' AND t.type = 'card')")
                        ->where(function ($q) use ($start, $end) {
                            $q->whereNull('t.date')
                                ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end]);
                        })
                        ->selectRaw('c.type as label, SUM(p.amount) as value')
                        ->groupBy('c.type')
                        ->pluck('value', 'label');

                    $invItemsDesp = DB::table('invoice_items as it')
                        ->join('invoices as i', 'i.id', '=', 'it.invoice_id')
                        ->join('transaction_categories as c', 'c.id', '=', 'it.transaction_category_id')
                        ->whereIn('i.user_id', $userIds)
                        ->where('i.current_month', $currentMonth)     // << AQUI
                        ->where('c.type', 'despesa')
                        ->selectRaw('SUM(it.amount) as total')->value('total') ?? 0;

                    // 2.3b Pagamentos de fatura no mês (só se a fatura não tiver itens – evita duplicar)
                    $invPaysDesp = (float)DB::table('invoice_payments as ip')
                        ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
                        ->whereIn('i.user_id', $userIds)
                        ->where('i.current_month', $currentMonth)     // << AQUI
                        ->whereBetween('ip.paid_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                        ->whereNotExists(function ($q) {
                            $q->select(DB::raw(1))
                                ->from('invoice_items as it')
                                ->whereColumn('it.invoice_id', 'i.id'); // basta existir item em qualquer data
                        })
                        ->selectRaw('COALESCE(SUM(ip.amount),0) as total')->value('total');

                    // 2.4 Consolidado
                    $sum = ['entrada' => 0, 'despesa' => 0, 'investimento' => 0];
                    foreach ($txBase as $k => $v) $sum[$k] += (float)$v;
                    foreach ($payAdd as $k => $v) $sum[$k] += (float)$v;
                    $sum['despesa'] += ($invItemsDesp + $invPaysDesp);

                    $rows = collect([
                        ['label' => 'Entrada', 'key' => 'entrada', 'color' => '#a6e3a1'],
                        ['label' => 'Despesa', 'key' => 'despesa', 'color' => '#f38ba8'],
                        ['label' => 'Investimento', 'key' => 'investimento', 'color' => '#89b4fa'],
                    ])->map(fn($r) => [
                        'id' => Str::uuid()->toString(),
                        'label' => $r['label'],
                        'value' => round($sum[$r['key']] ?? 0, 2),
                        'color' => $r['color'],
                        'next' => ['level' => 'category', 'params' => ['type' => strtolower($r['key'])]],
                    ]);

                    $rows = $rows->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'type', 'title' => 'Tipos',
                        'breadcrumbs' => [], 'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                }
            }

            if ($level === 'category' && $type) {
                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];

                if ($isProjection) {
                    $events = $this->projectEventsForRange($userIds, $rangeStart, $rangeEnd, $currentMonth);

                    // agrupa por categoria só do tipo solicitado
                    $byCat = [];
                    foreach ($events as $e) {
                        if ($e['type'] !== $type) continue;
                        $cid = $e['category_id'] ?? '__none__';
                        if (!isset($byCat[$cid])) {
                            $byCat[$cid] = ['label' => $e['category_name'] ?? '—', 'color' => $e['color'] ?? '#94a3b8', 'value' => 0.0];
                        }
                        $byCat[$cid]['value'] += abs((float)$e['amount']);
                    }

                    // DESPESA: injeta "Faturas" (sintético) do mês alvo (não pagas)
                    if ($type === 'despesa') {
                        $invTotal = (float)DB::table('invoices as i')
                            ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'i.id')
                            ->whereIn('i.user_id', $userIds)
                            ->where('i.current_month', $currentMonth)
                            ->where('i.paid', false)
                            ->selectRaw('COALESCE(SUM(it.amount),0) as total')->value('total');

                        if ($invTotal > 0) {
                            $byCat = ['__INVOICES__' => [
                                    'label' => 'Faturas',
                                    'color' => '#8b5cf6',
                                    'value' => round($invTotal, 2)
                                ]] + $byCat;
                        }
                    }

                    $rows = collect($byCat)
                        ->map(fn($v, $cid) => [
                            'id' => $cid,
                            'label' => $v['label'],
                            'value' => round($v['value'], 2),
                            'color' => $v['color'],
                            'next' => $cid === '__INVOICES__'
                                ? ['level' => 'invoice_cards_tx', 'params' => []]
                                : ['level' => 'pay', 'params' => ['type' => $type, 'category_id' => $cid]],
                        ])
                        ->sortByDesc('value')
                        ->values()
                        ->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'category', 'title' => 'Categorias (projeção • ' . ucfirst($type) . ')',
                        'breadcrumbs' => [['label' => 'Tipos', 'level' => 'type', 'params' => []]],
                        'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                } else {
                    if ($type === 'despesa') {
                        // 3.1 Transações do mês (sem 'card')
                        $txCat = DB::table('transactions as t')
                            ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                            ->whereIn('t.user_id', $userIds)
                            ->where('c.type', 'despesa')
                            ->whereRaw("t.type <> 'card'") // <<< faltava isso
                            ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                            ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(t.amount) as value')
                            ->groupBy('c.id', 'c.name', 'c.color')
                            ->get()->keyBy('id');

                        $payCat = DB::table('payment_transactions as p')
                            ->join('transactions as t', 't.id', '=', 'p.transaction_id')
                            ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                            ->whereIn('t.user_id', $userIds)
                            ->where('c.type', 'despesa')
                            ->whereBetween('p.payment_date', [$start, $end])   // <<< troquei
                            ->where(function ($q) use ($start, $end) {
                                $q->whereNull('t.date')
                                    ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end]);
                            })
                            ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(p.amount) as value')
                            ->groupBy('c.id', 'c.name', 'c.color')
                            ->get()->keyBy('id');

                        // 3.3 Total de faturas do mês (para a categoria sintética)
                        $invTotal = DB::table('invoice_items as it')
                            ->join('invoices as i', 'i.id', '=', 'it.invoice_id')
                            ->whereIn('i.user_id', $userIds)
                            ->where('i.current_month', $currentMonth)     // << AQUI
                            ->selectRaw('SUM(it.amount) as total')->value('total') ?? 0;

// 3.4 Merge TX + PAY e depois injeta a categoria "Faturas"
                        $ids = collect($txCat->keys())->merge($payCat->keys())->unique();

                        $rows = $ids->map(function ($id) use ($txCat, $payCat) {
                            $name = $txCat[$id]->name ?? $payCat[$id]->name ?? '—';
                            $color = $txCat[$id]->color ?? $payCat[$id]->color ?? '#18dec7';
                            $value = (float)($txCat[$id]->value ?? 0) + (float)($payCat[$id]->value ?? 0);
                            return [
                                'id' => $id,
                                'label' => $name,
                                'value' => round($value, 2),
                                'color' => $color,
                                'next' => ['level' => 'pay', 'params' => ['type' => 'despesa', 'category_id' => $id]],
                            ];
                        })->values();

                        // Injeta "Faturas" (categoria sintética) no topo, se houver valor
                        if ($invTotal > 0) {
                            $rows->prepend([
                                'id' => '__INVOICES__',
                                'label' => 'Faturas',
                                'value' => round($invTotal, 2),
                                'color' => '#8b5cf6',
                                'next' => ['level' => 'invoice_cards_tx', 'params' => []],
                            ]);
                        }
                        $rows = $rows->sortByDesc('value')->values();
                        $rows = $rows->map(fn($r) => self::paint($r));

                        return response()->json([
                            'mode' => 'tx', 'level' => 'category', 'title' => 'Categorias (Despesa)',
                            'breadcrumbs' => $breadcrumbs, 'items' => $rows, 'total' => $rows->sum('value'),
                        ]);
                    }

                    // === entrada / investimento (corrigido: soma payments do mês)
                    $txCat = DB::table('transactions as t')
                        ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                        ->whereIn('t.user_id', $userIds)
                        ->where('c.type', $type) // 'entrada' ou 'investimento'
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(t.amount) as tvalue')
                        ->groupBy('c.id', 'c.name', 'c.color')
                        ->get()->keyBy('id');

                    $monthNum = \Carbon\Carbon::parse($start)->format('m');
                    $monthNo0 = ltrim($monthNum, '0'); // caso tenha salvo "9" em vez de "09"

                    $payCat = DB::table('payment_transactions as p')
                        ->join('transactions as t', 't.id', '=', 'p.transaction_id')
                        ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                        ->whereIn('t.user_id', $userIds)
                        ->where('c.type', $type) // entrada ou investimento
                        ->whereBetween('p.payment_date', [$start, $end])   // <<< troquei
                        ->where(function ($q) use ($start, $end) {
                            $q->whereNull('t.date')
                                ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end]);
                        })
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(p.amount) as pvalue')
                        ->groupBy('c.id', 'c.name', 'c.color')
                        ->get()->keyBy('id');

                    $ids = collect($txCat->keys())->merge($payCat->keys())->unique();

                    $rows = $ids->map(function ($id) use ($txCat, $payCat, $type) {
                        $name = $txCat[$id]->name ?? $payCat[$id]->name ?? '—';
                        $color = $txCat[$id]->color ?? $payCat[$id]->color ?? '#18dec7';
                        $value = (float)($txCat[$id]->tvalue ?? 0) + (float)($payCat[$id]->pvalue ?? 0);
                        return [
                            'id' => $id,
                            'label' => $name,
                            'value' => round($value, 2),
                            'color' => $color,
                            'next' => ['level' => 'pay', 'params' => ['type' => $type, 'category_id' => $id]],
                        ];
                    })->sortByDesc('value')->values();

                    $rows = $rows->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'category', 'title' => 'Categorias (' . ucfirst($type) . ')',
                        'breadcrumbs' => $breadcrumbs, 'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                }
            }

            if ($level === 'pay' && $categoryId) {
                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
                $breadcrumbs[] = ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]];

                if ($isProjection) {
                    $events = $this->projectEventsForRange($userIds, $rangeStart, $rangeEnd, $currentMonth);

                    $map = ['pix' => 0.0, 'money' => 0.0, 'card' => 0.0];
                    foreach ($events as $e) {
                        if (($e['category_id'] ?? null) != $categoryId || !in_array($e['type'], ['entrada', 'despesa', 'investimento'], true)) continue;
                        $pay = $e['pay'] ?: 'money';
                        if (!isset($map[$pay])) $map[$pay] = 0.0;
                        $map[$pay] += abs((float)$e['amount']);
                    }

                    $mapLabel = ['pix' => 'PIX', 'money' => 'Dinheiro', 'card' => 'Cartão'];
                    $mapColor = ['pix' => '#a6e3a1', 'money' => '#f9e2af', 'card' => '#89b4fa'];

                    $rows = collect($map)
                        ->filter(fn($v) => $v > 0)
                        ->map(fn($v, $k) => [
                            'id' => $k, 'label' => $mapLabel[$k] ?? strtoupper($k), 'value' => round($v, 2), 'color' => $mapColor[$k] ?? '#18dec7',
                            'next' => ['level' => $k === 'card' ? 'card_type' : 'instrument', 'params' => ['type' => $type, 'category_id' => $categoryId, 'pay' => $k]],
                        ])->values()->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'pay', 'title' => 'Forma de pagamento (projeção)',
                        'breadcrumbs' => [
                            ['label' => 'Tipos', 'level' => 'type', 'params' => []],
                            ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]],
                        ],
                        'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                } else {
                    // A) Transações do mês
                    $tx = DB::table('transactions as t')
                        ->whereIn('t.user_id', $userIds)
                        ->where('t.transaction_category_id', $categoryId)
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('t.type as label, SUM(t.amount) as value')
                        ->groupBy('t.type')
                        ->pluck('value', 'label'); // ['pix'=>..., 'money'=>..., 'card'=>...]

                    // B) Pagamentos do mês (transações fora do mês)
                    $pay = DB::table('payment_transactions as p')
                        ->join('transactions as t', 't.id', '=', 'p.transaction_id')
                        ->whereIn('t.user_id', $userIds)
                        ->where('t.transaction_category_id', $categoryId)
                        ->whereBetween('p.payment_date', [$start, $end])
                        ->where(function ($q) use ($start, $end) {
                            $q->whereNull('t.date')
                                ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end]);
                        })
                        ->selectRaw('t.type as label, SUM(p.amount) as value')
                        ->groupBy('t.type')
                        ->pluck('value', 'label');

                    // C) Consolida
                    $byType = ['pix' => 0, 'money' => 0, 'card' => 0];
                    foreach ($tx as $k => $v) {
                        $byType[$k] = ($byType[$k] ?? 0) + (float)$v;
                    }
                    foreach ($pay as $k => $v) {
                        $byType[$k] = ($byType[$k] ?? 0) + (float)$v;
                    }

                    // D) Monta itens
                    $mapLabel = ['pix' => 'PIX', 'money' => 'Dinheiro', 'card' => 'Cartão'];

                    $mapColor = [
                        'pix' => '#a6e3a1', // antes #22c55e
                        'money' => '#f9e2af', // amarelo suave (mais clean que #f59e0b)
                        'card' => '#89b4fa', // antes #6366f1
                    ];

                    $rows = collect($byType)
                        ->filter(fn($v) => $v > 0)
                        ->map(function ($v, $k) use ($mapLabel, $mapColor, $type, $categoryId) {
                            return [
                                'id' => $k,
                                'label' => $mapLabel[$k] ?? strtoupper($k),
                                'value' => (float)$v,
                                'color' => $mapColor[$k] ?? '#18dec7',
                                'next' => [
                                    'level' => $k === 'card' ? 'card_type' : 'instrument',
                                    'params' => ['type' => $type, 'category_id' => $categoryId, 'pay' => $k],
                                ],
                            ];
                        })->values();

                    $rows = $rows->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'pay', 'title' => 'Forma de pagamento',
                        'breadcrumbs' => $breadcrumbs, 'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                }
            }

            if ($level === 'card_type' && $categoryId) {
                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
                $breadcrumbs[] = ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]];
                $breadcrumbs[] = ['label' => 'Cartão', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]];

                if ($isProjection) {
                    $events = $this->projectEventsForRange($userIds, $rangeStart, $rangeEnd, $currentMonth);

                    $map = ['credit' => 0.0, 'debit' => 0.0, 'desconhecido' => 0.0];
                    foreach ($events as $e) {
                        if (($e['category_id'] ?? null) != $categoryId || $e['pay'] !== 'card') continue;
                        $k = $e['type_card'] ?: 'desconhecido';
                        if (!isset($map[$k])) $map[$k] = 0.0;
                        $map[$k] += abs((float)$e['amount']);
                    }

                    $rows = collect($map)->filter(fn($v) => $v > 0)->map(function ($v, $k) {
                        $nice = ['credit' => 'Crédito', 'debit' => 'Débito']['' . $k] ?? ucfirst($k);
                        return [
                            'id' => $k, 'label' => $nice, 'value' => round($v, 2),
                            'color' => $k === 'credit' ? '#89b4fa' : '#94e2d5',
                            'next' => ['level' => 'instrument', 'params' => ['pay' => 'card', 'card_type' => $k]],
                        ];
                    })->values()->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'card_type', 'title' => 'Crédito x Débito (projeção)',
                        'breadcrumbs' => [
                            ['label' => 'Tipos', 'level' => 'type', 'params' => []],
                            ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]],
                            ['label' => 'Cartão', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]],
                        ],
                        'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                } else {


                    $rows = DB::table('transactions as t')
                        ->whereIn('t.user_id', $userIds)
                        ->where('t.transaction_category_id', $categoryId)
                        ->where('t.type', 'card')
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('t.type_card as label, SUM(t.amount) as value')
                        ->groupBy('t.type_card')->orderByDesc('value')->get()
                        ->map(function ($r) use ($type, $categoryId) {
                            $lbl = $r->label ?: 'desconhecido';
                            $nice = ['credit' => 'Crédito', 'debit' => 'Débito'][$lbl] ?? ucfirst($lbl);
                            return [
                                'id' => $lbl, 'label' => $nice, 'value' => (float)$r->value,
                                'color' => $lbl === 'credit' ? '#89b4fa' : '#94e2d5',
                                'next' => [
                                    'level' => 'instrument',
                                    'params' => ['type' => $type, 'category_id' => $categoryId, 'pay' => 'card', 'card_type' => $lbl],
                                ],
                            ];
                        });

                    $rows = $rows->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'card_type', 'title' => 'Crédito x Débito',
                        'breadcrumbs' => $breadcrumbs, 'items' => $rows, 'total' => $rows->sum('value'),
                    ]);
                }
            }

            if ($level === 'instrument') {
                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
                if ($type) $breadcrumbs[] = ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]];
                if ($categoryId) $breadcrumbs[] = ['label' => 'Pagamento', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]];

                if ($isProjection) {
                    $events = $this->projectEventsForRange($userIds, $rangeStart, $rangeEnd, $currentMonth);

                    if ($pay === 'card') {
                        // agrupa por card_id
                        $map = [];
                        foreach ($events as $e) {
                            if ($e['pay'] !== 'card') continue;
                            if ($categoryId && ($e['category_id'] ?? null) != $categoryId) continue;
                            if ($cardType && ($e['type_card'] ?? null) !== $cardType) continue;
                            $cid = (string)($e['card_id'] ?? '_none');
                            if (!isset($map[$cid])) $map[$cid] = 0.0;
                            $map[$cid] += abs((float)$e['amount']);
                        }

                        $cardMeta = DB::table('cards')->whereIn('id', array_keys(array_filter($map, fn($v) => $v > 0)))
                            ->get(['id', 'cardholder_name', 'last_four_digits', 'color_card'])
                            ->keyBy('id');

                        $items = collect($map)->filter(fn($v) => $v > 0)->map(function ($v, $cid) use ($cardMeta) {
                            $c = $cardMeta->get($cid);
                            $label = $c ? ($c->cardholder_name . ' • ' . str_pad((string)$c->last_four_digits, 4, '0', STR_PAD_LEFT)) : '(Cartão)';
                            return ['id' => $cid, 'label' => $label, 'value' => round($v, 2), 'color' => $c->color_card ?? '#a78bfa', 'next' => null];
                        })->values()->map(fn($r) => self::paint($r));

                        return response()->json([
                            'mode' => 'tx', 'level' => 'instrument', 'title' => 'Cartões (projeção)',
                            'breadcrumbs' => [
                                ['label' => 'Tipos', 'level' => 'type', 'params' => []],
                                $type ? ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]] : null,
                                ['label' => 'Pagamento', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]],
                            ],
                            'items' => $items->values(), 'total' => $items->sum('value'),
                        ]);
                    }

                    // PIX/Dinheiro → por account_id
                    $map = [];
                    foreach ($events as $e) {
                        if ($e['pay'] !== $pay) continue;
                        if ($categoryId && ($e['category_id'] ?? null) != $categoryId) continue;
                        $aid = (string)($e['account_id'] ?? '_none');
                        if (!isset($map[$aid])) $map[$aid] = 0.0;
                        $map[$aid] += abs((float)$e['amount']);
                    }

                    $accMeta = DB::table('accounts')->whereIn('id', array_keys(array_filter($map, fn($v) => $v > 0)))
                        ->get(['id', 'bank_name'])->keyBy('id');

                    $items = collect($map)->filter(fn($v) => $v > 0)->map(function ($v, $aid) use ($accMeta) {
                        $a = $accMeta->get($aid);
                        $label = $a?->bank_name ?? '(Sem conta)';
                        return ['id' => $aid, 'label' => $label, 'value' => round($v, 2), 'color' => self::colorForKey($aid . $label), 'next' => null];
                    })->values()->map(fn($r) => self::paint($r));

                    return response()->json([
                        'mode' => 'tx', 'level' => 'instrument', 'title' => 'Contas (projeção)',
                        'breadcrumbs' => [
                            ['label' => 'Tipos', 'level' => 'type', 'params' => []],
                            $type ? ['label' => ucfirst($type), 'level' => 'category', 'params' => ['type' => $type]] : null,
                            ['label' => 'Pagamento', 'level' => 'pay', 'params' => ['type' => $type, 'category_id' => $categoryId]],
                        ],
                        'items' => $items->values(), 'total' => $items->sum('value'),
                    ]);
                } else {
                    if ($pay === 'card') {
                        $q = DB::table('transactions as t')
                            ->join('cards as k', 'k.id', '=', 't.card_id')
                            ->whereIn('t.user_id', $userIds)
                            ->when($categoryId, fn($qq) => $qq->where('t.transaction_category_id', $categoryId))
                            ->where('t.type', 'card')
                            ->when($cardType, fn($qq) => $qq->where('t.type_card', $cardType))
                            ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                            ->selectRaw('k.id, k.cardholder_name as label, SUM(t.amount) as value, COALESCE(k.color_card,"#a78bfa") as color')
                            ->groupBy('k.id', 'k.cardholder_name', 'k.color_card')
                            ->orderByDesc('value')->get();

                        $items = $q->map(fn($r) => [
                            'id' => $r->id, 'label' => $r->label, 'value' => (float)$r->value, 'color' => $r->color, 'next' => null
                        ]);

                        return response()->json([
                            'mode' => 'tx', 'level' => 'instrument', 'title' => 'Cartões',
                            'breadcrumbs' => $breadcrumbs, 'items' => $items, 'total' => $items->sum('value'),
                        ]);
                    } else {
                        // === PIX / Dinheiro: somar transações do mês + payments do mês (conta via transactions.account_id)
                        $txAcc = DB::table('transactions as t')
                            ->leftJoin('accounts as a', 'a.id', '=', 't.account_id') // LEFT
                            ->whereIn('t.user_id', $userIds)
                            ->when($categoryId, fn($qq) => $qq->where('t.transaction_category_id', $categoryId))
                            ->where('t.type', $pay) // 'pix' ou 'money'
                            ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                            ->selectRaw('IFNULL(a.id, "_none") as id, COALESCE(a.bank_name,"(Sem conta)") as label, SUM(t.amount) as value')
                            ->groupBy(DB::raw('IFNULL(a.id, "_none")'), DB::raw('COALESCE(a.bank_name,"(Sem conta)")'))
                            ->get()->keyBy('id');

                        $payAcc = DB::table('payment_transactions as p')
                            ->join('transactions as t', 't.id', '=', 'p.transaction_id')
                            ->leftJoin('accounts as a', 'a.id', '=', 't.account_id') // conta vem da transação paga
                            ->whereIn('t.user_id', $userIds)
                            ->when($categoryId, fn($qq) => $qq->where('t.transaction_category_id', $categoryId))
                            ->whereBetween('p.payment_date', [$start, $end])
                            ->where(function ($q) use ($start, $end) {
                                $q->whereNull('t.date')
                                    ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end]);
                            })
                            ->where('t.type', $pay) // garante mesmo tipo
                            ->selectRaw('IFNULL(a.id, "_none") as id, COALESCE(a.bank_name,"(Sem conta)") as label, SUM(p.amount) as value')
                            ->groupBy(DB::raw('IFNULL(a.id, "_none")'), DB::raw('COALESCE(a.bank_name,"(Sem conta)")'))
                            ->get()->keyBy('id');

                        $ids = collect($txAcc->keys())->merge($payAcc->keys())->unique();

                        $items = $ids->map(function ($id) use ($txAcc, $payAcc) {
                            $label = $txAcc[$id]->label ?? $payAcc[$id]->label ?? '(Sem conta)';
                            $value = (float)($txAcc[$id]->value ?? 0) + (float)($payAcc[$id]->value ?? 0);
                            $color = self::colorForKey((string)$id . $label);
                            return ['id' => $id, 'label' => $label, 'value' => $value, 'color' => $color, 'next' => null];
                        })->sortByDesc('value')->values();

                        $items = $items->map(fn($r) => self::paint($r));  // <<< não esqueça

                        return response()->json([
                            'mode' => 'tx', 'level' => 'instrument', 'title' => 'Contas',
                            'breadcrumbs' => $breadcrumbs, 'items' => $items, 'total' => $items->sum('value'),
                        ]);
                    }
                }
            }

            if ($level === 'invoice_cards_tx') {
                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
                $breadcrumbs[] = ['label' => 'Despesa', 'level' => 'category', 'params' => ['type' => 'despesa']];
                $breadcrumbs[] = ['label' => 'Faturas', 'level' => 'invoice_cards_tx', 'params' => []];

                $rows = DB::table('invoices as i')
                    ->join('invoice_items as it', 'it.invoice_id', '=', 'i.id')
                    ->join('cards as k', 'k.id', '=', 'i.card_id')
                    ->whereIn('i.user_id', $userIds)
                    ->where('i.current_month', $currentMonth)       // << AQUI
                    ->selectRaw('k.id, CONCAT(k.cardholder_name," • ",LPAD(COALESCE(k.last_four_digits,0),4,"0")) as label,
               COALESCE(k.color_card,"#a78bfa") as color, SUM(it.amount) as value')
                    ->groupBy('k.id', 'k.cardholder_name', 'k.last_four_digits', 'k.color_card')
                    ->orderByDesc('value')->get()
                    ->map(fn($r) => [
                        'id' => $r->id, 'label' => $r->label, 'value' => (float)$r->value, 'color' => $r->color,
                        'next' => ['level' => 'invoice_items_tx', 'params' => ['card_id' => $r->id]],
                    ]);

                $rows = $rows->map(fn($r) => self::paint($r));

                return response()->json([
                    'mode' => 'tx', 'level' => 'invoice_cards_tx', 'title' => 'Faturas por cartão',
                    'breadcrumbs' => $breadcrumbs, 'items' => $rows, 'total' => $rows->sum('value'),
                ]);
            }

            if ($level === 'invoice_items_tx') {
                $cardId = $req->string('card_id')->toString();

                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];
                $breadcrumbs[] = ['label' => 'Despesa', 'level' => 'category', 'params' => ['type' => 'despesa']];
                $breadcrumbs[] = ['label' => 'Faturas', 'level' => 'invoice_cards_tx', 'params' => []];

                $rows = DB::table('invoice_items as it')
                    ->join('invoices as i', 'i.id', '=', 'it.invoice_id')
                    ->join('transaction_categories as c', 'c.id', '=', 'it.transaction_category_id')
                    ->whereIn('i.user_id', $userIds)
                    ->where('i.card_id', $cardId)
                    ->where('i.current_month', $currentMonth)
                    ->selectRaw('
        it.id,
        COALESCE(it.title,c.name) as label,
        it.amount as value,
        it.date,
        COALESCE(c.color,"#94a3b8") as color
    ')
                    ->orderByDesc('it.date')->get()
                    ->map(fn($r) => [
                        'id' => $r->id,
                        'label' => $r->label . ' — ' . \Carbon\Carbon::parse($r->date)->format('d/m'),
                        'value' => (float)$r->value,
                        'color' => $r->color,  // << aqui também
                        'next' => null
                    ]);

                $rows = $rows->map(fn($r) => self::paint($r));

                return response()->json([
                    'mode' => 'tx', 'level' => 'invoice_items_tx', 'title' => 'Itens da fatura',
                    'breadcrumbs' => $breadcrumbs, 'items' => $rows, 'total' => $rows->sum('value'),
                ]);
            }
        }

        // =======================
        // MODO: FATURAS
        // =======================
        if ($mode === 'invoice') {
            // Nível 1: faturas por cartão no mês
            if ($level === 'invoice_cards') {
                $rows = DB::table('invoices as i')
                    ->leftJoin('invoice_items as it', 'it.invoice_id', '=', 'i.id')
                    ->join('cards as k', 'k.id', '=', 'i.card_id')
                    ->whereIn('i.user_id', $userIds)
                    ->whereBetween('it.date', [$start, $end]) // << FILTRO DO MÊS
                    ->selectRaw('i.id, k.id as card_id,
        CONCAT(k.cardholder_name, " • ", LPAD(COALESCE(k.last_four_digits,0),4,"0")) as label,
        COALESCE(k.color_card,"#a78bfa") as color,
        SUM(it.amount) as items_total,
        MAX(i.paid) as is_paid')
                    ->groupBy('i.id', 'k.id', 'k.cardholder_name', 'k.last_four_digits', 'k.color_card')
                    ->orderBy('label')->get()
                    ->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'label' => $r->label . ($r->is_paid ? ' (pago)' : ''),
                            'value' => (float)$r->items_total,   // << SOMENTE O TOTAL DO MÊS
                            'color' => $r->color,
                            'next' => ['level' => 'invoice_categories', 'params' => ['invoice_id' => $r->id]],
                        ];
                    });

                $rows = $rows->map(fn($r) => self::paint($r));

                return response()->json([
                    'mode' => 'invoice', 'level' => 'invoice_cards', 'title' => 'Faturas do mês',
                    'breadcrumbs' => [['label' => 'Faturas', 'level' => 'invoice_cards', 'params' => []]],
                    'items' => $rows, 'total' => $rows->sum('value'),
                ]);
            }

            // Nível 2: categorias dentro da fatura
            if ($level === 'invoice_categories' && $invoiceId) {
                $card = DB::table('invoices as i')
                    ->join('cards as k', 'k.id', '=', 'i.card_id')
                    ->where('i.id', $invoiceId)
                    ->selectRaw('CONCAT(k.cardholder_name," • ",LPAD(COALESCE(k.last_four_digits,0),4,"0")) as label')->first();

                $rows = DB::table('invoice_items as it')
                    ->join('transaction_categories as c', 'c.id', '=', 'it.transaction_category_id')
                    ->where('it.invoice_id', $invoiceId)
                    ->whereBetween('it.date', [$start, $end]) // << AQUI
                    ->selectRaw('c.id, c.name as label, COALESCE(c.color,"#18dec7") as color, SUM(it.amount) as value')
                    ->groupBy('c.id', 'c.name', 'c.color')
                    ->orderByDesc('value')->get()
                    ->map(fn($r) => [
                        'id' => $r->id, 'label' => $r->label, 'value' => (float)$r->value, 'color' => $r->color,
                        'next' => ['level' => 'invoice_items', 'params' => ['invoice_id' => $invoiceId, 'category_id' => $r->id]],
                    ]);

                $rows = $rows->map(fn($r) => self::paint($r));

                return response()->json([
                    'mode' => 'invoice', 'level' => 'invoice_categories', 'title' => 'Categorias da fatura',
                    'breadcrumbs' => [
                        ['label' => 'Faturas', 'level' => 'invoice_cards', 'params' => []],
                        ['label' => $card?->label ?? 'Cartão', 'level' => 'invoice_categories', 'params' => ['invoice_id' => $invoiceId]],
                    ],
                    'items' => $rows, 'total' => $rows->sum('value'),
                ]);
            }

            // Nível 3: itens da fatura (opcionalmente filtrados por categoria)
            if ($level === 'invoice_items' && $invoiceId) {
                $catName = $categoryId ? DB::table('transaction_categories')->where('id', $categoryId)->value('name') : null;

                $rows = DB::table('invoice_items as it')
                    ->join('transaction_categories as c', 'c.id', '=', 'it.transaction_category_id') // inner é ok (é not null)
                    ->where('it.invoice_id', $invoiceId)
                    ->when($categoryId, fn($q) => $q->where('it.transaction_category_id', $categoryId))
                    ->whereBetween('it.date', [$start, $end])
                    ->selectRaw('
        it.id,
        COALESCE(it.title,c.name) as label,
        it.amount as value,
        it.date,
        COALESCE(c.color,"#94a3b8") as color
    ')
                    ->orderByDesc('it.date')->get()
                    ->map(fn($r) => [
                        'id' => $r->id,
                        'label' => $r->label . ' — ' . Carbon::parse($r->date)->format('d/m'),
                        'value' => (float)$r->value,
                        'color' => $r->color,   // << usa a cor da categoria
                        'next' => null,
                    ]);

                $rows = $rows->map(fn($r) => self::paint($r));

                return response()->json([
                    'mode' => 'invoice', 'level' => 'invoice_items', 'title' => $catName ? "Itens • $catName" : 'Itens da fatura',
                    'breadcrumbs' => [
                        ['label' => 'Faturas', 'level' => 'invoice_cards', 'params' => []],
                        ['label' => 'Categorias', 'level' => 'invoice_categories', 'params' => ['invoice_id' => $invoiceId]],
                    ],
                    'items' => $rows, 'total' => $rows->sum('value'),
                ]);
            }
        }

        return response()->json(['mode' => $mode, 'level' => $level, 'items' => [], 'breadcrumbs' => [], 'total' => 0]);
    }

    private function monthBounds(?string $ym): array
    {
        $m = $ym && preg_match('/^\d{4}-\d{2}$/', $ym) ? Carbon::createFromFormat('Y-m', $ym) : now();
        $start = $m->copy()->startOfMonth()->toDateString();
        $end = $m->copy()->endOfMonth()->toDateString();
        return [$start, $end];
    }

    private static function withAlpha(string $hex, string $alpha = '82'): string
    {
        $hex = trim($hex);
        if ($hex === '') return '#94a3b8' . $alpha;        // fallback

        // aceita "#RGB", "#RRGGBB", "#RRGGBBAA" ou sem "#"
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        } elseif (strlen($hex) === 8) {
            $hex = substr($hex, 0, 6); // remove alpha antigo
        } elseif (strlen($hex) !== 6) {
            return '#' . $hex; // formatos não-hex: devolve como veio
        }
        return '#' . $hex . $alpha; // #RRGGBB1a
    }

    private static function paint(array $row): array
    {
        $base = $row['color'] ?? self::colorForKey(($row['id'] ?? $row['label'] ?? 'x'));
        $row['color'] = $base;              // mantém compat
        $row['border'] = $base;              // borda sólida
        $row['bg'] = self::withAlpha($base, '1a'); // fill com alpha
        return $row;
    }

    private static function colorForKey(string $key): string
    {
        $palette = [
            '#89b4fa', '#a6e3a1', '#f38ba8', '#94e2d5', '#f9e2af',
            '#fab387', '#cba6f7', '#f5c2e7', '#74c7ec', '#b4befe',
            '#8bd5ca', '#eed49f'
        ];
        $i = abs(crc32($key)) % count($palette);
        return $palette[$i];
    }

    private function paymentsIndex($userIds): array
    {
        $rows = DB::table('payment_transactions as pt')
            ->join('transactions as t','t.id','=','pt.transaction_id')
            ->whereIn('t.user_id', $userIds)
            ->get(['pt.transaction_id','pt.reference_year','pt.reference_month','pt.payment_date']);

        $idx = []; $byDate = [];
        foreach ($rows as $r) {
            if ($r->reference_year && $r->reference_month) {
                $ym = sprintf('%04d-%02d', (int)$r->reference_year, (int)$r->reference_month);
                $idx[$r->transaction_id][$ym] = true;
            }
            if (!empty($r->payment_date)) {
                $d = Carbon::parse($r->payment_date)->toDateString();
                $byDate[$r->transaction_id][$d] = true;
            }
        }
        $idx['_byDate'] = $byDate;
        return $idx;
    }

    private function firstAlignedDays(Carbon $start, Carbon $from, int $interval): Carbon
    {
        $s = $start->copy();
        if ($s->lt($from)) {
            $diff  = $s->diffInDays($from);
            $steps = intdiv($diff + $interval - 1, $interval);
            $s->addDays($steps * $interval);
        }
        return $s;
    }
    private function normalizeW(Carbon $d, bool $sat, bool $sun): Carbon
    {
        if (!$sat && $d->isSaturday()) $d->addDays(2);
        if (!$sun && $d->isSunday())   $d->addDay();
        return $d;
    }

    /**
     * Gera eventos previstos no intervalo (sem pagamentos), + faturas a vencer.
     * Retorna array de rows canônicos:
     * ['type','amount','category_id','category_name','color','pay','type_card','account_id','card_id','is_invoice']
     */
    private function projectEventsForRange($userIds, Carbon $rangeStart, Carbon $rangeEnd, string $currentMonth): array
    {
        $out  = [];
        $paid = $this->paymentsIndex($userIds);
        $monthStart = $rangeEnd->copy()->startOfMonth();

        // === ÚNICAS no intervalo (não pagas)
        $unique = DB::table('transactions as t')
            ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
            ->leftJoin('payment_transactions as pt','pt.transaction_id','=','t.id')
            ->whereIn('t.user_id', $userIds)
            ->where('t.recurrence_type','unique')
            ->whereBetween('t.date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->whereNull('pt.id')
            ->where(function ($q) {
                $q->where('t.type','!=','card')
                    ->orWhereNull('t.type')
                    ->orWhere(fn($qq)=>$qq->where('t.type','card')->where('t.type_card','!=','credit'));
            })
            ->where(function ($q) {
                $q->whereNull('t.title')
                    ->orWhereRaw('LOWER(t.title) NOT IN (?, ?, ?)', ['total fatura','fatura total','total da fatura']);
            })
            ->get([
                't.id','t.amount','t.type as pay','t.type_card','t.account_id','t.card_id',
                'c.id as category_id','c.name as category_name','c.type as cat_type','c.color'
            ]);
        foreach ($unique as $r) {
            $type = in_array($r->cat_type, ['entrada','despesa','investimento'], true) ? $r->cat_type : 'investimento';
            $out[] = [
                'type'         => $type,
                'amount'       => abs((float)$r->amount),
                'category_id'  => (string)$r->category_id,
                'category_name'=> $r->category_name,
                'color'        => $r->color,
                'pay'          => $r->pay,
                'type_card'    => $r->type_card,
                'account_id'   => $r->account_id,
                'card_id'      => $r->card_id,
                'is_invoice'   => false,
            ];
        }

        // === RECORRENTES monthly/yearly (sem itens custom)
        $recMY = DB::table('recurrents as r')
            ->join('transactions as t','t.id','=','r.transaction_id')
            ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
            ->whereIn('r.user_id', $userIds)
            ->whereIn('t.recurrence_type',['monthly','yearly'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('custom_item_recurrents as cir')
                    ->whereColumn('cir.recurrent_id','r.id');
            })
            ->where(function ($q) {
                $q->where('t.type','!=','card')
                    ->orWhereNull('t.type')
                    ->orWhere(fn($qq)=>$qq->where('t.type','card')->where('t.type_card','!=','credit'));
            })
            ->where(function ($q) {
                $q->whereNull('t.title')
                    ->orWhereRaw('LOWER(t.title) NOT IN (?, ?, ?)', ['total fatura','fatura total','total da fatura']);
            })
            ->get([
                'r.id as rid','r.payment_day','r.amount',
                't.id as tid','t.date as tdate','t.type as pay','t.type_card','t.account_id','t.card_id','t.recurrence_type',
                'c.id as category_id','c.name as category_name','c.type as cat_type','c.color'
            ]);
        foreach ($recMY as $r) {
            $tStart = Carbon::parse($r->tdate);
            $type   = in_array($r->cat_type, ['entrada','despesa','investimento'], true) ? $r->cat_type : 'investimento';
            if ($r->recurrence_type === 'monthly') {
                $occ = $monthStart->copy()->day(min((int)$r->payment_day, $monthStart->daysInMonth));
                if ($occ->betweenIncluded($rangeStart, $rangeEnd) && $occ->gte($tStart)) {
                    $ym = $occ->format('Y-m');
                    if (empty($paid[$r->tid][$ym])) {
                        $out[] = [
                            'type'=>$type,'amount'=>abs((float)$r->amount),
                            'category_id'=>(string)$r->category_id,'category_name'=>$r->category_name,'color'=>$r->color,
                            'pay'=>$r->pay,'type_card'=>$r->type_card,'account_id'=>$r->account_id,'card_id'=>$r->card_id,
                            'is_invoice'=>false,
                        ];
                    }
                }
            } else { // yearly
                $anchorMonth = Carbon::parse($r->tdate)->month;
                if ((int)$monthStart->month === (int)$anchorMonth) {
                    $daysIn = $monthStart->daysInMonth;
                    $occ = Carbon::create($monthStart->year, $anchorMonth, min((int)$r->payment_day, $daysIn));
                    if ($occ->betweenIncluded($rangeStart, $rangeEnd) && $occ->gte($tStart)) {
                        $ym = $occ->format('Y-m');
                        if (empty($paid[$r->tid][$ym])) {
                            $out[] = [
                                'type'=>$type,'amount'=>abs((float)$r->amount),
                                'category_id'=>(string)$r->category_id,'category_name'=>$r->category_name,'color'=>$r->color,
                                'pay'=>$r->pay,'type_card'=>$r->type_card,'account_id'=>$r->account_id,'card_id'=>$r->card_id,
                                'is_invoice'=>false,
                            ];
                        }
                    }
                }
            }
        }

        // === RECORRENTES custom (itens explícitos)
        $recC = DB::table('recurrents as r')
            ->join('transactions as t','t.id','=','r.transaction_id')
            ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
            ->join('custom_item_recurrents as ci','ci.recurrent_id','=','r.id')
            ->whereIn('r.user_id', $userIds)
            ->get([
                't.id as tid','t.type as pay','t.type_card','t.account_id','t.card_id',
                'c.id as category_id','c.name as category_name','c.type as cat_type','c.color',
                'ci.reference_year','ci.reference_month','ci.payment_day','ci.amount'
            ]);
        foreach ($recC as $r) {
            $daysIn = Carbon::create($r->reference_year, $r->reference_month, 1)->daysInMonth;
            $occ    = Carbon::create($r->reference_year, $r->reference_month, min((int)$r->payment_day, $daysIn));
            if (!$occ->betweenIncluded($rangeStart, $rangeEnd)) continue;
            $ymd = $occ->toDateString();
            if (!empty(($paid['_byDate'][$r->tid] ?? [])[$ymd])) continue;
            $type = in_array($r->cat_type, ['entrada','despesa','investimento'], true) ? $r->cat_type : 'investimento';
            $out[] = [
                'type'=>$type,'amount'=>abs((float)$r->amount),
                'category_id'=>(string)$r->category_id,'category_name'=>$r->category_name,'color'=>$r->color,
                'pay'=>$r->pay,'type_card'=>$r->type_card,'account_id'=>$r->account_id,'card_id'=>$r->card_id,
                'is_invoice'=>false,
            ];
        }

        // === RECORRENTES custom "a cada X dias" (sem itens)
        $recD = DB::table('recurrents as r')
            ->join('transactions as t','t.id','=','r.transaction_id')
            ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
            ->whereIn('r.user_id', $userIds)
            ->where('r.interval_unit','days')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('custom_item_recurrents as cir')
                    ->whereColumn('cir.recurrent_id','r.id');
            })
            ->get(['r.*','t.id as tid','t.type as pay','t.type_card','t.account_id','t.card_id','c.id as category_id','c.name as category_name','c.type as cat_type','c.color']);
        foreach ($recD as $r) {
            $type = in_array($r->cat_type, ['entrada','despesa','investimento'], true) ? $r->cat_type : 'investimento';
            $start    = Carbon::parse($r->start_date)->startOfDay();
            $interval = max(1, (int)$r->interval_value);
            $cursor = $this->firstAlignedDays($start, $rangeStart->copy()->startOfDay(), $interval);
            $cursor = $this->normalizeW($cursor, (bool)$r->include_sat, (bool)$r->include_sun);
            while ($cursor->lte($rangeEnd)) {
                $ymd = $cursor->toDateString();
                if (empty(($paid['_byDate'][$r->tid] ?? [])[$ymd])) {
                    $out[] = [
                        'type'=>$type,'amount'=>abs((float)$r->amount),
                        'category_id'=>(string)$r->category_id,'category_name'=>$r->category_name,'color'=>$r->color,
                        'pay'=>$r->pay,'type_card'=>$r->type_card,'account_id'=>$r->account_id,'card_id'=>$r->card_id,
                        'is_invoice'=>false,
                    ];
                }
                $cursor = $this->normalizeW(
                    $cursor->copy()->addDays($interval),
                    (bool)$r->include_sat,
                    (bool)$r->include_sun
                );
            }
        }

        // === FATURAS a vencer no mês (não pagas)
        $rows = DB::table('invoices as inv')
            ->join('cards as c','c.id','=','inv.card_id')
            ->leftJoin('invoice_items as it','it.invoice_id','=','inv.id')
            ->whereIn('inv.user_id', $userIds)
            ->where('inv.current_month', $currentMonth)
            ->where('inv.paid', false)
            ->groupBy('inv.id','inv.card_id','inv.current_month','c.due_day')
            ->select('inv.id','inv.card_id','inv.current_month','c.due_day', DB::raw('COALESCE(SUM(it.amount),0) as total'))
            ->get();

        foreach ($rows as $r) {
            $base = Carbon::createFromFormat('Y-m', $r->current_month)->startOfMonth();
            $due  = $base->copy()->day(min((int)($r->due_day ?: 1), $base->daysInMonth));
            $total = (float)$r->total;
            if ($total > 0 && $due->betweenIncluded($rangeStart, $rangeEnd)) {
                $out[] = [
                    'type'=>'despesa','amount'=>abs($total),
                    'category_id'=>null,'category_name'=>'Fatura Cartão','color'=>'#8b5cf6',
                    'pay'=>'card','type_card'=>'credit','account_id'=>null,'card_id'=>$r->card_id,
                    'is_invoice'=>true,'current_month'=>$r->current_month,
                ];
            }
        }

        return $out;
    }
}
