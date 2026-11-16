<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model {
    public const CATEGORIES = ['Lumber','Hardware','Tools','Electrical','Plumbing','Paint'];
    protected $fillable = ['name','sku','description','category','quantity','reorder_level','unit_price','supplier'];
    protected function casts(): array {
        return ['quantity'=>'integer','reorder_level'=>'integer','unit_price'=>'decimal:2'];
    }
    public function transactions(): HasMany { return $this->hasMany(InventoryTransaction::class); }
    public function isLowStock(): bool { return $this->quantity <= $this->reorder_level; }
}
