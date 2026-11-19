<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\InventoryService;
use App\Services\ProcurementCache;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request, SearchService $search)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:255',
            'stock' => 'nullable|in:low,out', 'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ]);

        return view('products.index', [
            'products' => $search->products($filters)->paginate(15)->withQueryString(),
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.create', ['product' => new Product(), 'suppliers' => Supplier::active()->orderBy('company_name')->get()]);
    }

    public function store(ProductRequest $request, InventoryService $inventory)
    {
        DB::transaction(function () use ($request, $inventory) {
            $data = $request->validated();
            $opening = (int) $data['quantity'];
            $data['quantity'] = 0;
            $this->checkSupplierChange($request, $data, null);
            $product = Product::create($data);
            $this->ensureAgreement($product);

            if ($opening > 0) {
                $inventory->record($product->id, 'in', $opening, $request->user(), 'Opening inventory');
            }

            ProcurementCache::clear();
        });

        return to_route('products.index')->with('success', 'Product added successfully.');
    }

    public function show(Product $product, Request $request)
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $transactions = $product->transactions()->with('user')
            ->when($request->from, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->to, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->recent()->paginate(20)->withQueryString();

        return view('products.show', compact('product', 'transactions'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', ['product' => $product, 'suppliers' => Supplier::active()->orderBy('company_name')->get()]);
    }

    public function update(ProductRequest $request, Product $product, InventoryService $inventory)
    {
        DB::transaction(function () use ($request, $product, $inventory) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $data = $request->validated();
            $target = (int) $data['quantity'];
            unset($data['quantity']);
            $this->checkSupplierChange($request, $data, $product->supplier_id);

            if ($target !== $product->quantity) {
                Gate::authorize('manage-inventory');
            }

            $product->update($data);
            $this->ensureAgreement($product);

            if ($target !== $product->quantity) {
                $inventory->adjust($product->id, $target, $request->user(), 'Product edit correction');
            }

            ProcurementCache::clear();
        });

        return to_route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        Gate::authorize('manage-inventory');

        if ($product->purchaseOrderItems()->whereHas('purchaseOrder', fn ($query) => $query->active())->exists()) {
            throw ValidationException::withMessages(['product' => 'Complete or cancel active purchase orders before archiving this product.']);
        }

        $product->delete();
        ProcurementCache::clear();

        return to_route('products.index')->with('success', 'Product archived. Stock history is preserved.');
    }

    private function checkSupplierChange(Request $request, array &$data, ?int $previous): void
    {
        if (array_key_exists('supplier_id', $data) && (int) $data['supplier_id'] !== (int) $previous) {
            abort_unless($request->user()->isManager(), 403);
        }

        if (!empty($data['supplier_id'])) {
            $data['supplier'] = Supplier::active()->findOrFail($data['supplier_id'])->company_name;
        }
    }

    private function ensureAgreement(Product $product): void
    {
        if ($product->supplier_id && !$product->suppliers()->where('suppliers.id', $product->supplier_id)->exists()) {
            $product->suppliers()->attach($product->supplier_id, [
                'unit_cost' => max(0.01, round((float) $product->unit_price * .75, 2)),
                'minimum_order_quantity' => 1, 'package_size' => 1,
                'lead_time_days' => $product->supplierRecord->lead_time_days,
            ]);
        }
    }
}
