<?php
/* Lau Ke Xin */

namespace App\Services;

use App\Models\DonationStatusHistory;
use App\Models\FoodDonation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonationExpiryService
{
    /**
     * Mark overdue AVAILABLE donations as EXPIRED.
     * Returns the number of donations updated.
     */
    public function expireOverdueDonations(): int
    {
        $changed = 0;

        FoodDonation::query()
            ->with('donor')
            ->where('donation_status', 'AVAILABLE')
            ->where('expiry_datetime', '<=', now())
            ->orderBy('donation_id')
            ->chunkById(100, function ($donations) use (&$changed) {
                foreach ($donations as $donation) {
                    if ($this->expireOne($donation)) {
                        $changed++;
                    }
                }
            }, 'donation_id');

        return $changed;
    }

    private function expireOne(FoodDonation $donation): bool
    {
        return (bool) DB::transaction(function () use ($donation) {
            /** @var FoodDonation|null $locked */
            $locked = FoodDonation::query()
                ->whereKey($donation->donation_id)
                ->lockForUpdate()
                ->first();

            // Re-check inside the lock so concurrent runs stay idempotent.
            if (
                $locked === null
                || $locked->donation_status !== 'AVAILABLE'
                || $locked->expiry_datetime === null
                || $locked->expiry_datetime->isFuture()
            ) {
                return false;
            }

            $ownerUserId = $locked->donor?->user_id
                ?? $donation->donor?->user_id;

            if ($ownerUserId === null) {
                Log::warning('Donation expiry skipped: donor user_id missing.', [
                    'donation_id' => $locked->donation_id,
                ]);

                return false;
            }

            $locked->donation_status = 'EXPIRED';
            $locked->save();

            DonationStatusHistory::create([
                'donation_id' => $locked->donation_id,
                'old_status' => 'AVAILABLE',
                'new_status' => 'EXPIRED',
                'changed_by' => $ownerUserId,
                'remarks' => 'Automatically expired (past expiry datetime)',
            ]);

            Log::info('Donation automatically expired.', [
                'donation_id' => $locked->donation_id,
                'changed_by' => $ownerUserId,
            ]);

            return true;
        });
    }
}
