<section class="panel form-panel">
    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif
        <div class="form-grid">
            @foreach (['company_name' => 'Company name', 'contact_person' => 'Contact person', 'email' => 'Email', 'phone' => 'Phone'] as $name => $label)
                <div class="field"><label for="{{ $name }}">{{ $label }} *</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $name === 'email' ? 'email' : 'text' }}" value="{{ old($name, $supplier->$name) }}" maxlength="{{ $name === 'phone' ? 40 : 255 }}" required>@error($name)<div class="error">{{ $message }}</div>@enderror</div>
            @endforeach
            <div class="field full"><label for="address">Address *</label><textarea id="address" name="address" rows="3" maxlength="2000" required>{{ old('address', $supplier->address) }}</textarea></div>
            <div class="field"><label for="payment_terms">Payment terms *</label><input id="payment_terms" name="payment_terms" list="payment-options" maxlength="60" value="{{ old('payment_terms', $supplier->payment_terms) }}" required><datalist id="payment-options"><option value="Net 30"><option value="Net 15"><option value="Net 60"><option value="COD"></datalist></div>
            <div class="field"><label for="lead_time_days">Lead time in days *</label><input id="lead_time_days" name="lead_time_days" type="number" min="1" max="365" value="{{ old('lead_time_days', $supplier->lead_time_days) }}" required></div>
            <div class="field"><label for="minimum_order_amount">Minimum order amount (PHP) *</label><input id="minimum_order_amount" name="minimum_order_amount" type="number" min="0" step="0.01" value="{{ old('minimum_order_amount', $supplier->minimum_order_amount) }}" required></div>
            <div class="field"><label for="active">Supplier status</label><input type="hidden" name="active" value="0"><label class="checkbox-label"><input id="active" type="checkbox" name="active" value="1" @checked(old('active', $supplier->active))> Active supplier</label></div>
        </div>
        <p class="muted">* Required fields. Active purchase orders must be completed or cancelled before a supplier is deactivated or archived.</p>
        <div class="actions"><button class="button">{{ $supplier->exists ? 'Update supplier' : 'Save supplier' }}</button><a class="button secondary" href="{{ route('suppliers.index') }}">Cancel</a></div>
    </form>
</section>
