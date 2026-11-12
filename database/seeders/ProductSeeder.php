<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Bulad Danggit',
                'sku' => 'BUL002',
                'description' => 'Dried salted danggit fish',
                'category' => 'Dried Seafood',
                'quantity' => 15,
                'reorder_level' => 5,
                'unit_price' => 250.00,
                'supplier' => 'Pishport Gensan',
            ],
            [
                'name' => 'Bulad Pusit',
                'sku' => 'BUL003',
                'description' => 'Dried squid',
                'category' => 'Dried Seafood',
                'quantity' => 8,
                'reorder_level' => 5,
                'unit_price' => 350.00,
                'supplier' => 'Pishport Gensan',
            ],
            [
                'name' => 'Bulad Dilis',
                'sku' => 'BUL004',
                'description' => 'Dried anchovies',
                'category' => 'Dried Seafood',
                'quantity' => 3,
                'reorder_level' => 5,
                'unit_price' => 180.00,
                'supplier' => 'Pishport Gensan',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
