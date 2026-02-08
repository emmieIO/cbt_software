<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use Illuminate\Database\Seeder;

class AcademicSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true,
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
        ]);
    }
}
