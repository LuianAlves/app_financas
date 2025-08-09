<?php

namespace App\Http\Controllers\Web;

use App\Models\Card;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class InvoiceController extends Controller
{
    public function index($cardId)
    {
        /*  Falta:
         *
         * Criar tabela de registro de invoices: irá salvar se a fatura foi paga e o valor (assim permite adiantamento/pagamento parcial da fatura)
         * Resolver problema ao cadastrar item no cartão de crédito: liberar para selecionar recorrência (exemplo: assinatura de streaming),
         * também deve resolver problema de cadastrar item com 1x parcela e não salvar. (a ideia é cadastrar como gasto 'unico').
         * */

        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR.UTF-8');

        $card  = Card::with('invoices.items')->findOrFail($cardId);
        $today = Carbon::today();

        $cycle = $today->format('Y-m');
        [$Y, $M] = explode('-', $cycle);
        $dtClose = Carbon::create($Y, $M, $card->closing_day);
        $dtDue   = Carbon::create($Y, $M, $card->due_day);
        $inv     = $card->invoices->firstWhere('current_month', $cycle);

        if ($today->gte($dtClose)) {
            if ($inv && $inv->paid) {
                $cycle = Carbon::create($Y, $M, 1)->addMonth()->format('Y-m');
                [$Y, $M] = explode('-', $cycle);
                $dtClose = Carbon::create($Y, $M, $card->closing_day);
                $dtDue   = Carbon::create($Y, $M, $card->due_day);
                $inv     = $card->invoices->firstWhere('current_month', $cycle);
            }
        }

        $currentInv  = $card->invoices->firstWhere('current_month', $cycle);
        $faturaAtual = $currentInv ? $currentInv->items->sum('amount') : 0;

        App\Http\Controllers\Api\CardController::getTotalInvoice($faturaAtual);

        $transactions = Transaction::query()
            ->where('type', 'card')
            ->where('type_card', 'credit')
            ->where('card_id', $card->id)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $totalUnpaid = $card->invoices
            ->filter(fn($i) => ! $i->paid)
            ->flatMap->items
            ->sum('amount');
        $limiteDisponivel = $card->credit_limit - $totalUnpaid;

        $closeLabel = 'Fecha em <b>' . strtoupper($dtClose->isoFormat('DD MMM')) . '</b>';

        if ($currentInv && ! $currentInv->paid
            && $today->between($dtClose->copy()->addDay(-1), $dtDue)
            && $today->diffInDays($dtDue) === 1
        ) {
            $dueLabel = 'Sua fatura vence amanhã';
        } else {
            $dueLabel = 'Vence em <b>' . strtoupper($dtDue->isoFormat('DD MMM')) . '</b>';
        }

        $invoices = $card->invoices
            ->sortBy('current_month')
            ->map(function($inv){
                $dt = Carbon::createFromFormat('Y-m', $inv->current_month)->locale('pt_BR');
                $inv->month       = strtoupper($dt->isoFormat('MMM'));
                $inv->totalAmount = brlPrice($inv->items->sum('amount'));
                return $inv;
            });

        return view('app.invoices.invoice.invoice_index', [
            'invoices'         => $invoices,
            'faturaAtual'      => brlPrice($faturaAtual),
            'limiteDisponivel' => brlPrice($limiteDisponivel),
            'closeLabel'       => $closeLabel,
            'dueLabel'         => $dueLabel,
            'transactions'     => $transactions,
        ]);
    }
}
