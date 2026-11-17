<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class ProductResource extends JsonResource {
 public function toArray($r):array{return ['id'=>$this->id,'name'=>$this->name,'sku'=>$this->sku,'quantity'=>$this->quantity,'reorder_level'=>$this->reorder_level,'unit_price'=>$this->unit_price,'category'=>$this->category,'status'=>$this->stock_status,'url'=>route('products.show',$this->id)];}
}
