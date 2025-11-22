<?php

namespace App\Services;

use App\Models\CdiRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class CdiService
{
    /**
     * Busca a taxa CDI anual da tabela ou da API do Banco Central.
     */
    public function getAnnualRate(Carbon $date): float
    {
        $iso = $date->toDateString();

        // 1) Tenta buscar no banco (cache local)
        $row = CdiRate::where('date', $iso)->first();
        if ($row) {
            return $row->annual_rate;
        }

        // 2) Busca na API pública do BCB
        try {
            $url = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados?formato=json&limit=1";

            $res = Http::timeout(8)->retry(2, 200)->get($url);

            if ($res->successful()) {
                $json = $res->json();
                $last = $json[0] ?? null;

                if ($last && isset($last['valor'])) {
                    // API retorna tipo: "14.90" → 14.90% ao ano
                    $annualRate = floatval(str_replace(',', '.', $last['valor'])) / 100;

                    // salva no cache local
                    CdiRate::create([
                        'date'        => $iso,
                        'annual_rate' => $annualRate,
                    ]);

                    return $annualRate;
                }
            }
        } catch (\Exception $e) {
            // Falha de API: continua para fallback
        }

        // 3) Fallback — caso API falhe e não exista cache
        return 0.1080; // 10,80% a.a. (exemplo)
    }

    /**
     * Converte a taxa anual para taxa diária (252 dias úteis).
     */
    public function dailyRate(Carbon $date): float
    {
        $annual = $this->getAnnualRate($date);

        // converter anual para diária
        return pow(1 + $annual, 1 / 252) - 1;
    }
}
