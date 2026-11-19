<section class="panel">
    <form method="POST" id="purchase-order-form" action="{{ $order->exists ? route('purchase-orders.update', $order) : route('purchase-orders.store') }}">
        @csrf
        @if ($order->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field"><label for="supplier_id">Supplier *</label><select id="supplier_id" name="supplier_id" required><option value="">Choose an active supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" data-lead="{{ $supplier->lead_time_days }}" data-minimum="{{ $supplier->minimum_order_amount }}" data-terms="{{ $supplier->payment_terms }}" @selected((string) old('supplier_id', $order->supplier_id) === (string) $supplier->id)>{{ $supplier->company_name }}</option>@endforeach</select><small id="supplier-terms" class="muted"></small></div>
            <div class="field"><label for="order_date">Order date *</label><input id="order_date" type="date" name="order_date" value="{{ old('order_date', $order->order_date?->toDateString()) }}" required></div>
            <div class="field"><label for="expected_delivery_date">Expected delivery date *</label><input id="expected_delivery_date" type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $order->expected_delivery_date?->toDateString()) }}" required></div>
            <div class="field"><label for="tax_rate">Tax rate (%) *</label><input id="tax_rate" type="number" name="tax_rate" min="0" max="100" step="0.01" value="{{ old('tax_rate', $order->tax_rate) }}" required></div>
        </div>
        <div class="page-heading"><div><h2>Order items</h2><p class="muted">Use the supplier catalog and agreed package sizes.</p></div><button id="add-line" class="button secondary" type="button" disabled>Add line item</button></div>
        <p id="catalog-status" class="muted" role="status">Choose a supplier to load its catalog.</p>
        <button id="retry-catalog" type="button" class="small-button" hidden>Retry catalog</button>
        <div class="table-wrap po-lines"><table><thead><tr><th>Product *</th><th>Quantity *</th><th>Unit price (PHP) *</th><th>Line total</th><th>Action</th></tr></thead><tbody id="order-lines"></tbody></table></div>
        <dl class="order-totals"><div><dt>Subtotal</dt><dd id="po-subtotal">PHP 0.00</dd></div><div><dt>Tax</dt><dd id="po-tax">PHP 0.00</dd></div><div><dt>Total</dt><dd id="po-total">PHP 0.00</dd></div></dl>
        <div class="form-grid"><div class="field"><label for="terms">Payment and delivery terms</label><textarea id="terms" name="terms" rows="3" maxlength="2000">{{ old('terms', $order->terms) }}</textarea></div><div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes', $order->notes) }}</textarea></div></div>
        @if (!$order->exists)<div class="field"><label for="status">Save as</label><select id="status" name="status"><option value="draft" @selected(old('status') === 'draft')>Draft</option><option value="pending" @selected(old('status') === 'pending')>Pending approval</option></select></div>@endif
        <p class="muted">Orders of PHP 50,000 or more require manager approval followed by approval from a different administrator.</p>
        <div class="actions"><button class="button" id="save-order" disabled>{{ $order->exists ? 'Update draft' : 'Save purchase order' }}</button><a class="button secondary" href="{{ route('purchase-orders.index') }}">Cancel</a></div>
    </form>
</section>
@php
    $initialItems = old('items', $order->exists ? $order->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all() : []);
@endphp
<script type="application/json" id="initial-order-items">@json($initialItems)</script>
@push('scripts')<script src="{{ asset('js/purchase-order-form.js') }}" defer></script>@endpush
