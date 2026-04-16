<?php

namespace Database\Seeders;


use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RevampPermissionsSeeder::class
        ]);
    }

    // private function seedUsers(): array
    // {
    //     // Create Super Admin
    //     $admin = User::updateOrCreate(
    //         ['username' => config('app.admin_username', 'admin_root')],
    //         [
    //             'name' => 'System Admin',
    //             'email' => config('app.admin_email', 'admin@chrisland.org'),
    //             'password' => bcrypt(config('app.admin_password', 'password')),
    //         ]
    //     );
    //     $admin->syncRoles(['super_admin']);

    //     return [$admin];
    // }


}
