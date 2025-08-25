@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive--md table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Image')</th>
                                <th>@lang('Name') | @lang('SKU') </th>
                                <th>@lang('Category') | @lang('Brand')</th>
                                <th>@lang('Stock') </th>
                                <th>@lang('Total Sale') | @lang('Alert Qty')</th>
                                <th>@lang('Unit')</th>
                                <!-- <th>@lang('Net Weight')</th> -->
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="user justify-content-center">
                                        <div class="thumb">
                                            <img
                                                src="{{ getImage(getFilePath('product') . '/' . $product->image, getFileSize('product')) }}">
                                        </div>
                                    </div>
                                </td>

                                <td class="long-text">
                                    <span class="fw-bold text--primary">{{ __($product->name) }}</span>
                                    <br>
                                    <span class="text--small ">{{ $product->sku }} </span>
                                </td>

                                <td>
                                    {{ __($product->category->name) }}
                                    <br>
                                    <span class="text--primary">{{ $product->brand?->name }}</span>
                                </td>

                                <td>
                                    <span class="fw-bold text--primary">QTY : {{ $product->totalInStock() }}</span>

                                    @if($product->unit && ($product->unit->name == 'kg' || $product->unit->name == 'KG' || $product->unit->name == 'Kg'))
                                    <br>
                                    <span class="text--small ">Weight : {{ $product->totalWeightInStock() }} {{ $product->unit?->name }}</span>
                                    @endif


                                </td>

                                <td>
                                    {{ $product->totalSale() }}
                                    <br>
                                    <span class="badge badge--warning">{{ $product->alert_quantity }}</span>
                                </td>

                                <td> {{ $product->unit->name }}</td>
                                <!-- <td> {{ $product->net_weight ?? '' }}</td> -->

                                <td>
                                    @permit('admin.product.edit')
                                    <div class="button--group">
                                        <a class="btn btn-sm btn-outline--primary ms-1 editBtn"
                                            href="{{ route('admin.product.edit', $product->id) }}"><i
                                                class="las la-pen"></i> @lang('Edit')</a>

                                         <button type="button"
                                                        class="btn btn-sm btn-outline-danger  confirmationBtn"
                                                        data-question="@lang('Are you sure to delete this Product?')" data-action="{{ route('admin.product.delete', $product->id) }}">
                                                        <i class="la la-trash"></i>@lang('Delete')
                                                    </button>
                                    </div>
                                    @endpermit
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table><!-- table end -->
                </div>
            </div>
            @if ($products->hasPages())
            <div class="card-footer py-4">
                @php echo paginateLinks($products) @endphp
            </div>
            @endif
        </div><!-- card end -->
    </div>
</div>

<!-- IMPORT MODAL -->
<div class="modal fade" id="importModal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('Import Product')</h4>
                <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="la la-times" aria-hidden="true"></i>
                </button>
            </div>
            <form id="importForm" method="post" action="{{ route('admin.product.import') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <div class="alert alert-warning p-3" role="alert">
                            <ul>
                                <li>
                                    <strong>@lang('CSV Formatting'):</strong>
                                    <ul>
                                        <li>@lang('Match the structure of the sample file').</li>
                                        <li>@lang('Ensure the column count is the same').</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>@lang('Valid fields Tip'):</strong>
                                    <ul>
                                        <li>@lang('Required'): <code>@lang('name')</code>,
                                            <code>@lang('category')</code>, <code>@lang('sku')</code>,
                                            <code>brand</code>, <code>unit</code>, <code>alert_quantity</code>,
                                            <code>note</code>.
                                        </li>
                                        <li>@lang('Field names must match exactly') .</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>@lang('Required and Unique Fields'):</strong>
                                    <ul>
                                        <li><code>name</code>, <code>category</code>, <code>sku</code>,
                                            <code>brand</code>, <code>unit</code>, <code>alert_quantity</code>
                                            @lang('cannot be empty').
                                        </li>
                                        <li><code>name</code> and <code>sku</code>@lang(' must be unique').</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>@lang('Error Handling'):</strong>
                                    <ul>
                                        <li>@lang('Download, correct errors, and re-import the file').</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>@lang('Performance'):</strong>
                                    <ul>
                                        <li>@lang('Increase server execution time and memory for large imports').</li>
                                    </ul>
                                </li>
                            </ul>

                        </div>
                    </div>
                    <div class="form-group">
                        <label class="fw-bold">@lang('Select File')</label>
                        <input class="form-control" name="file" type="file" accept=".csv" required>
                        <div class="mt-1">
                            <small class="d-block">
                                @lang('Supported files:') <b class="fw-bold">@lang('csv')</b>
                            </small>
                            <small>
                                @lang('Download sample template file from here')
                                <a class="text--primary" href="{{ asset('assets/files/sample/product.csv') }}"
                                    title="@lang('Download csv file')" download>
                                    <b>@lang('product.csv')</b>
                                </a>

                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn--primary w-100 h-45" type="Submit">@lang('Import')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
<x-search-form placeholder="Search Name or SKU" />
@permit('admin.product.create')
<a wire:navigate class="btn btn-outline--primary" href="{{ route('admin.product.create') }}">
    <i class="la la-plus"></i>@lang('Add New')
</a>
<a wire:navigate class="btn btn-outline--secondary" href="{{ route('admin.product.open-stock') }}">
    <i class="la la-plus"></i>@lang('Enter Stock')
</a>
@endpermit
@php
$params = request()->all();
@endphp

@permit(['admin.product.import', 'admin.product.pdf', 'admin.product.csv'])
<div class="btn-group">
    <button class="btn btn-outline--success dropdown-toggle" data-bs-toggle="dropdown" type="button"
        aria-expanded="false">
        @lang('Action')
    </button>
    <ul class="dropdown-menu">
        @permit('admin.product.pdf')
        <li>
            <a class="dropdown-item" href="{{ route('admin.product.pdf', $params) }}"><i
                    class="la la-download"></i>@lang('Download PDF')</a>
        </li>
        @endpermit
        @permit('admin.product.csv')
        <li>
            <a class="dropdown-item" href="{{ route('admin.product.csv') }}"><i
                    class="la la-download"></i>@lang('Download CSV')</a>
        </li>
        @endpermit
        @permit('admin.product.import')
        <li>
            <a class="dropdown-item importBtn" href="javascript:void(0)">
                <i class="las la-cloud-upload-alt"></i> @lang('Import CSV')</a>
        </li>
        @endpermit
    </ul>
</div>
@endpermit
@endpush

@push('script')
<script>
    (function($) {
        "use strict"
        $(".importBtn").on('click', function(e) {
            let importModal = $("#importModal");
            importModal.modal('show');
        });
    })(jQuery);
</script>
@endpush