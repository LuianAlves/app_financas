<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AdditionalUser;

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
        $yearNum  = Carbon::parse($start)->format('Y');

        $ym = $req->string('month')->toString();
        $currentMonth = $ym && preg_match('/^\d{4}-\d{2}$/', $ym) ? $ym : now()->format('Y-m');

        $mode        = $req->string('mode', 'tx')->toString(); // tx | invoice
        $level       = $req->string('level', $mode === 'invoice' ? 'invoice_cards' : 'type')->toString();

        // params comuns
        $type         = $req->string('type')->toString();
        $categoryId   = $req->string('category_id')->toString();
        $pay          = $req->string('pay')->toString();
        $cardType     = $req->string('card_type')->toString();
        $invoiceId    = $req->string('invoice_id')->toString();

        // =======================
        // MODO: TRANSAÇÕES
        // =======================
        if ($mode === 'tx') {
            $breadcrumbs = [];

            if ($level === 'type') {
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
                    ->join('transactions as t','t.id','=','p.transaction_id')
                    ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
                    ->whereIn('t.user_id', $userIds)
                    ->whereBetween('p.payment_date', [$start, $end])   // <<< troquei
                    ->whereRaw("NOT (c.type = 'despesa' AND t.type = 'card')")
                    ->where(function ($q) use ($start,$end) {
                        $q->whereNull('t.date')
                            ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end]);
                    })
                    ->selectRaw('c.type as label, SUM(p.amount) as value')
                    ->groupBy('c.type')
                    ->pluck('value','label');

                $invItemsDesp = DB::table('invoice_items as it')
                    ->join('invoices as i','i.id','=','it.invoice_id')
                    ->join('transaction_categories as c','c.id','=','it.transaction_category_id')
                    ->whereIn('i.user_id', $userIds)
                    ->where('i.current_month', $currentMonth)     // << AQUI
                    ->where('c.type','despesa')
                    ->selectRaw('SUM(it.amount) as total')->value('total') ?? 0;

                // 2.3b Pagamentos de fatura no mês (só se a fatura não tiver itens – evita duplicar)
                $invPaysDesp = (float) DB::table('invoice_payments as ip')
                    ->join('invoices as i','i.id','=','ip.invoice_id')
                    ->whereIn('i.user_id', $userIds)
                    ->where('i.current_month', $currentMonth)     // << AQUI
                    ->whereBetween('ip.paid_at', [$start.' 00:00:00', $end.' 23:59:59'])
                    ->whereNotExists(function($q){
                        $q->select(DB::raw(1))
                            ->from('invoice_items as it')
                            ->whereColumn('it.invoice_id','i.id'); // basta existir item em qualquer data
                    })
                    ->selectRaw('COALESCE(SUM(ip.amount),0) as total')->value('total');

                // 2.4 Consolidado
                $sum = ['entrada'=>0,'despesa'=>0,'investimento'=>0];
                foreach ($txBase as $k=>$v) $sum[$k] += (float)$v;
                foreach ($payAdd as $k=>$v) $sum[$k] += (float)$v;
                $sum['despesa'] += ($invItemsDesp + $invPaysDesp);

                $rows = collect([
                    ['label'=>'Entrada',      'key'=>'entrada',     'color'=>'#a6e3a1'],
                    ['label'=>'Despesa',      'key'=>'despesa',     'color'=>'#f38ba8'],
                    ['label'=>'Investimento', 'key'=>'investimento','color'=>'#89b4fa'],
                ])->map(fn($r)=>[
                    'id'    => Str::uuid()->toString(),
                    'label' => $r['label'],
                    'value' => round($sum[$r['key']] ?? 0, 2),
                    'color' => $r['color'],
                    'next'  => ['level'=>'category','params'=>['type'=>strtolower($r['key'])]],
                ]);

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'tx','level'=>'type','title'=>'Tipos',
                    'breadcrumbs'=>[], 'items'=>$rows, 'total'=>$rows->sum('value'),
                ]);
            }

            if ($level === 'category' && $type) {
                $breadcrumbs[] = ['label' => 'Tipos', 'level' => 'type', 'params' => []];

                if ($type === 'despesa') {
                    // 3.1 Transações do mês (sem 'card')
                    $txCat = DB::table('transactions as t')
                        ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
                        ->whereIn('t.user_id', $userIds)
                        ->where('c.type','despesa')
                        ->whereRaw("t.type <> 'card'") // <<< faltava isso
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(t.amount) as value')
                        ->groupBy('c.id','c.name','c.color')
                        ->get()->keyBy('id');

                    $payCat = DB::table('payment_transactions as p')
                        ->join('transactions as t','t.id','=','p.transaction_id')
                        ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
                        ->whereIn('t.user_id', $userIds)
                        ->where('c.type','despesa')
                        ->whereBetween('p.payment_date', [$start, $end])   // <<< troquei
                        ->where(function ($q) use ($start,$end) {
                            $q->whereNull('t.date')
                                ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end]);
                        })
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(p.amount) as value')
                        ->groupBy('c.id','c.name','c.color')
                        ->get()->keyBy('id');

                    // 3.3 Total de faturas do mês (para a categoria sintética)
                    $invTotal = DB::table('invoice_items as it')
                        ->join('invoices as i','i.id','=','it.invoice_id')
                        ->whereIn('i.user_id',$userIds)
                        ->where('i.current_month', $currentMonth)     // << AQUI
                        ->selectRaw('SUM(it.amount) as total')->value('total') ?? 0;

// 3.4 Merge TX + PAY e depois injeta a categoria "Faturas"
                    $ids = collect($txCat->keys())->merge($payCat->keys())->unique();

                    $rows = $ids->map(function ($id) use ($txCat,$payCat) {
                        $name  = $txCat[$id]->name  ?? $payCat[$id]->name  ?? '—';
                        $color = $txCat[$id]->color ?? $payCat[$id]->color ?? '#18dec7';
                        $value = (float)($txCat[$id]->value ?? 0) + (float)($payCat[$id]->value ?? 0);
                        return [
                            'id'    => $id,
                            'label' => $name,
                            'value' => round($value, 2),
                            'color' => $color,
                            'next'  => ['level'=>'pay','params'=>['type'=>'despesa','category_id'=>$id]],
                        ];
                    })->values();

                    // Injeta "Faturas" (categoria sintética) no topo, se houver valor
                    if ($invTotal > 0) {
                        $rows->prepend([
                            'id'    => '__INVOICES__',
                            'label' => 'Faturas',
                            'value' => round($invTotal, 2),
                            'color' => '#8b5cf6',
                            'next'  => ['level' => 'invoice_cards_tx', 'params' => []],
                        ]);
                    }
                    $rows = $rows->sortByDesc('value')->values();
                    $rows = $rows->map(fn($r)=>self::paint($r));

                    return response()->json([
                        'mode'=>'tx','level'=>'category','title'=>'Categorias (Despesa)',
                        'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                    ]);
                }

                // === entrada / investimento (corrigido: soma payments do mês)
                $txCat = DB::table('transactions as t')
                    ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
                    ->whereIn('t.user_id', $userIds)
                    ->where('c.type', $type) // 'entrada' ou 'investimento'
                    ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                    ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(t.amount) as tvalue')
                    ->groupBy('c.id','c.name','c.color')
                    ->get()->keyBy('id');

                $monthNum = \Carbon\Carbon::parse($start)->format('m');
                $monthNo0 = ltrim($monthNum, '0'); // caso tenha salvo "9" em vez de "09"

                $payCat = DB::table('payment_transactions as p')
                    ->join('transactions as t','t.id','=','p.transaction_id')
                    ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
                    ->whereIn('t.user_id', $userIds)
                    ->where('c.type', $type) // entrada ou investimento
                    ->whereBetween('p.payment_date', [$start, $end])   // <<< troquei
                    ->where(function ($q) use ($start,$end) {
                        $q->whereNull('t.date')
                            ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end]);
                    })
                    ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(p.amount) as pvalue')
                    ->groupBy('c.id','c.name','c.color')
                    ->get()->keyBy('id');

                $ids = collect($txCat->keys())->merge($payCat->keys())->unique();

                $rows = $ids->map(function ($id) use ($txCat,$payCat,$type) {
                    $name  = $txCat[$id]->name  ?? $payCat[$id]->name  ?? '—';
                    $color = $txCat[$id]->color ?? $payCat[$id]->color ?? '#18dec7';
                    $value = (float)($txCat[$id]->tvalue ?? 0) + (float)($payCat[$id]->pvalue ?? 0);
                    return [
                        'id'    => $id,
                        'label' => $name,
                        'value' => round($value, 2),
                        'color' => $color,
                        'next'  => ['level'=>'pay','params'=>['type'=>$type,'category_id'=>$id]],
                    ];
                })->sortByDesc('value')->values();

                return response()->json([
                    'mode'=>'tx','level'=>'category','title'=>'Categorias ('.ucfirst($type).')',
                    'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            if ($level === 'pay' && $categoryId) {
                $breadcrumbs[] = ['label'=>'Tipos','level'=>'type','params'=>[]];
                $breadcrumbs[] = ['label'=>ucfirst($type),'level'=>'category','params'=>['type'=>$type]];

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
                    ->join('transactions as t','t.id','=','p.transaction_id')
                    ->whereIn('t.user_id', $userIds)
                    ->where('t.transaction_category_id', $categoryId)
                    ->whereBetween('p.payment_date', [$start, $end])
                    ->where(function ($q) use ($start,$end) {
                        $q->whereNull('t.date')
                            ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end]);
                    })
                    ->selectRaw('t.type as label, SUM(p.amount) as value')
                    ->groupBy('t.type')
                    ->pluck('value', 'label');

                // C) Consolida
                $byType = ['pix'=>0,'money'=>0,'card'=>0];
                foreach ($tx as $k=>$v)  { $byType[$k] = ($byType[$k] ?? 0) + (float)$v; }
                foreach ($pay as $k=>$v) { $byType[$k] = ($byType[$k] ?? 0) + (float)$v; }

                // D) Monta itens
                $mapLabel = ['pix'=>'PIX','money'=>'Dinheiro','card'=>'Cartão'];

                $mapColor = [
                    'pix'   => '#a6e3a1', // antes #22c55e
                    'money' => '#f9e2af', // amarelo suave (mais clean que #f59e0b)
                    'card'  => '#89b4fa', // antes #6366f1
                ];

                $rows = collect($byType)
                    ->filter(fn($v)=>$v > 0)
                    ->map(function($v,$k) use ($mapLabel,$mapColor,$type,$categoryId){
                        return [
                            'id'    => $k,
                            'label' => $mapLabel[$k] ?? strtoupper($k),
                            'value' => (float)$v,
                            'color' => $mapColor[$k] ?? '#18dec7',
                            'next'  => [
                                'level'  => $k === 'card' ? 'card_type' : 'instrument',
                                'params' => ['type'=>$type,'category_id'=>$categoryId,'pay'=>$k],
                            ],
                        ];
                    })->values();

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'tx','level'=>'pay','title'=>'Forma de pagamento',
                    'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            if ($level === 'card_type' && $categoryId) {
                $breadcrumbs[] = ['label'=>'Tipos','level'=>'type','params'=>[]];
                $breadcrumbs[] = ['label'=>ucfirst($type),'level'=>'category','params'=>['type'=>$type]];
                $breadcrumbs[] = ['label'=>'Cartão','level'=>'pay','params'=>['type'=>$type,'category_id'=>$categoryId]];

                $rows = DB::table('transactions as t')
                    ->whereIn('t.user_id', $userIds)
                    ->where('t.transaction_category_id',$categoryId)
                    ->where('t.type','card')
                    ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                    ->selectRaw('t.type_card as label, SUM(t.amount) as value')
                    ->groupBy('t.type_card')->orderByDesc('value')->get()
                    ->map(function($r) use($type,$categoryId){
                        $lbl = $r->label ?: 'desconhecido';
                        $nice = ['credit'=>'Crédito','debit'=>'Débito'][$lbl] ?? ucfirst($lbl);
                        return [
                            'id'=>$lbl,'label'=>$nice,'value'=>(float)$r->value,
                            'color'=> $lbl==='credit' ? '#89b4fa' : '#94e2d5',
                            'next'=>[
                                'level'=>'instrument',
                                'params'=>['type'=>$type,'category_id'=>$categoryId,'pay'=>'card','card_type'=>$lbl],
                            ],
                        ];
                    });

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'tx','level'=>'card_type','title'=>'Crédito x Débito',
                    'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            if ($level === 'instrument') {
                $breadcrumbs[] = ['label'=>'Tipos','level'=>'type','params'=>[]];
                if ($type)       $breadcrumbs[] = ['label'=>ucfirst($type),'level'=>'category','params'=>['type'=>$type]];
                if ($categoryId) $breadcrumbs[] = ['label'=>'Pagamento','level'=>'pay','params'=>['type'=>$type,'category_id'=>$categoryId]];

                if ($pay === 'card') {
                    $q = DB::table('transactions as t')
                        ->join('cards as k','k.id','=','t.card_id')
                        ->whereIn('t.user_id', $userIds)
                        ->when($categoryId, fn($qq)=>$qq->where('t.transaction_category_id',$categoryId))
                        ->where('t.type','card')
                        ->when($cardType, fn($qq)=>$qq->where('t.type_card',$cardType))
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('k.id, k.cardholder_name as label, SUM(t.amount) as value, COALESCE(k.color_card,"#a78bfa") as color')
                        ->groupBy('k.id','k.cardholder_name','k.color_card')
                        ->orderByDesc('value')->get();

                    $items = $q->map(fn($r)=>[
                        'id'=>$r->id,'label'=>$r->label,'value'=>(float)$r->value,'color'=>$r->color,'next'=>null
                    ]);

                    return response()->json([
                        'mode'=>'tx','level'=>'instrument','title'=>'Cartões',
                        'breadcrumbs'=>$breadcrumbs,'items'=>$items,'total'=>$items->sum('value'),
                    ]);
                } else {
                    // === PIX / Dinheiro: somar transações do mês + payments do mês (conta via transactions.account_id)
                    $txAcc = DB::table('transactions as t')
                        ->leftJoin('accounts as a','a.id','=','t.account_id') // LEFT
                        ->whereIn('t.user_id', $userIds)
                        ->when($categoryId, fn($qq)=>$qq->where('t.transaction_category_id',$categoryId))
                        ->where('t.type',$pay) // 'pix' ou 'money'
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('IFNULL(a.id, "_none") as id, COALESCE(a.bank_name,"(Sem conta)") as label, SUM(t.amount) as value')
                        ->groupBy(DB::raw('IFNULL(a.id, "_none")'), DB::raw('COALESCE(a.bank_name,"(Sem conta)")'))
                        ->get()->keyBy('id');

                    $payAcc = DB::table('payment_transactions as p')
                        ->join('transactions as t','t.id','=','p.transaction_id')
                        ->leftJoin('accounts as a','a.id','=','t.account_id') // conta vem da transação paga
                        ->whereIn('t.user_id', $userIds)
                        ->when($categoryId, fn($qq)=>$qq->where('t.transaction_category_id',$categoryId))
                        ->whereBetween('p.payment_date', [$start, $end])
                        ->where(function ($q) use ($start,$end) {
                            $q->whereNull('t.date')
                                ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end]);
                        })
                        ->where('t.type', $pay) // garante mesmo tipo
                        ->selectRaw('IFNULL(a.id, "_none") as id, COALESCE(a.bank_name,"(Sem conta)") as label, SUM(p.amount) as value')
                        ->groupBy(DB::raw('IFNULL(a.id, "_none")'), DB::raw('COALESCE(a.bank_name,"(Sem conta)")'))
                        ->get()->keyBy('id');

                    $ids = collect($txAcc->keys())->merge($payAcc->keys())->unique();

                    $items = $ids->map(function($id) use ($txAcc,$payAcc){
                        $label = $txAcc[$id]->label ?? $payAcc[$id]->label ?? '(Sem conta)';
                        $value = (float)($txAcc[$id]->value ?? 0) + (float)($payAcc[$id]->value ?? 0);
                        $color = self::colorForKey((string)$id.$label);
                        return ['id'=>$id,'label'=>$label,'value'=>$value,'color'=>$color,'next'=>null];
                    })->sortByDesc('value')->values();

                    $items = $items->map(fn($r)=>self::paint($r));  // <<< não esqueça

                    return response()->json([
                        'mode'=>'tx','level'=>'instrument','title'=>'Contas',
                        'breadcrumbs'=>$breadcrumbs,'items'=>$items,'total'=>$items->sum('value'),
                    ]);
                }
            }

            if ($level === 'invoice_cards_tx') {
                $breadcrumbs[] = ['label'=>'Tipos','level'=>'type','params'=>[]];
                $breadcrumbs[] = ['label'=>'Despesa','level'=>'category','params'=>['type'=>'despesa']];
                $breadcrumbs[] = ['label'=>'Faturas','level'=>'invoice_cards_tx','params'=>[]];

                $rows = DB::table('invoices as i')
                    ->join('invoice_items as it','it.invoice_id','=','i.id')
                    ->join('cards as k','k.id','=','i.card_id')
                    ->whereIn('i.user_id', $userIds)
                    ->where('i.current_month', $currentMonth)       // << AQUI
                    ->selectRaw('k.id, CONCAT(k.cardholder_name," • ",LPAD(COALESCE(k.last_four_digits,0),4,"0")) as label,
               COALESCE(k.color_card,"#a78bfa") as color, SUM(it.amount) as value')
                    ->groupBy('k.id','k.cardholder_name','k.last_four_digits','k.color_card')
                    ->orderByDesc('value')->get()
                    ->map(fn($r)=>[
                        'id'=>$r->id,'label'=>$r->label,'value'=>(float)$r->value,'color'=>$r->color,
                        'next'=>['level'=>'invoice_items_tx','params'=>['card_id'=>$r->id]],
                    ]);

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'tx','level'=>'invoice_cards_tx','title'=>'Faturas por cartão',
                    'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            if ($level === 'invoice_items_tx') {
                $cardId = $req->string('card_id')->toString();

                $breadcrumbs[] = ['label'=>'Tipos','level'=>'type','params'=>[]];
                $breadcrumbs[] = ['label'=>'Despesa','level'=>'category','params'=>['type'=>'despesa']];
                $breadcrumbs[] = ['label'=>'Faturas','level'=>'invoice_cards_tx','params'=>[]];

                $rows = DB::table('invoice_items as it')
                    ->join('invoices as i','i.id','=','it.invoice_id')
                    ->join('transaction_categories as c','c.id','=','it.transaction_category_id')
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
                    ->map(fn($r)=>[
                        'id'=>$r->id,
                        'label'=>$r->label.' — '.\Carbon\Carbon::parse($r->date)->format('d/m'),
                        'value'=>(float)$r->value,
                        'color'=>$r->color,  // << aqui também
                        'next'=>null
                    ]);

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'tx','level'=>'invoice_items_tx','title'=>'Itens da fatura',
                    'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
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
                    ->leftJoin('invoice_items as it','it.invoice_id','=','i.id')
                    ->join('cards as k','k.id','=','i.card_id')
                    ->whereIn('i.user_id', $userIds)
                    ->whereBetween('it.date', [$start, $end]) // << FILTRO DO MÊS
                    ->selectRaw('i.id, k.id as card_id,
        CONCAT(k.cardholder_name, " • ", LPAD(COALESCE(k.last_four_digits,0),4,"0")) as label,
        COALESCE(k.color_card,"#a78bfa") as color,
        SUM(it.amount) as items_total,
        MAX(i.paid) as is_paid')
                    ->groupBy('i.id','k.id','k.cardholder_name','k.last_four_digits','k.color_card')
                    ->orderBy('label')->get()
                    ->map(function($r){
                        return [
                            'id'    => $r->id,
                            'label' => $r->label . ($r->is_paid ? ' (pago)' : ''),
                            'value' => (float)$r->items_total,   // << SOMENTE O TOTAL DO MÊS
                            'color' => $r->color,
                            'next'  => ['level'=>'invoice_categories','params'=>['invoice_id'=>$r->id]],
                        ];
                    });

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'invoice','level'=>'invoice_cards','title'=>'Faturas do mês',
                    'breadcrumbs'=>[['label'=>'Faturas','level'=>'invoice_cards','params'=>[]]],
                    'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            // Nível 2: categorias dentro da fatura
            if ($level === 'invoice_categories' && $invoiceId) {
                $card = DB::table('invoices as i')
                    ->join('cards as k','k.id','=','i.card_id')
                    ->where('i.id',$invoiceId)
                    ->selectRaw('CONCAT(k.cardholder_name," • ",LPAD(COALESCE(k.last_four_digits,0),4,"0")) as label')->first();

                $rows = DB::table('invoice_items as it')
                    ->join('transaction_categories as c','c.id','=','it.transaction_category_id')
                    ->where('it.invoice_id',$invoiceId)
                    ->whereBetween('it.date', [$start, $end]) // << AQUI
                    ->selectRaw('c.id, c.name as label, COALESCE(c.color,"#18dec7") as color, SUM(it.amount) as value')
                    ->groupBy('c.id','c.name','c.color')
                    ->orderByDesc('value')->get()
                    ->map(fn($r)=>[
                        'id'=>$r->id,'label'=>$r->label,'value'=>(float)$r->value,'color'=>$r->color,
                        'next'=>['level'=>'invoice_items','params'=>['invoice_id'=>$invoiceId,'category_id'=>$r->id]],
                    ]);

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'invoice','level'=>'invoice_categories','title'=>'Categorias da fatura',
                    'breadcrumbs'=>[
                        ['label'=>'Faturas','level'=>'invoice_cards','params'=>[]],
                        ['label'=>$card?->label ?? 'Cartão','level'=>'invoice_categories','params'=>['invoice_id'=>$invoiceId]],
                    ],
                    'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            // Nível 3: itens da fatura (opcionalmente filtrados por categoria)
            if ($level === 'invoice_items' && $invoiceId) {
                $catName = $categoryId ? DB::table('transaction_categories')->where('id',$categoryId)->value('name') : null;

                $rows = DB::table('invoice_items as it')
                    ->join('transaction_categories as c','c.id','=','it.transaction_category_id') // inner é ok (é not null)
                    ->where('it.invoice_id',$invoiceId)
                    ->when($categoryId, fn($q)=>$q->where('it.transaction_category_id',$categoryId))
                    ->whereBetween('it.date', [$start, $end])
                    ->selectRaw('
        it.id,
        COALESCE(it.title,c.name) as label,
        it.amount as value,
        it.date,
        COALESCE(c.color,"#94a3b8") as color
    ')
                    ->orderByDesc('it.date')->get()
                    ->map(fn($r)=>[
                        'id'    => $r->id,
                        'label' => $r->label.' — '.Carbon::parse($r->date)->format('d/m'),
                        'value' => (float)$r->value,
                        'color' => $r->color,   // << usa a cor da categoria
                        'next'  => null,
                    ]);

                $rows = $rows->map(fn($r)=>self::paint($r));

                return response()->json([
                    'mode'=>'invoice','level'=>'invoice_items','title'=>$catName ? "Itens • $catName" : 'Itens da fatura',
                    'breadcrumbs'=>[
                        ['label'=>'Faturas','level'=>'invoice_cards','params'=>[]],
                        ['label'=>'Categorias','level'=>'invoice_categories','params'=>['invoice_id'=>$invoiceId]],
                    ],
                    'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }
        }

        return response()->json(['mode'=>$mode,'level'=>$level,'items'=>[],'breadcrumbs'=>[],'total'=>0]);
    }

    private function monthBounds(?string $ym): array
    {
        $m = $ym && preg_match('/^\d{4}-\d{2}$/', $ym) ? Carbon::createFromFormat('Y-m', $ym) : now();
        $start = $m->copy()->startOfMonth()->toDateString();
        $end   = $m->copy()->endOfMonth()->toDateString();
        return [$start, $end];
    }

    private static function withAlpha(string $hex, string $alpha = '82'): string
    {
        $hex = trim($hex);
        if ($hex === '') return '#94a3b8'.$alpha;        // fallback

        // aceita "#RGB", "#RRGGBB", "#RRGGBBAA" ou sem "#"
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        } elseif (strlen($hex) === 8) {
            $hex = substr($hex, 0, 6); // remove alpha antigo
        } elseif (strlen($hex) !== 6) {
            return '#'.$hex; // formatos não-hex: devolve como veio
        }
        return '#'.$hex.$alpha; // #RRGGBB1a
    }

    private static function paint(array $row): array
    {
        $base = $row['color'] ?? self::colorForKey(($row['id'] ?? $row['label'] ?? 'x'));
        $row['color']  = $base;              // mantém compat
        $row['border'] = $base;              // borda sólida
        $row['bg']     = self::withAlpha($base, '1a'); // fill com alpha
        return $row;
    }

    private static function colorForKey(string $key): string
    {
        $palette = [
            '#89b4fa','#a6e3a1','#f38ba8','#94e2d5','#f9e2af',
            '#fab387','#cba6f7','#f5c2e7','#74c7ec','#b4befe',
            '#8bd5ca','#eed49f'
        ];
        $i = abs(crc32($key)) % count($palette);
        return $palette[$i];
    }
}
