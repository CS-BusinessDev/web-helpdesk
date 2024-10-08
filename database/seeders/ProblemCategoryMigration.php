<?php

namespace Database\Seeders;

use App\Models\ProblemCategory;
use Illuminate\Database\Seeder;

class ProblemCategoryMigration extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProblemCategory::insert([
            [
                'unit_id' => 1,
                'name' => 'SOP',
            ],
            [
                'unit_id' => 1,
                'name' => 'SJ',
            ],
        ]);
    }
}
