<?php
/* Lau Ke Xin */

namespace Database\Seeders;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecondTestDonorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'seconddonor@foodlink.test'],
            [
                'full_name' => 'Second Test Food Donor',
                'password_hash' => Hash::make('password'),
                'role' => User::ROLE_FOOD_DONOR,
                'account_status' => User::STATUS_ACTIVE,
            ]
        );

        PartnerProfile::query()->firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'address' => '456 Second Donor Avenue',
                'verification_status' => 'APPROVED',
            ]
        );
    }
}
