<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\CdiRate;
use Carbon\Carbon;

class UpdateCdiRates extends Command
{
    protected $signature = 'cdi:update';
    protected $description = 'Atualiza a taxa CDI diária usando a API do Banco Central';

    public function handle()
    {
        $this->info("🔄 Atualizando CDI...");

        try {
            $url = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados?formato=json&limit=5";

            $res = Http::withHeaders(['Accept' => '*/*'])
                ->timeout(8)
                ->retry(2, 200)
                ->get($url);

            $rows = $res->json();

            if (!is_array($rows) || count($rows) === 0) {
                $this->error("❌ API retornou lista vazia");
                return 1;
            }

            $last = end($rows);

            if (!isset($last['valor'])) {
                $this->error("❌ Formato inesperado da API BCB");
                return 1;
            }

            $annual = floatval(str_replace(',', '.', $last['valor'])) / 100;

            $today = Carbon::now()->toDateString();

            CdiRate::updateOrCreate(
                ['date' => $today],
                ['annual_rate' => $annual]
            );

            $this->info("✅ CDI atualizado! Taxa anual: {$annual}");

            return 0;
        }

        catch (\Exception $e) {
            $this->error("❗ Erro inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
