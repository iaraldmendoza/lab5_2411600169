<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Kiln Dried Lumber 2 x 4','sku' => 'LUM-001','category' => 'Lumber','quantity' => 80,'reorder_level' => 15,'unit_price' => 185,'supplier' => 'Timberline Supply','description' => 'Kiln-dried lumber for framing, shelving, and general carpentry.'],
            ['name' => 'Marine Plywood 12 mm','sku' => 'LUM-002','category' => 'Lumber','quantity' => 12,'reorder_level' => 15,'unit_price' => 920,'supplier' => 'Timberline Supply','description' => 'Moisture-resistant plywood for cabinets, partitions, and construction work.'],
            ['name' => 'Coco Lumber 2 x 2','sku' => 'LUM-003','category' => 'Lumber','quantity' => 50,'reorder_level' => 10,'unit_price' => 95,'supplier' => 'Timberline Supply','description' => 'Coconut lumber for temporary bracing and light construction.'],
            ['name' => 'Common Nails 1 kg','sku' => 'HDW-001','category' => 'Hardware','quantity' => 45,'reorder_level' => 10,'unit_price' => 85,'supplier' => 'Metro Hardware','description' => 'General-purpose steel nails supplied in a one-kilogram pack.'],
            ['name' => 'Concrete Screws 100 pcs','sku' => 'HDW-002','category' => 'Hardware','quantity' => 5,'reorder_level' => 10,'unit_price' => 145,'supplier' => 'Metro Hardware','description' => 'Concrete fastening screws supplied in a pack of 100.'],
            ['name' => 'Door Hinge Pair','sku' => 'HDW-003','category' => 'Hardware','quantity' => 30,'reorder_level' => 8,'unit_price' => 120,'supplier' => 'Metro Hardware','description' => 'Paired door hinges for interior door installation.'],
            ['name' => 'Claw Hammer 16 oz','sku' => 'TLS-001','category' => 'Tools','quantity' => 18,'reorder_level' => 5,'unit_price' => 350,'supplier' => 'Builder Tools','description' => '16-ounce claw hammer for driving and removing nails.'],
            ['name' => 'Cordless Drill 12V','sku' => 'TLS-002','category' => 'Tools','quantity' => 0,'reorder_level' => 3,'unit_price' => 2450,'supplier' => 'Builder Tools','description' => '12-volt cordless drill for drilling and screw fastening.'],
            ['name' => 'Measuring Tape 5 m','sku' => 'TLS-003','category' => 'Tools','quantity' => 22,'reorder_level' => 5,'unit_price' => 180,'supplier' => 'Builder Tools','description' => 'Five-meter retractable tape for measuring materials and work areas.'],
            ['name' => 'THHN Wire 2 mm 150 m','sku' => 'ELE-001','category' => 'Electrical','quantity' => 8,'reorder_level' => 3,'unit_price' => 1850,'supplier' => 'Bright Electrical','description' => '150-meter roll of THHN electrical wire for building installations.'],
            ['name' => 'LED Bulb 9W','sku' => 'ELE-002','category' => 'Electrical','quantity' => 4,'reorder_level' => 10,'unit_price' => 95,'supplier' => 'Bright Electrical','description' => 'Nine-watt LED bulb for household and commercial lighting.'],
            ['name' => 'Duplex Outlet','sku' => 'ELE-003','category' => 'Electrical','quantity' => 36,'reorder_level' => 10,'unit_price' => 75,'supplier' => 'Bright Electrical','description' => 'Duplex electrical outlet for standard wall installations.'],
            ['name' => 'PVC Pipe 1 inch 3 m','sku' => 'PLB-001','category' => 'Plumbing','quantity' => 28,'reorder_level' => 10,'unit_price' => 160,'supplier' => 'Flow Plumbing','description' => 'Three-meter PVC pipe for water supply and plumbing projects.'],
            ['name' => 'Brass Faucet Half Inch','sku' => 'PLB-002','category' => 'Plumbing','quantity' => 0,'reorder_level' => 5,'unit_price' => 285,'supplier' => 'Flow Plumbing','description' => 'Half-inch brass faucet for household water fixtures.'],
            ['name' => 'PVC Elbow 1 inch','sku' => 'PLB-003','category' => 'Plumbing','quantity' => 60,'reorder_level' => 15,'unit_price' => 18,'supplier' => 'Flow Plumbing','description' => 'One-inch PVC elbow for joining pipe sections at a right angle.'],
            ['name' => 'Latex White Paint 4 L','sku' => 'PNT-001','category' => 'Paint','quantity' => 16,'reorder_level' => 5,'unit_price' => 680,'supplier' => 'Colorworks','description' => 'Four-liter white latex paint for interior wall finishing.'],
            ['name' => 'Red Oxide Primer 1 L','sku' => 'PNT-002','category' => 'Paint','quantity' => 3,'reorder_level' => 5,'unit_price' => 210,'supplier' => 'Colorworks','description' => 'One-liter red oxide primer for preparing metal surfaces.'],
            ['name' => 'Paint Roller 9 inch','sku' => 'PNT-003','category' => 'Paint','quantity' => 24,'reorder_level' => 6,'unit_price' => 135,'supplier' => 'Colorworks','description' => 'Nine-inch paint roller for applying paint to walls and ceilings.'],
        ];

        $manager = \App\Models\User::where('email', 'manager@facinalhardware.test')->firstOrFail();
        foreach ($products as $data) {
            $opening = (int)$data['quantity'];
            $data['quantity'] = 0;
            $product = Product::firstOrCreate(['sku' => $data['sku']], $data);
            if ($product->wasRecentlyCreated && $opening > 0) {
                app(\App\Services\InventoryService::class)->record($product->id, 'in', $opening, $manager, 'Opening inventory');
            }
        }
    }
}
