<?php
namespace App\Http\Controllers;
use App\Models\{Product,InventoryTransaction};
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
 public function index(){
  $totalProducts=Product::count();$lowStock=Product::lowStock()->count();$outOfStock=Product::outOfStock()->count();
  $inventoryValue=Product::sum(DB::raw('quantity * unit_price'));$lowProducts=Product::lowStock()->orderBy('quantity')->get();
  $recentTransactions=InventoryTransaction::with(['product','user'])->recent()->limit(10)->get();
  return view('dashboard',compact('totalProducts','lowStock','outOfStock','inventoryValue','lowProducts','recentTransactions'));
 }
}
