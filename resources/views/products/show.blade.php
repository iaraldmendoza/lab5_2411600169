<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bulad Inventory - Product Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <h3 class="text-2xl font-bold mb-6">
                    Product Information
                </h3>

                <table class="w-full border-collapse">
                    <tbody>
                        <tr>
                            <th class="border p-3 text-left">Product ID</th>
                            <td class="border p-3">{{ $product->id }}</td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Product Name</th>
                            <td class="border p-3">{{ $product->name }}</td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">SKU</th>
                            <td class="border p-3">{{ $product->sku }}</td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Description</th>
                            <td class="border p-3">
                                {{ $product->description ?? 'N/A' }}
                            </td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Category</th>
                            <td class="border p-3">{{ $product->category }}</td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Quantity</th>
                            <td class="border p-3">{{ $product->quantity }}</td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Reorder Level</th>
                            <td class="border p-3">
                                {{ $product->reorder_level }}
                            </td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Unit Price</th>
                            <td class="border p-3">
                                ₱{{ number_format($product->unit_price, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Supplier</th>
                            <td class="border p-3">
                                {{ $product->supplier ?? 'N/A' }}
                            </td>
                        </tr>

                        <tr>
                            <th class="border p-3 text-left">Stock Status</th>
                            <td class="border p-3">
                                @if($product->quantity == 0)
                                    <span style="color:red;font-weight:bold;">
                                        Out of Stock
                                    </span>
                                @elseif($product->isLowStock())
                                    <span style="color:orange;font-weight:bold;">
                                        Low Stock
                                    </span>
                                @else
                                    <span style="color:green;font-weight:bold;">
                                        In Stock
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="display:flex;gap:12px;margin-top:25px;">

                    <a href="{{ route('products.edit', $product) }}"
                       style="background:#2563eb;color:white;
                              padding:12px 24px;border-radius:6px;
                              text-decoration:none;">
                        Edit Product
                    </a>

                    <a href="{{ route('products.index') }}"
                       style="background:#6b7280;color:white;
                              padding:12px 24px;border-radius:6px;
                              text-decoration:none;">
                        Back to Inventory
                    </a>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>