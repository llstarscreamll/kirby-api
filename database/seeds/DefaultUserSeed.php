<?php

use Illuminate\Database\Seeder;
use Kirby\Users\Models\User;

class DefaultUserSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate(['email' => 'admin@pascal.com'], [
            'first_name' => 'Pascal',
            'last_name' => 'Admin',
            'phone_number' => '+573001234567',
            'email' => 'admin@pascal.com',
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole('admin');
    }
}
