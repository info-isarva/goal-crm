@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'productsPageSkeleton',
        'columns' => 6,
        'rows' => 10,
        'filterFields' => 2,
    ])

    <div class="row justify-content-center">
        <div class="col-12 col-lg-12">
            <div class="card mt-0">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <h4 class="mb-2 mb-md-0">Products</h4>
                    <a href="{{ route('products.create') }}" class="btn btn-custom btn-sm @if(!auth()->user()->hasCrmPermission('create_crm_product_guard')) disabled @endif"><i class="bi bi-plus"></i> Add product</a>
                </div>
                <div class="card-body">
                   
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Owner</th>
                                    <th>Category</th>
                                    <th>Unit Price</th>
                                    <th>Commission Rate</th>
                                    <th>Tax</th>
                                    <th>Taxable</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>{{ $product->product_name }}</td>
                                        <td>{{ $product->product_code }}</td>
                                        <td>{{ optional($product->owner)->name ?? '-' }}</td>
                                        <td>{{ optional($product->category)->category_name ?? '-' }}</td>
                                        <td>{{ $product->unit_price }}</td>
                                        <td>{{ $product->commission_rate }}</td>
                                        <td>
                                            @if($product->taxRate->isNotEmpty())
                                                {{ $product->taxRate->pluck('name')->join(', ') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $product->tax_status ? 'Yes' : 'No' }}</td>
                                        <td>{{ $product->product_status ? 'Active' : 'Inactive' }}</td>
                                        <td>
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('edit_crm_product_guard')) disabled @endif"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm @if(!auth()->user()->hasCrmPermission('delete_crm_product_guard')) disabled @endif" ><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForms = document.querySelectorAll('form[action*="products"]');

        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
