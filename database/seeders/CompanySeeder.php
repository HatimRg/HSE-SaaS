<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'Demo Construction Company',
            'domain' => 'demo.hse-saas.com',
            'email' => 'admin@demo.com',
            'phone' => '+212 522 123 456',
            'address' => '123 Construction Ave, Casablanca, Morocco',
            'registration_number' => 'RC-123456',
            'settings' => [
                'language' => 'fr',
                'timezone' => 'Africa/Casablanca',
                'date_format' => 'd/m/Y',
            ],
            'color_primary_light' => '#3b82f6',
            'color_primary_dark' => '#1d4ed8',
            'color_background_light' => '#ffffff',
            'color_background_dark' => '#0f172a',
            'color_accent' => '#f59e0b',
            'is_active' => true,
            'subscription_plan' => 'enterprise',
            'subscription_expires_at' => now()->addYear(),
        ]);

        Company::create([
            'name' => 'Test Company',
            'domain' => 'test.hse-saas.com',
            'email' => 'admin@test.com',
            'is_active' => true,
            'subscription_plan' => 'basic',
            'subscription_expires_at' => now()->addMonths(3),
        ]);
    }
}
