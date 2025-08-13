<?php

namespace App\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Card;
use App\Models\Invoice;

use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index($cardId)
    {
        Carbon::setLocale('pt_BR');
        $card  = Card::with('invoices.items')->findOrFail($cardId);

        // garante 12 meses a partir de hoje
        $today = Carbon::today();
        $startMonth = Carbon::create($today->year, $today->month, 1);
        for ($i=0; $i<12; $i++) {
            $m = $startMonth->copy()->addMonths($i)->format('Y-m');
            Invoice::firstOrCreate(
                ['user_id'=>auth()->id(), 'card_id'=>$card->id, 'current_month'=>$m],
                ['paid'=>false]
            );
        }

        $card->load('invoices.items.category');

        // mês selecionado = competência atual
        $selectedYm = $today->format('Y-m');

        // header para o mês selecionado
        [$header, $items] = $this->buildInvoicePayload($card, $selectedYm);

        // carrossel
        $invoices = $card->invoices
            ->sortBy('current_month')
            ->map(function($inv){
                $dt = Carbon::createFromFormat('Y-m', $inv->current_month)->locale('pt_BR');
                return (object)[
                    'ym' => $inv->current_month,
                    'month' => strtoupper($dt->isoFormat('MMM')),
                    'paid' => (bool)$inv->paid,
                    'total' => brlPrice($inv->items->sum('amount')),
                ];
            })->values();

        return view('app.invoices.invoice.invoice_index', compact('card','invoices','header','items','selectedYm'));
    }

    public function show($cardId, $ym)
    {
        $card = Card::with('invoices.items.category')->findOrFail($cardId);

        [$header, $items] = $this->buildInvoicePayload($card, $ym);

        return response()->json(compact('header','items'));
    }

    private function buildInvoicePayload(Card $card, string $ym): array
    {
        $dt   = Carbon::createFromFormat('Y-m', $ym);
        $close= Carbon::create($dt->year, $dt->month, $card->closing_day);
        $due  = Carbon::create($dt->year, $dt->month, $card->due_day);

        $inv = $card->invoices->firstWhere('current_month', $ym);
        $items = collect();

        $monthTotal = 0;

        if ($inv) {
            $items = $inv->items()->with('category')->orderBy('date')->get()->map(function (\App\Models\InvoiceItem $it){
                return (object)[
                    'id' => $it->id,
                    'title' => $it->title,
                    'date' => \Carbon\Carbon::parse($it->date)->format('d/m/Y'),
                    'amount_raw' => (float)$it->amount,
                    'amount' => brlPrice($it->amount),
                    'installments' => (int)$it->installments,
                    'current_installment' => (int)$it->current_installment,
                    'is_projection' => (bool)$it->is_projection,
                    'icon' => optional($it->category)->icon,
                    'color' => optional($it->category)->color,
                ];
            });

            $monthTotal = $inv->items->sum('amount');
        }

        $selectedYm = $ym;

        $blocked = \App\Models\InvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($card, $selectedYm) {
                $q->where('card_id', $card->id)
                    ->where('current_month', '<=', $selectedYm)
                    ->where('paid', false);
            })
            ->get()
            ->sum(function ($item) {
                if ($item->installments > 1) {
                    $restantes = $item->installments - $item->current_installment + 1;
                    return ($item->amount) * $restantes;
                }
                return $item->amount;
            });

        $limitAvail = max(0, $card->credit_limit - $blocked);

        $header = [
            'ym'           => $ym,
            'month_label'  => ucfirst($dt->locale('pt_BR')->isoFormat('MMMM')),
            'paid'         => (bool)optional($inv)->paid,
            'total'        => brlPrice($monthTotal),
            'limit'        => brlPrice($limitAvail),
            'close_label'  => 'Fecha em <b>'.strtoupper($close->isoFormat('DD MMM')).'</b>',
            'due_label'    => 'Vence em <b>'.strtoupper($due->isoFormat('DD MMM')).'</b>',
        ];

        return [$header, $items];
    }
}
