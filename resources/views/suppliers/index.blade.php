@extends('layouts.inventory')
@section('title', 'Suppliers')
@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Business partners</div><h1>Supplier directory</h1><p class="muted">Contacts, supply terms and delivery commitments in one place.</p></div>
        @can('manage-suppliers')<a class="button" href="{{ route('suppliers.create') }}">Add supplier</a>@endcan
    </div>
    <x-procurement-filters kind="suppliers" />
    <div class="actions section-gap"><a href="{{ route('suppliers.index') }}">Current suppliers</a><a href="{{ route('suppliers.index', ['archived' => 1]) }}">Archived suppliers</a></div>
    <section class="panel table-wrap">
        <table>
            <thead><tr><th>Company</th><th>Contact</th><th>Lead time</th><th>Payment terms</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td><strong>{{ $supplier->company_name }}</strong><br><small>{{ $supplier->email }}</small></td>
                        <td>{{ $supplier->contact_person }}<br><small>{{ $supplier->phone }}</small></td>
                        <td>{{ $supplier->lead_time_days }} days</td><td>{{ $supplier->payment_terms }}</td><td>{{ $supplier->products_count }}</td>
                        <td><span class="badge {{ $supplier->trashed() ? 'out' : ($supplier->active ? 'good' : 'low') }}">{{ $supplier->trashed() ? 'Archived' : ($supplier->active ? 'Active' : 'Inactive') }}</span></td>
                        <td><div class="actions">
                            @if ($supplier->trashed())
                                @if (auth()->user()->isAdmin())<form method="POST" action="{{ route('suppliers.restore', $supplier->id) }}">@csrf<button class="small-button">Restore</button></form>@endif
                            @else
                                <a href="{{ route('suppliers.show', $supplier) }}">View</a>
                                @can('manage-suppliers')
                                    <a href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}">@csrf @method('DELETE')<button class="text-button">Archive</button></form>
                                @endcan
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No suppliers match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $suppliers->links() }}</div>
    </section>
@endsection
