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
                    ->where('t.user_id', $userId)
                    ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                    ->whereRaw("NOT (c.type = 'despesa' AND t.type = 'card')")
                    ->selectRaw('c.type as label, SUM(t.amount) as value')
                    ->groupBy('c.type')
                    ->pluck('value', 'label'); // ['entrada'=>..., 'despesa'=>..., 'investimento'=>...]

                // 2.2 Pagamentos do mês (para transações que NÃO caem no mês corrente)
                $payAdd = DB::table('payment_transactions as p')
                    ->join('transactions as t', 't.id', '=', 'p.transaction_id')
                    ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                    ->where('t.user_id', $userId)
                    ->where('p.reference_month', $monthNum)
                    ->where('p.reference_year',  $yearNum)
                    ->whereRaw("NOT (c.type = 'despesa' AND t.type = 'card')")
                    ->where(function ($q) use ($start, $end) {
                        $q->whereNull('t.date')
                            ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end]);
                    })
                    ->selectRaw('c.type as label, SUM(p.amount) as value')
                    ->groupBy('c.type')
                    ->pluck('value', 'label');

                // 2.3 Faturas do mês -> somar em DESPESA
                $invDesp = (float) (DB::table('invoice_items as it')
                    ->join('invoices as i', 'i.id', '=', 'it.invoice_id')
                    ->join('transaction_categories as c','c.id','=','it.transaction_category_id')
                    ->where('i.user_id', $userId)
                    ->where('c.type','despesa')
                    ->whereBetween('it.date', [$start, $end])
                    ->selectRaw('SUM(it.amount) as total')->value('total') ?? 0);

                // 2.4 Consolidado
                $sum = ['entrada'=>0,'despesa'=>0,'investimento'=>0];
                foreach ($txBase as $k=>$v) $sum[$k] += (float)$v;
                foreach ($payAdd as $k=>$v) $sum[$k] += (float)$v;
                $sum['despesa'] += $invDesp;

                $rows = collect([
                    ['label'=>'Entrada',      'key'=>'entrada',     'color'=>'#10b981'],
                    ['label'=>'Despesa',      'key'=>'despesa',     'color'=>'#ef4444'],
                    ['label'=>'Investimento', 'key'=>'investimento','color'=>'#3b82f6'],
                ])->map(fn($r)=>[
                    'id'    => Str::uuid()->toString(),
                    'label' => $r['label'],
                    'value' => round($sum[$r['key']] ?? 0, 2),
                    'color' => $r['color'],
                    'next'  => ['level' => 'category', 'params' => ['type' => strtolower($r['key'])]],
                ]);

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
                        ->where('t.user_id',$userId)
                        ->where('c.type','despesa')
                        ->whereRaw("t.type <> 'card'")
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end])
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(t.amount) as value')
                        ->groupBy('c.id','c.name','c.color')
                        ->get()
                        ->keyBy('id');

                    // 3.2 Pagamentos do mês (de transações de despesa fora do mês)
                    $payCat = DB::table('payment_transactions as p')
                        ->join('transactions as t','t.id','=','p.transaction_id')
                        ->join('transaction_categories as c','c.id','=','t.transaction_category_id')
                        ->where('t.user_id',$userId)
                        ->where('c.type','despesa')
                        ->whereRaw("t.type <> 'card'")
                        ->where('p.reference_month',$monthNum)
                        ->where('p.reference_year', $yearNum)
                        ->where(function ($q) use ($start,$end) {
                            $q->whereNull('t.date')
                                ->orWhereNotBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start,$end]);
                        })
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(p.amount) as value')
                        ->groupBy('c.id','c.name','c.color')
                        ->get()
                        ->keyBy('id');

                    // 3.3 Faturas do mês por categoria
                    $invCat = DB::table('invoice_items as it')
                        ->join('invoices as i','i.id','=','it.invoice_id')
                        ->join('transaction_categories as c','c.id','=','it.transaction_category_id')
                        ->where('i.user_id',$userId)
                        ->where('c.type','despesa')
                        ->whereBetween('it.date',[$start,$end])
                        ->selectRaw('c.id, c.name, COALESCE(c.color,"#18dec7") as color, SUM(it.amount) as value')
                        ->groupBy('c.id','c.name','c.color')
                        ->get()
                        ->keyBy('id');

                    // 3.4 Merge por categoria
                    $ids = collect($txCat->keys())->merge($payCat->keys())->merge($invCat->keys())->unique();
                    $rows = $ids->map(function ($id) use ($txCat,$payCat,$invCat) {
                        $name  = $txCat[$id]->name  ?? $payCat[$id]->name  ?? $invCat[$id]->name  ?? '—';
                        $color = $txCat[$id]->color ?? $payCat[$id]->color ?? $invCat[$id]->color ?? '#18dec7';
                        $value = (float)($txCat[$id]->value ?? 0) + (float)($payCat[$id]->value ?? 0) + (float)($invCat[$id]->value ?? 0);
                        return [
                            'id'    => $id,
                            'label' => $name,
                            'value' => round($value, 2),
                            'color' => $color,
                            'next'  => ['level'=>'pay','params'=>['type'=>'despesa','category_id'=>$id]],
                        ];
                    })->sortByDesc('value')->values();

                    return response()->json([
                        'mode'=>'tx','level'=>'category','title'=>'Categorias (Despesa)',
                        'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                    ]);
                }

                // === entrada / investimento (original)
                $rows = DB::table('transactions as t')
                    ->join('transaction_categories as c', 'c.id', '=', 't.transaction_category_id')
                    ->where('t.user_id', $userId)
                    ->where('c.type', $type)
                    ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                    ->selectRaw('c.id, c.name as label, COALESCE(c.color,"#18dec7") as color, SUM(t.amount) as value')
                    ->groupBy('c.id','c.name','c.color')
                    ->orderByDesc('value')->get()
                    ->map(fn($r) => [
                        'id'=>$r->id,'label'=>$r->label,'value'=>(float)$r->value,'color'=>$r->color,
                        'next'=>['level'=>'pay','params'=>['type'=>$type,'category_id'=>$r->id]],
                    ]);

                return response()->json([
                    'mode'=>'tx','level'=>'category','title'=>'Categorias ('.ucfirst($type).')',
                    'breadcrumbs'=>$breadcrumbs,'items'=>$rows,'total'=>$rows->sum('value'),
                ]);
            }

            if ($level === 'pay' && $categoryId) {
                $breadcrumbs[] = ['label'=>'Tipos','level'=>'type','params'=>[]];
                $breadcrumbs[] = ['label'=>ucfirst($type),'level'=>'category','params'=>['type'=>$type]];

                $rows = DB::table('transactions as t')
                    ->whereIn('t.user_id', $userIds)
                    ->where('t.transaction_category_id',$categoryId)
                    ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                    ->selectRaw('t.type as label, SUM(t.amount) as value')
                    ->groupBy('t.type')->orderByDesc('value')->get()
                    ->map(function($r) use($type,$categoryId){
                        $label = $r->label;
                        $nice  = ['pix'=>'PIX','card'=>'Cartão','money'=>'Dinheiro'][$label] ?? strtoupper($label);
                        return [
                            'id'=>$label,'label'=>$nice,'value'=>(float)$r->value,
                            'color'=> $label==='card' ? '#6366f1' : ($label==='pix'?'#22c55e':'#f59e0b'),
                            'next'=>[
                                'level'=> $label==='card' ? 'card_type' : 'instrument',
                                'params'=>['type'=>$type,'category_id'=>$categoryId,'pay'=>$label],
                            ],
                        ];
                    });

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
                            'color'=> $lbl==='credit' ? '#0ea5e9' : '#22d3ee',
                            'next'=>[
                                'level'=>'instrument',
                                'params'=>['type'=>$type,'category_id'=>$categoryId,'pay'=>'card','card_type'=>$lbl],
                            ],
                        ];
                    });

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
                    $q = DB::table('transactions as t')
                        ->join('accounts as a','a.id','=','t.account_id')
                        ->whereIn('t.user_id', $userIds)
                        ->when($categoryId, fn($qq)=>$qq->where('t.transaction_category_id',$categoryId))
                        ->where('t.type',$pay)
                        ->whereBetween(DB::raw('COALESCE(t.date, t.create_date)'), [$start, $end])
                        ->selectRaw('a.id, a.bank_name as label, SUM(t.amount) as value')
                        ->groupBy('a.id','a.bank_name')
                        ->orderByDesc('value')->get();

                    $items = $q->map(fn($r)=>[
                        'id'=>$r->id,'label'=>$r->label,'value'=>(float)$r->value,'color'=>'#93c5fd','next'=>null
                    ]);

                    return response()->json([
                        'mode'=>'tx','level'=>'instrument','title'=>'Contas',
                        'breadcrumbs'=>$breadcrumbs,'items'=>$items,'total'=>$items->sum('value'),
                    ]);
                }
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
                    ->leftJoin('transaction_categories as c','c.id','=','it.transaction_category_id')
                    ->where('it.invoice_id',$invoiceId)
                    ->when($categoryId, fn($q)=>$q->where('it.transaction_category_id',$categoryId))
                    ->whereBetween('it.date', [$start, $end]) // << AQUI
                    ->selectRaw('it.id, COALESCE(it.title,c.name) as label, it.amount as value, it.date')
                    ->orderByDesc('it.date')->get()
                    ->map(fn($r)=>[
                        'id'=>$r->id,
                        'label'=>$r->label.' — '.Carbon::parse($r->date)->format('d/m'),
                        'value'=>(float)$r->value,
                        'color'=>'#94a3b8',
                        'next'=>null
                    ]);

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
}
