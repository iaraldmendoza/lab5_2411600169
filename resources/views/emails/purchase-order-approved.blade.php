<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Purchase order approved</title></head>
<body style="margin:0;background:#f4f7f7;font:15px Arial,sans-serif;color:#17343c">
    <div style="max-width:680px;margin:30px auto;background:#fff;border:1px solid #dce6e8">
        <header style="padding:25px;background:#103e4c;color:white;font-size:20px;font-weight:bold">FACINAL <span style="color:#ffd16b">HARDWARE</span></header>
        <main style="padding:28px"><h1 style="font-size:24px">Purchase order approved</h1><p>Reference <strong>{{ $order->reference }}</strong></p><p>Supplier: {{ $order->supplier->company_name }}<br>Expected delivery: {{ $order->expected_delivery_date->format('d M Y') }}<br>Status: {{ \Illuminate\Support\Str::headline($eventStatus) }}</p>
        <table style="width:100%;border-collapse:collapse"><thead><tr><th style="text-align:left;padding:10px;background:#edf4f4">Product</th><th>Quantity</th><th>Unit price</th></tr></thead><tbody>@foreach ($order->items as $item)<tr><td style="padding:10px;border-bottom:1px solid #dce6e8">{{ $item->product->name }}</td><td style="text-align:center">{{ $item->quantity }}</td><td style="text-align:right">PHP {{ number_format((float) $item->unit_price,2) }}</td></tr>@endforeach</tbody></table>
        <p style="font-size:20px;font-weight:bold;text-align:right">Total PHP {{ number_format((float) $order->total,2) }}</p><p>Terms: {{ $order->terms }}</p><p>{{ $order->notes }}</p><p style="color:#64767e">Facinal Hardware purchasing team</p></main>
    </div>
</body></html>
