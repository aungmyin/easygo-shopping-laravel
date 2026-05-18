<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'      => 'Admin',
            'email'     => 'admin@easygo.com',
            'password'  => bcrypt('admin123456'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}
