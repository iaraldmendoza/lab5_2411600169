<?php

namespace App\Http\Controllers;

use App\Models\{Product,InventoryTransaction};
use App\Http\Resources\{ProductResource,TransactionResource};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryApiController extends Controller
{
    public function stats(Request $r)
    {
        return response()->json(['data' => [
         'total_products' => Product::count(),'low_stock' => Product::lowStock()->count(),'out_of_stock' => Product::outOfStock()->count(),
         'inventory_value' => (float)Product::sum(DB::raw('quantity * unit_price')),'unread_notifications' => $r->user()->unreadNotifications()->count()
        ],'meta' => ['updated_at' => now()->toIso8601String()]])->header('Cache-Control', 'no-store');
    }
    public function lowStock()
    {
        return ProductResource::collection(Product::lowStock()->orderBy('quantity')->get());
    }
    public function recent()
    {
        return TransactionResource::collection(InventoryTransaction::with(['product','user'])->recent()->limit(10)->get());
    }
    public function product(Product $product)
    {
        return new ProductResource($product);
    }
    public function read(Request $r, string $notification)
    {
        $r->user()->notifications()->findOrFail($notification)->markAsRead();
        return back()->with('success', 'Notification marked as read.');
    }
}
