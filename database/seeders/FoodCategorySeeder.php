<?php
/* Lau Ke Xin */

namespace Database\Seeders;

use App\Models\FoodCategory;
use Illuminate\Database\Seeder;

class FoodCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Rice', 'description' => 'Staple food'],
            ['category_name' => 'Vegetables', 'description' => 'Fresh produce'],
            ['category_name' => 'Bakery', 'description' => 'Bread and pastries'],
            ['category_name' => 'Canned Food', 'description' => 'Shelf-stable items'],
        ];

        foreach ($categories as $category) {
            FoodCategory::query()->firstOrCreate(
                ['category_name' => $category['category_name']],
                ['description' => $category['description']]
            );
        }
    }
}
