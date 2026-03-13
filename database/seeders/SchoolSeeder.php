<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = config('app.branches');

        foreach ($branches as $slug => $data) {
            $phones = $data['phones'] ?? '';
            // Split comma-separated phones into an array and trim whitespace
            $phoneArray = array_filter(array_map('trim', explode(',', $phones)));

            School::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'address' => $data['address'] ?? null,
                    'contact_phone' => $phoneArray,
                    'contact_email' => Str::slug($data['name']).'@chrisland.org',
                    'is_active' => true,
                ]
            );
        }
    }
}
