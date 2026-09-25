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
        User::firstOrCreate(
            ['email' => 'admin@mssurgicals.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Sample categories
        $surgical = Category::firstOrCreate(['name' => 'Surgical Instruments']);
        $disposable = Category::firstOrCreate(['name' => 'Disposables']);
        $diagnostic = Category::firstOrCreate(['name' => 'Diagnostic Equipment']);

        // Sample products
        $products = [
            ['name' => 'Surgical Scissors', 'category_id' => $surgical->id, 'code' => 'SI-001', 'purchase_price' => 150, 'sale_price' => 250, 'stock' => 40],
            ['name' => 'Forceps', 'category_id' => $surgical->id, 'code' => 'SI-002', 'purchase_price' => 120, 'sale_price' => 200, 'stock' => 30],
            ['name' => 'Disposable Syringe 5ml (Box)', 'category_id' => $disposable->id, 'code' => 'DP-001', 'purchase_price' => 300, 'sale_price' => 450, 'stock' => 3],
            ['name' => 'Surgical Gloves (Box)', 'category_id' => $disposable->id, 'code' => 'DP-002', 'purchase_price' => 500, 'sale_price' => 700, 'stock' => 4],
            ['name' => 'Digital Thermometer', 'category_id' => $diagnostic->id, 'code' => 'DE-001', 'purchase_price' => 400, 'sale_price' => 600, 'stock' => 20],
            ['name' => 'Blood Pressure Monitor', 'category_id' => $diagnostic->id, 'code' => 'DE-002', 'purchase_price' => 2500, 'sale_price' => 3500, 'stock' => 10],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['code' => $product['code']], $product);
        }
    }
}
