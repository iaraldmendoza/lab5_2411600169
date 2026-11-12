
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bulad Inventory Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <h2 class="text-2xl font-bold mb-6">
                Inventory Overview
            </h2>

            <!-- Inventory Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-gray-500">Total Products</p>
                    <h3 class="text-3xl font-bold text-blue-600">
                        {{ $totalProducts }}
                    </h3>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-gray-500">Low Stock Products</p>
                    <h3 class="text-3xl font-bold text-yellow-600">
                        {{ $lowStock }}
                    </h3>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-gray-500">Out of Stock</p>
                    <h3 class="text-3xl font-bold text-red-600">
                        {{ $outOfStock }}
                    </h3>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-gray-500">Inventory Value</p>
                    <h3 class="text-3xl font-bold text-green-600">
                        ₱{{ number_format($inventoryValue, 2) }}
                    </h3>
                </div>

            </div>

            <!-- Recent Products -->
            <div class="bg-white p-6 rounded-lg shadow">

                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold">
                        Recent Products
                    </h3>

                    <a href="{{ route('products.index') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        View Inventory
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">

                        <thead class="bg-gray-200">
                            <tr>
                                <th class="p-3 text-left">Product</th>
                                <th class="p-3 text-left">SKU</th>
                                <th class="p-3 text-left">Category</th>
                                <th class="p-3 text-left">Quantity</th>
                                <th class="p-3 text-left">Price</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($recentProducts as $product)
                                <tr class="border-b">
                                    <td class="p-3">{{ $product->name }}</td>
                                    <td class="p-3">{{ $product->sku }}</td>
                                    <td class="p-3">{{ $product->category }}</td>
                                    <td class="p-3">{{ $product->quantity }}</td>
                                    <td class="p-3">
                                        ₱{{ number_format($product->unit_price, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center">
                                        No products available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
