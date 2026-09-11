<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Services/DeliveryNoteSanitizer.php
 * Purpose: Defence-in-depth sanitisation for user-supplied delivery notes.
 *
 * Delivery notes are intentionally stored as plain text. All HTML tags are
 * removed before storage, then Blade's {{ }} encoding is used on output.
 */

namespace App\Services;

class DeliveryNoteSanitizer
{
    public function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
