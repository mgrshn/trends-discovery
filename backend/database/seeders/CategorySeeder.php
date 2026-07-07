<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Mapping of ET Pro categories to Google Trends category IDs
        // Google IDs: https://trends.google.com/trends/api/explore/pickers/category
        $categories = [
            ['name' => 'Business',                  'ids' => [12]],
            ['name' => 'Technology',                'ids' => [5]],
            ['name' => 'Health & Wellness',         'ids' => [45]],
            ['name' => 'Finance',                   'ids' => [7]],
            ['name' => 'Food & Beverage',           'ids' => [71]],
            ['name' => 'Entertainment',             'ids' => [3]],
            ['name' => 'Fashion',                   'ids' => [185]],
            ['name' => 'Beauty',                    'ids' => [44]],
            ['name' => 'Home',                      'ids' => [11]],
            ['name' => 'Lifestyle & Culture',       'ids' => [22]],
            ['name' => 'Retail',                    'ids' => [68]],
            ['name' => 'Marketing',                 'ids' => [784]],
            ['name' => 'Productivity',              'ids' => [958]],
            ['name' => 'Cybersecurity',             'ids' => [1227]],
            ['name' => 'Human Resources',           'ids' => [434]],
            ['name' => 'Legal',                     'ids' => [19]],
            ['name' => 'Real Estate',               'ids' => [29]],
            ['name' => 'Supply Chain & Logistics',  'ids' => [328]],
            ['name' => 'Industry',                  'ids' => [435]],
            ['name' => 'Workplace',                 'ids' => [958]],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->upsert(
                [
                    'name'                => $cat['name'],
                    'google_category_ids' => json_encode($cat['ids']),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
                ['name'],
                ['google_category_ids', 'updated_at'],
            );
        }
    }
}
