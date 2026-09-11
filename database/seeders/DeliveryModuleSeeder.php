<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : database/seeders/DeliveryModuleSeeder.php
 * Purpose: Demo API credentials for the Delivery REST web service.
 *
 * Only SHA-256 token hashes are stored in the database. The plain demo tokens
 * are printed/separately documented for local tutor demonstrations only.
 */

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DeliveryModuleSeeder extends Seeder
{
    public const ADMIN_API_TOKEN = 'foodlink-delivery-admin-demo-token';
    public const VOLUNTEER_API_TOKEN = 'foodlink-delivery-volunteer-demo-token';

    public function run(): void
    {
        $tokens = [
            'admin@foodlink.test' => self::ADMIN_API_TOKEN,
            'volunteer@foodlink.test' => self::VOLUNTEER_API_TOKEN,
        ];

        foreach ($tokens as $email => $plainToken) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                continue;
            }

            $user->api_token = hash('sha256', $plainToken);
            $user->save();
        }

        $this->command?->info('Module 4 admin API token: '.self::ADMIN_API_TOKEN);
        $this->command?->info('Module 4 volunteer API token: '.self::VOLUNTEER_API_TOKEN);
    }
}
