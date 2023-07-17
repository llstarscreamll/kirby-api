<?php

use Illuminate\Database\Seeder;
use Kirby\Users\Models\User;

class DefaultUserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (User::count() > 0) {
            return;
        }

        $user = User::firstOrCreate(['email' => 'admin@pascal.com'], [
            'first_name' => 'Pascal',
            'last_name' => 'Admin',
            'phone_prefix' => '+57',
            'phone_number' => '3001234567',
            'email' => 'admin@pascal.com',
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole('admin');
    }
}
