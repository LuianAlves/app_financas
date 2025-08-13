<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessRecurrences;

Schedule::job(new ProcessRecurrences)->dailyAt('03:10');
