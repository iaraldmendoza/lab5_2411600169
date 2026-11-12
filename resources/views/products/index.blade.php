
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Bulad Bay Inventory Management System
        </h2>
    </x-slot>

    <style>
        .inventory-container {
            max-width: 1300px;
            margin: 35px auto;
            padding: 20px;
        }

        .welcome-banner {
            background: linear-gradient(120deg, #103e4c, #138486);
            color: white;
            padding: 38px;
            border-radius: 20px;
            margin-bottom: 25px;
        }

        .welcome-banner h1 {
            font-size: 32px;
            font-weight: 800;
            margin: 15px 0;
        }

        .welcome-banner p {
            font-size: 16px;
        }

        .welcome-badge {
            background: #ffd16b;
            color: #103e4c;
            padding: 9px 18px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            padding: 26px;
            border-radius: 17px;
            border: 1px solid #e2e8f0;
        }

        .stat-card p {
            font-size: 14px;
            color: #64748b;
        }

        .stat-card h2 {
            font-size: 27px;
            font-weight: 800;
            color: #103e4c;
            margin-top: 15px;
        }

        .inventory-box {
            background: white;
            padding: 28px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .inventory-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .inventory-heading h2 {
            font-size: 23px;
            font-weight: 800;
            color: #103e4c;
        }

        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-add {
            background: #138486;
            color: white;
            padding: 13px 20px;
            border-radius: 10px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-add:hover {
            background: #0e6668;
        }

        .btn-report {
            background: #103e4c;
            color: white;
            padding: 13px 20px;
            border-radius: 10px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-report:hover {
            background: #205e70;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .inventory-table th {
            background: #edf5f4;
            padding: 17px;
            text-align: left;
            color: #103e4c;
            font-size: 13px;
        }

        .inventory-table td {
            padding: 17px;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
        }

        .inventory-table tr:hover {
            background: #f8fafc;
        }

        .stock-badge {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: bold;
        }

        .in-stock {
            background: #dcfce7;
            color: #166534;
        }

        .low-stock {
            background: #fef3c7;
            color: #92400e;
        }

        .out-stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-links a {
            text-decoration: none;
            font-weight: bold;
        }

        .view-link {
            color: #138486;
        }

        .edit-link {
            color: #d97706;
        }

        .delete-link {
            color: #dc2626;
            font-weight: bold;
            background: none;
            border: none;
            cursor: pointer;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .inventory-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome-banner {
                padding: 25px;
            }

            .welcome-banner h1 {
                font-size: 25px;
            }
        }
    </style>

    @php
        $allProducts = collect($products);

        $totalProducts = $allProducts->count();

        $availableStock = $allProducts->sum('quantity');

        $lowStock = $allProducts->filter(function ($product) {
            return $product->quantity > 0 &&
                $product->quantity <= $product->reorder_level;
        })->count();

        $inventoryValue = $allProducts->sum(function ($product) {
            return $product->quantity * $product->unit_price;
        });
    @endphp

    <div class="inventory-container">

        <!-- Welcome Banner -->
        <div class="welcome-banner">

            <span class="welcome-badge">
                SEAFOOD INVENTORY
            </span>

            <h1>Welcome to Bulad Bay!</h1>

            <p>
                Manage your dried seafood products,
                monitor stock, and grow your business.
            </p>

        </div>

        <!-- Inventory Statistics -->
        <div class="stats-grid">

            <div class="stat-card">
                <p>Total Products</p>
                <h2>{{ $totalProducts }}</h2>
            </div>

            <div class="stat-card">
                <p>Available Stock</p>
                <h2>{{ $availableStock }}</h2>
            </div>

            <div class="stat-card">
                <p>Low Stock Products</p>
                <h2>{{ $lowStock }}</h2>
            </div>

            <div class="stat-card">
                <p>Total Inventory Value</p>
                <h2>
                    ₱{{ number_format($inventoryValue, 2) }}
                </h2>
            </div>

        </div>

        <!-- Inventory Table -->
        <div class="inventory-box">

            <div class="inventory-heading">

                <h2>Our Seafood Inventory</h2>

                <div class="button-group">

                    <!-- Inventory Reports Button -->
                    <a href="{{ route('reports.inventory') }}"
                       class="btn-report">
                        Inventory Reports
                    </a>

                    <!-- Add Product Button -->
                    <a href="{{ route('products.create') }}"
                       class="btn-add">
                        + Add New Product
                    </a>

                </div>

            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-wrapper">

                <table class="inventory-table">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($products as $product)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $product->name }}
                                    </strong>
                                </td>

                                <td>{{ $product->sku }}</td>

                                <td>{{ $product->category }}</td>

                                <td>{{ $product->quantity }}</td>

                                <td>
                                    ₱{{ number_format($product->unit_price, 2) }}
                                </td>

                                <td>

                                    @if($product->quantity == 0)

                                        <span class="stock-badge out-stock">
                                            Out of Stock
                                        </span>

                                    @elseif($product->quantity <= $product->reorder_level)

                                        <span class="stock-badge low-stock">
                                            Low Stock
                                        </span>

                                    @else

                                        <span class="stock-badge in-stock">
                                            In Stock
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <div class="action-links">

                                        <a href="{{ route('products.show', $product->id) }}"
                                           class="view-link">
                                            View
                                        </a>

                                        <a href="{{ route('products.edit', $product->id) }}"
                                           class="edit-link">
                                            Edit
                                        </a>

                                        <form action="{{ route('products.destroy', $product->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this product?');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="delete-link">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7"
                                    style="text-align:center;padding:30px;">
                                    No products found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>
