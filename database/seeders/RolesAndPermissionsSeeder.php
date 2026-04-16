<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Backward-compatible wrapper for older deploy/manual seed commands.
     */
    public function run(): void
    {
        $this->call(RevampPermissionsSeeder::class);
    }
}
