
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Bulad Bay - Inventory Reports
        </h2>
    </x-slot>

    <style>
        .report-container {
            max-width: 1250px;
            margin: 35px auto;
            padding: 20px;
        }

        .report-header {
            background: linear-gradient(120deg, #103e4c, #138486);
            color: white;
            padding: 35px;
            border-radius: 18px;
            margin-bottom: 25px;
        }

        .report-header h1 {
            font-size: 30px;
            font-weight: bold;
        }

        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .report-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e0e7e9;
        }

        .report-card p {
            color: #64748b;
            font-size: 14px;
        }

        .report-card h2 {
            color: #103e4c;
            font-size: 26px;
            font-weight: bold;
            margin-top: 10px;
        }

        .report-table-container {
            background: white;
            padding: 25px;
            border-radius: 16px;
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .report-table th {
            background: #eaf4f3;
            padding: 14px;
            text-align: left;
        }

        .report-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .report-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }

        .report-btn {
            background: #138486;
            color: white;
            padding: 12px 20px;
            border-radius: 9px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        .report-btn:hover {
            background: #103e4c;
        }

        @media print {
            nav, header, .report-actions {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .report-container {
                margin: 0;
                padding: 0;
            }

            .report-card, .report-table-container {
                break-inside: avoid;
            }
        }
    </style>

    <div class="report-container">

        <div class="report-header">
            <h1>Bulad Bay Inventory Report</h1>
            <p>Seafood Inventory Management System</p>
            <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
        </div>

        <div class="report-actions">
            <a href="{{ route('products.index') }}"
               class="report-btn">
                Back to Inventory
            </a>

            <button onclick="window.print()"
                    class="report-btn">
                Print / Save as PDF
            </button>
        </div>

        <div class="report-cards">

            <div class="report-card">
                <p>Total Products</p>
                <h2>{{ $totalProducts }}</h2>
            </div>

            <div class="report-card">
                <p>Available Stock</p>
                <h2>{{ $availableStock }}</h2>
            </div>

            <div class="report-card">
                <p>Low Stock</p>
                <h2>{{ $lowStock }}</h2>
            </div>

            <div class="report-card">
                <p>Out of Stock</p>
                <h2>{{ $outOfStock }}</h2>
            </div>

            <div class="report-card">
                <p>Inventory Value</p>
                <h2>₱{{ number_format($inventoryValue, 2) }}</h2>
            </div>

        </div>

        <div class="report-table-container">

            <h2 style="font-size:22px;font-weight:bold;">
                Complete Inventory Report
            </h2>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category }}</td>
                            <td>{{ $product->quantity }}</td>
                            <td>₱{{ number_format($product->unit_price, 2) }}</td>

                            <td>
                                ₱{{ number_format(
                                    $product->quantity * $product->unit_price,
                                    2
                                ) }}
                            </td>

                            <td>
                                @if($product->quantity == 0)
                                    <span style="color:red;font-weight:bold;">
                                        Out of Stock
                                    </span>
                                @elseif($product->quantity <= $product->reorder_level)
                                    <span style="color:#d97706;font-weight:bold;">
                                        Low Stock
                                    </span>
                                @else
                                    <span style="color:green;font-weight:bold;">
                                        In Stock
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;">
                                No inventory products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

    </div>
</x-app-layout>
