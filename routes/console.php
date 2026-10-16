<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:process-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
