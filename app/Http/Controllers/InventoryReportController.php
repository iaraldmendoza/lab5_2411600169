<?php

namespace App\Http\Controllers;

use App\Models\Product;

class InventoryReportController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->get();

        $totalProducts = $products->count();

        $availableStock = $products->sum('quantity');

        $lowStock = $products->filter(function ($product) {
            return $product->quantity > 0 &&
                $product->quantity <= $product->reorder_level;
        })->count();

        $outOfStock = $products->where('quantity', 0)->count();

        $inventoryValue = $products->sum(function ($product) {
            return $product->quantity * $product->unit_price;
        });

        return view('reports.inventory', compact(
            'products',
            'totalProducts',
            'availableStock',
            'lowStock',
            'outOfStock',
            'inventoryValue'
        ));
    }
}
