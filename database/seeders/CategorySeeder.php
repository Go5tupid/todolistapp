<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Work', 'color' => '#3b82f6'],
            ['name' => 'Personal', 'color' => '#10b981'],
            ['name' => 'Shopping', 'color' => '#f59e0b'],
            ['name' => 'Health', 'color' => '#ef4444'],
            ['name' => 'Learning', 'color' => '#8b5cf6'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
