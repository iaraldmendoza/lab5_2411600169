@if($product->quantity===0)<span class="badge out">Out of stock</span>
@elseif($product->isLowStock())<span class="badge low">Low stock</span>
@else<span class="badge good">In stock</span>@endif
