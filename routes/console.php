<?php
/**
 * FoodLink

 */

use Illuminate\Support\Facades\Schedule;

Schedule::command('foodlink:refresh-requests')->hourly();
Schedule::command('foodlink:expire-donations')->everyMinute();
