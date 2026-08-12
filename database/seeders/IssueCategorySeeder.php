<?php

namespace Database\Seeders;

use App\Models\IssueCategory;
use Illuminate\Database\Seeder;

class IssueCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Card Lost',
            'User ID and Password Not Found',
            'login invalid',
            'NID correction',
            'Payment Related Issue',
        ];

        foreach ($categories as $categoryName) {
            IssueCategory::firstOrCreate(['name' => $categoryName]);
        }
    }
}
