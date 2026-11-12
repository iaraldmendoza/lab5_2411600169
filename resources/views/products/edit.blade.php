<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Product - Bulad Inventory
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <h3 class="text-xl font-bold mb-6">
                    Edit Product Information
                </h3>

                @if ($errors->any())
                    <div style="background:#fee2e2; color:#991b1b;
                                padding:15px; margin-bottom:20px;
                                border-radius:6px;">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('products.update', $product) }}"
                      method="POST">

                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block mb-2">Product Name</label>
                        <input type="text" name="name"
                               value="{{ old('name', $product->name) }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">SKU</label>
                        <input type="text" name="sku"
                               value="{{ old('sku', $product->sku) }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Description</label>
                        <textarea name="description"
                                  class="w-full border-gray-300 rounded"
                                  rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Category</label>
                        <input type="text" name="category"
                               value="{{ old('category', $product->category) }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Quantity</label>
                        <input type="number" name="quantity"
                               value="{{ old('quantity', $product->quantity) }}"
                               min="0"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Reorder Level</label>
                        <input type="number" name="reorder_level"
                               value="{{ old('reorder_level', $product->reorder_level) }}"
                               min="0"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Unit Price (₱)</label>
                        <input type="number" name="unit_price"
                               value="{{ old('unit_price', $product->unit_price) }}"
                               min="0" step="0.01"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <div class="mb-6">
                        <label class="block mb-2">Supplier</label>
                        <input type="text" name="supplier"
                               value="{{ old('supplier', $product->supplier) }}"
                               class="w-full border-gray-300 rounded">
                    </div>

                    <div style="display:flex; gap:12px; margin-top:25px;">

                        <button type="submit"
                                style="background:#2563eb;
                                       color:white;
                                       padding:12px 24px;
                                       border:none;
                                       border-radius:6px;
                                       font-weight:bold;
                                       cursor:pointer;">
                            Update Product
                        </button>

                        <a href="{{ route('products.index') }}"
                           style="background:#6b7280;
                                  color:white;
                                  padding:12px 24px;
                                  border-radius:6px;
                                  text-decoration:none;">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>