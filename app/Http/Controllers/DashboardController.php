<?php

namespace App\Http\Controllers;

use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();

        $lowStock = Product::where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();

        $outOfStock = Product::where('quantity', 0)->count();

        $inventoryValue = Product::all()->sum(function ($product) {
            return $product->quantity * $product->unit_price;
        });

        $recentProducts = Product::latest()->take(5)->get();

        return view('dashboard', compact(
            'totalProducts',
            'lowStock',
            'outOfStock',
            'inventoryValue',
            'recentProducts'
        ));
    }
}
