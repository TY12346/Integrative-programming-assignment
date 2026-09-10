<?php

namespace Database\Seeders;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local test Food Donor for Module 3.2 when DatabaseSeeder cannot be rerun.
 *
 * Run with: php artisan db:seed --class=TestDonorSeeder
 */
class TestDonorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'testdonor@foodlink.test'],
            [
                'full_name' => 'Test Food Donor',
                'password_hash' => Hash::make('password'),
                'role' => User::ROLE_FOOD_DONOR,
                'account_status' => User::STATUS_ACTIVE,
            ]
        );

        PartnerProfile::query()->firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'address' => '123 Test Donor Street',
                'verification_status' => 'APPROVED',
            ]
        );
    }
}
