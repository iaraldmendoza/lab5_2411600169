<section class="panel form-panel">
@if($errors->any())<div role="alert" class="notice error">Please correct the highlighted fields before saving.</div>@endif
<form method="POST" action="{{ $product->exists ? route('products.update',$product) : route('products.store') }}">
@csrf
@if($product->exists) @method('PUT') @endif
<div class="form-grid">
@foreach(['name'=>'Product name','sku'=>'SKU'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }} *</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$product->$field) }}" maxlength="{{ $field==='sku'?60:255 }}" required @error($field) aria-invalid="true" @enderror>@error($field)<div class="error">{{ $message }}</div>@enderror</div>
@endforeach
<div class="field full"><label for="description">Description</label><textarea id="description" name="description" rows="2" maxlength="5000">{{ old('description',$product->description) }}</textarea>@error('description')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="category">Category *</label><select id="category" name="category" required><option value="">Choose a category</option>@foreach(\App\Models\Product::CATEGORIES as $category)<option @selected(old('category',$product->category)===$category)>{{ $category }}</option>@endforeach</select>@error('category')<div class="error">{{ $message }}</div>@enderror</div>

<div class="field">
    <label for="supplier_id">Preferred supplier</label>
    @if (!auth()->user()->isManager())<input type="hidden" name="supplier_id" value="{{ $product->supplier_id }}">@endif
    <select id="supplier_id" name="supplier_id" @disabled(!auth()->user()->isManager())>
        <option value="">No preferred supplier</option>
        @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id) === (string) $supplier->id)>{{ $supplier->company_name }}</option>@endforeach
    </select>
</div>
@foreach (['safety_stock' => ['Safety stock',5,'1'], 'target_stock' => ['Target stock',50,'1'], 'daily_usage_estimate' => ['Estimated daily usage',1,'0.01']] as $field => $settings)
    <div class="field"><label for="{{ $field }}">{{ $settings[0] }} *</label><input id="{{ $field }}" name="{{ $field }}" type="number" min="{{ $field === 'target_stock' ? 1 : 0 }}" step="{{ $settings[2] }}" value="{{ old($field, $product->$field ?? $settings[1]) }}" required></div>
@endforeach
@foreach(['quantity'=>'Stock quantity','reorder_level'=>'Reorder level','unit_price'=>'Unit price (PHP)'] as $field=>$label)
<div class="field"><label for="{{ $field }}">{{ $label }} *</label><input type="number" id="{{ $field }}" name="{{ $field }}" min="0" @if($field==='quantity' && $product->exists && !auth()->user()->isAdmin()) readonly @endif step="{{ $field==='unit_price'?'0.01':'1' }}" value="{{ old($field,$product->$field ?? ($field==='reorder_level'?5:0)) }}" required>@error($field)<div class="error">{{ $message }}</div>@enderror</div>
@endforeach
</div><p class="muted">* Required fields. Low stock means quantity is at or below the reorder level.</p>
<div class="actions"><button class="button">{{ $product->exists?'Update product':'Save product' }}</button><a class="button secondary" href="{{ route('products.index') }}">Cancel</a></div>
</form></section>
