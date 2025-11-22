<?php

namespace App\Services;

use App\Models\CdiRate;
use Carbon\Carbon;

class CdiService
{
    public function getAnnualRate(Carbon $date): float
    {
        $row = CdiRate::where('date', $date->toDateString())->first();

        // se não tiver na tabela, você pode:
        // - buscar de API no futuro
        // - ou usar uma taxa fixa de fallback
        return $row->annual_rate ?? 0.1080; // 10,80% a.a. default
    }

    public function dailyRate(Carbon $date): float
    {
        $annual = $this->getAnnualRate($date);

        // 252 dias úteis
        return pow(1 + $annual, 1 / 252) - 1;
    }
}
