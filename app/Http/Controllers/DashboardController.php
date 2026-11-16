<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
    public function index() {
        $totalProducts=Product::count();
        $lowStock=Product::whereColumn('quantity','<=','reorder_level')->count();
        $outOfStock=Product::where('quantity',0)->count();
        $inventoryValue=Product::sum(DB::raw('quantity * unit_price'));
        $recentProducts=Product::latest('id')->take(5)->get();
        $categories=Product::selectRaw('category, SUM(quantity) as units')->groupBy('category')->orderBy('category')->get();
        return view('dashboard',compact('totalProducts','lowStock','outOfStock','inventoryValue','recentProducts','categories'));
    }
}
