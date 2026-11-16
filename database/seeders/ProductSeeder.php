<?php
namespace Database\Seeders;
use App\Models\Product;
use Illuminate\Database\Seeder;
class ProductSeeder extends Seeder {
    public function run(): void {
        $products=[
            ['name'=>'Kiln Dried Lumber 2 x 4','sku'=>'LUM-001','category'=>'Lumber','quantity'=>80,'reorder_level'=>15,'unit_price'=>185,'supplier'=>'Timberline Supply','description'=>'Hardware store sample product'],
            ['name'=>'Marine Plywood 12 mm','sku'=>'LUM-002','category'=>'Lumber','quantity'=>12,'reorder_level'=>15,'unit_price'=>920,'supplier'=>'Timberline Supply','description'=>'Hardware store sample product'],
            ['name'=>'Coco Lumber 2 x 2','sku'=>'LUM-003','category'=>'Lumber','quantity'=>50,'reorder_level'=>10,'unit_price'=>95,'supplier'=>'Timberline Supply','description'=>'Hardware store sample product'],
            ['name'=>'Common Nails 1 kg','sku'=>'HDW-001','category'=>'Hardware','quantity'=>45,'reorder_level'=>10,'unit_price'=>85,'supplier'=>'Metro Hardware','description'=>'Hardware store sample product'],
            ['name'=>'Concrete Screws 100 pcs','sku'=>'HDW-002','category'=>'Hardware','quantity'=>5,'reorder_level'=>10,'unit_price'=>145,'supplier'=>'Metro Hardware','description'=>'Hardware store sample product'],
            ['name'=>'Door Hinge Pair','sku'=>'HDW-003','category'=>'Hardware','quantity'=>30,'reorder_level'=>8,'unit_price'=>120,'supplier'=>'Metro Hardware','description'=>'Hardware store sample product'],
            ['name'=>'Claw Hammer 16 oz','sku'=>'TLS-001','category'=>'Tools','quantity'=>18,'reorder_level'=>5,'unit_price'=>350,'supplier'=>'Builder Tools','description'=>'Hardware store sample product'],
            ['name'=>'Cordless Drill 12V','sku'=>'TLS-002','category'=>'Tools','quantity'=>0,'reorder_level'=>3,'unit_price'=>2450,'supplier'=>'Builder Tools','description'=>'Hardware store sample product'],
            ['name'=>'Measuring Tape 5 m','sku'=>'TLS-003','category'=>'Tools','quantity'=>22,'reorder_level'=>5,'unit_price'=>180,'supplier'=>'Builder Tools','description'=>'Hardware store sample product'],
            ['name'=>'THHN Wire 2 mm 150 m','sku'=>'ELE-001','category'=>'Electrical','quantity'=>8,'reorder_level'=>3,'unit_price'=>1850,'supplier'=>'Bright Electrical','description'=>'Hardware store sample product'],
            ['name'=>'LED Bulb 9W','sku'=>'ELE-002','category'=>'Electrical','quantity'=>4,'reorder_level'=>10,'unit_price'=>95,'supplier'=>'Bright Electrical','description'=>'Hardware store sample product'],
            ['name'=>'Duplex Outlet','sku'=>'ELE-003','category'=>'Electrical','quantity'=>36,'reorder_level'=>10,'unit_price'=>75,'supplier'=>'Bright Electrical','description'=>'Hardware store sample product'],
            ['name'=>'PVC Pipe 1 inch 3 m','sku'=>'PLB-001','category'=>'Plumbing','quantity'=>28,'reorder_level'=>10,'unit_price'=>160,'supplier'=>'Flow Plumbing','description'=>'Hardware store sample product'],
            ['name'=>'Brass Faucet Half Inch','sku'=>'PLB-002','category'=>'Plumbing','quantity'=>0,'reorder_level'=>5,'unit_price'=>285,'supplier'=>'Flow Plumbing','description'=>'Hardware store sample product'],
            ['name'=>'PVC Elbow 1 inch','sku'=>'PLB-003','category'=>'Plumbing','quantity'=>60,'reorder_level'=>15,'unit_price'=>18,'supplier'=>'Flow Plumbing','description'=>'Hardware store sample product'],
            ['name'=>'Latex White Paint 4 L','sku'=>'PNT-001','category'=>'Paint','quantity'=>16,'reorder_level'=>5,'unit_price'=>680,'supplier'=>'Colorworks','description'=>'Hardware store sample product'],
            ['name'=>'Red Oxide Primer 1 L','sku'=>'PNT-002','category'=>'Paint','quantity'=>3,'reorder_level'=>5,'unit_price'=>210,'supplier'=>'Colorworks','description'=>'Hardware store sample product'],
            ['name'=>'Paint Roller 9 inch','sku'=>'PNT-003','category'=>'Paint','quantity'=>24,'reorder_level'=>6,'unit_price'=>135,'supplier'=>'Colorworks','description'=>'Hardware store sample product'],
        ];
        foreach($products as $data) {
            $product=Product::firstOrCreate(['sku'=>$data['sku']],$data);
            if($product->wasRecentlyCreated && $product->quantity>0)
                $product->transactions()->create(['type'=>'in','quantity'=>$product->quantity,'reference'=>'Sample opening stock']);
        }
    }
}
