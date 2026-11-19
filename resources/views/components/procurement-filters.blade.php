@props(['kind', 'suppliers' => null])
<form method="GET" class="filters panel" aria-label="Search and filters">
    <div class="field">
        <label for="filter-q">Search</label>
        <input id="filter-q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ $kind === 'suppliers' ? 'Company, contact or email' : 'Reference or supplier' }}">
    </div>
    @if ($kind === 'suppliers')
        <div class="field">
            <label for="filter-active">Status</label>
            <select id="filter-active" name="active"><option value="">All statuses</option><option value="1" @selected(request('active') === '1')>Active</option><option value="0" @selected(request('active') === '0')>Inactive</option></select>
        </div>
        <div class="field">
            <label for="filter-sort">Sort by</label>
            <select id="filter-sort" name="sort">@foreach (['company_name' => 'Company name', 'lead_time_days' => 'Lead time', 'created_at' => 'Date added'] as $value => $label)<option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        @if (request('archived'))<input type="hidden" name="archived" value="1">@endif
    @else
        <div class="field">
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status"><option value="">All statuses</option>@foreach (\App\Models\PurchaseOrder::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>@endforeach</select>
        </div>
        <div class="field">
            <label for="filter-supplier">Supplier</label>
            <select id="filter-supplier" name="supplier_id"><option value="">All suppliers</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->company_name }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="filter-from">From</label><input id="filter-from" type="date" name="from" value="{{ request('from') }}"></div>
        <div class="field"><label for="filter-to">To</label><input id="filter-to" type="date" name="to" value="{{ request('to') }}"></div>
    @endif
    <button class="button">Apply filters</button>
    <a class="button secondary" href="{{ url()->current() }}">Reset</a>
</form>
