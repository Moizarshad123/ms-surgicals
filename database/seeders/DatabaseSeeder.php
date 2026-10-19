<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin login user
        User::updateOrCreate(
            ['email' => 'admin@tgsurgicals.com'],
            [
                'name' => 'Saifullah',
                'password' => Hash::make('admin@123'),
            ]
        );
    }
}
