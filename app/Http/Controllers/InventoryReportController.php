<?php
namespace App\Http\Controllers;
use App\Models\Product;
class InventoryReportController extends Controller {
    public function index() {
        $products=Product::orderBy('category')->orderBy('name')->get();
        $inventoryValue=$products->sum(fn($p)=>$p->quantity*$p->unit_price);
        return view('reports.inventory',compact('products','inventoryValue'));
    }
    public function csv() {
        $products=Product::orderBy('name')->get();
        return response()->streamDownload(function() use($products) {
            $out=fopen('php://output','w');
            fputcsv($out,['Name','SKU','Category','Quantity','Reorder level','Unit price','Supplier']);
            foreach($products as $p) {
                $row=[$p->name,$p->sku,$p->category,$p->quantity,$p->reorder_level,$p->unit_price,$p->supplier];
                $row=array_map(fn($v)=>is_string($v)&&preg_match('/^[=+@\\-\\t\\r]/',$v) ? "'".$v : $v,$row);
                fputcsv($out,$row);
            }
            fclose($out);
        },'hardware-inventory.csv',['Content-Type'=>'text/csv']);
    }
}
