<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add New Product - Bulad Inventory
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <h3 class="text-xl font-bold mb-6">
                    Product Information
                </h3>

                <!-- Validation Errors -->
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

                <!-- Add Product Form -->
                <form action="{{ route('products.store') }}"
                      method="POST">

                    @csrf

                    <!-- Product Name -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            Product Name
                        </label>

                        <input type="text"
                               name="name"
                               value="{{ old('name') }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <!-- SKU -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            SKU
                        </label>

                        <input type="text"
                               name="sku"
                               value="{{ old('sku') }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            Description
                        </label>

                        <textarea name="description"
                                  class="w-full border-gray-300 rounded"
                                  rows="3">{{ old('description') }}</textarea>
                    </div>

                    <!-- Category -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            Category
                        </label>

                        <input type="text"
                               name="category"
                               value="{{ old('category') }}"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <!-- Quantity -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            Quantity
                        </label>

                        <input type="number"
                               name="quantity"
                               value="{{ old('quantity', 0) }}"
                               min="0"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <!-- Reorder Level -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            Reorder Level
                        </label>

                        <input type="number"
                               name="reorder_level"
                               value="{{ old('reorder_level', 5) }}"
                               min="0"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <!-- Unit Price -->
                    <div class="mb-4">
                        <label class="block mb-2 font-medium">
                            Unit Price (₱)
                        </label>

                        <input type="number"
                               name="unit_price"
                               value="{{ old('unit_price') }}"
                               min="0"
                               step="0.01"
                               class="w-full border-gray-300 rounded"
                               required>
                    </div>

                    <!-- Supplier -->
                    <div class="mb-6">
                        <label class="block mb-2 font-medium">
                            Supplier
                        </label>

                        <input type="text"
                               name="supplier"
                               value="{{ old('supplier') }}"
                               class="w-full border-gray-300 rounded">
                    </div>

                    <!-- FIXED SAVE AND CANCEL BUTTONS -->
                    <div style="display:flex; gap:12px;
                                margin-top:25px;">

                        <button type="submit"
                                style="background-color:#2563eb;
                                       color:#ffffff;
                                       padding:12px 24px;
                                       border:none;
                                       border-radius:6px;
                                       font-weight:bold;
                                       cursor:pointer;">
                            Save Product
                        </button>

                        <a href="{{ route('products.index') }}"
                           style="background-color:#6b7280;
                                  color:#ffffff;
                                  padding:12px 24px;
                                  border-radius:6px;
                                  text-decoration:none;
                                  font-weight:bold;">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>