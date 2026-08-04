<?php
/**
 * FoodLink
 * File: routes/console.php
 *
 * Scheduled task for module 3.3 Food Request Management added by NG JIA QIN:
 * every hour, food requests whose fulfilment deadline has passed are flagged as
 * expired so the dashboard always shows the true status.
 */

use Illuminate\Support\Facades\Schedule;

Schedule::command('foodlink:refresh-requests')->hourly();
