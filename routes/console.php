<?php

use App\Jobs\SendDailyDigestJob;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessRecurrences;

Schedule::job(new ProcessRecurrences)->dailyAt('03:10');
Schedule::job(new SendDailyDigestJob)
    ->timezone('America/Sao_Paulo')
    ->dailyAt('15:55');

Schedule::job(new SendDailyDigestJob)->dailyAt('15:53');
