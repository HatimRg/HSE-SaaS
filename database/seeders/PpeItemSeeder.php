<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PpeItem;
use Illuminate\Database\Seeder;

class PpeItemSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        $items = [
            [
                'name' => 'Safety Helmet (White)',
                'category' => 'head',
                'description' => 'Standard construction safety helmet with ventilation',
                'size_options' => ['S', 'M', 'L', 'XL'],
                'color_options' => ['White', 'Yellow', 'Blue', 'Red'],
                'unit_cost' => 150.00,
                'reorder_level' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Safety Boots S5',
                'category' => 'foot',
                'description' => 'Steel toe safety boots with anti-puncture sole',
                'size_options' => ['38', '39', '40', '41', '42', '43', '44', '45'],
                'color_options' => ['Black'],
                'unit_cost' => 450.00,
                'reorder_level' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Safety Glasses',
                'category' => 'eye_face',
                'description' => 'Clear safety glasses with anti-fog coating',
                'size_options' => ['Universal'],
                'color_options' => ['Clear'],
                'unit_cost' => 85.00,
                'reorder_level' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'High Visibility Vest',
                'category' => 'high_visibility',
                'description' => 'Class 2 high visibility vest with reflective strips',
                'size_options' => ['S', 'M', 'L', 'XL', 'XXL'],
                'color_options' => ['Orange', 'Yellow'],
                'unit_cost' => 120.00,
                'reorder_level' => 25,
                'is_active' => true,
            ],
            [
                'name' => 'Work Gloves (Leather)',
                'category' => 'hand',
                'description' => 'Heavy duty leather work gloves',
                'size_options' => ['S', 'M', 'L', 'XL'],
                'color_options' => ['Brown'],
                'unit_cost' => 75.00,
                'reorder_level' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Ear Defenders',
                'category' => 'hearing',
                'description' => 'Noise reducing ear defenders SNR 30dB',
                'size_options' => ['Universal'],
                'color_options' => ['Yellow', 'Blue'],
                'unit_cost' => 200.00,
                'reorder_level' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Dust Mask FFP2',
                'category' => 'respiratory',
                'description' => 'Disposable dust mask with valve',
                'size_options' => ['Universal'],
                'color_options' => ['White'],
                'unit_cost' => 15.00,
                'reorder_level' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Safety Harness',
                'category' => 'fall_protection',
                'description' => 'Full body safety harness with lanyard',
                'size_options' => ['S/M', 'L/XL'],
                'color_options' => ['Blue/Black'],
                'unit_cost' => 1200.00,
                'reorder_level' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            PpeItem::create([
                ...$item,
                'company_id' => $company->id,
            ]);
        }
    }
}
