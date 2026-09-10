<?php
/**
 * FoodLink
 * File: routes/console.php
 *
 * Scheduled tasks:
 * - Module 3.3 (NG JIA QIN): foodlink:refresh-requests — expire overdue food requests.
 * - Module 3.2 (LAU KE XIN): foodlink:expire-donations — persist
 *   AVAILABLE -> EXPIRED for donations past expiry_datetime.
 */

use Illuminate\Support\Facades\Schedule;

Schedule::command('foodlink:refresh-requests')->hourly();
Schedule::command('foodlink:expire-donations')->everyMinute();
