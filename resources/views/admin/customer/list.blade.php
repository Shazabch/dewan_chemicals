@extends('admin.layouts.app')
@section('panel')
<div class="row">
     <style>
        .sortable span{
            color: white;
        }
    </style>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive--md table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>

                                <th class="sortable" data-col-index="1" data-type="string">
                                    <span class="">@lang('Name')</span>
                                    <span class="sort-ind ms-1">↕</span>
                                </th>

                                <th class="sortable" data-col-index="2" data-type="number">
                                    <span>@lang('Book Let')</span>
                                    <span class="sort-ind ms-1">↕</span>
                                </th>
                                <th>@lang('Address')</th>
                                <th>@lang('Mobile') | @lang('Email')</th>
                                <th>@lang('Opening Balance')</th>
                                <th>@lang('Receivable')</th>
                                <th>@lang('Payable')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $customer->name }}</span>
                                    <br>

                                </td>
                                <td>
                                    <span class="fw-bold">{{ $customer->booklet_no }}</span>

                                </td>
                                 <td>
                                    <span class="fw-bold"> {{ strLimit($customer->address, 40) }}</span>
                                <td>
                                    <span class="fw-bold">{{ $customer->mobile }}</span> <br> {{ $customer->email }}
                                </td>
                                <td>{{ number_format($customer->opening_balance, 2) }}</td>
                                <td>{{ showAmount($customer->totalReceivableAmount()) }}</td>
                                <td>{{ showAmount($customer->totalPayableAmount()) }}</td>
                                <td>
                                    <div class="button--group">
                                        <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                            data-modal_title="@lang('Edit Customer')" data-resource="{{ $customer }}">
                                            <i class="la la-pencil"></i>@lang('Edit')
                                        </button>
                                        @permit('admin.customer.advance.store')
                                        <button type="button" class="btn btn-sm btn-outline--success advanceModalBtn"
                                            data-customer_id="{{ $customer->id }}"
                                            data-customer_name="{{ $customer->name }}">
                                            <i class="las la-hand-holding-usd"></i>@lang('Advance')
                                        </button>
                                        @endpermit
                                        <form id="delete-form-{{ $customer->id }}" action="{{ route('admin.customer.destroy', $customer->id) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete({{ $customer->id }})">Delete</button>
                                        @permit('admin.customer.notification.log')
                                        <a class="btn btn-sm btn-outline--warning"
                                            href="{{ route('admin.customer.notification.log', $customer->id) }}"><i class="la la-bell"></i>
                                            @lang('Notify')
                                        </a>
                                        @endpermit
                                        @php
                                        $totalReceivable = $customer->totalReceivableAmount() - abs($customer->totalPayableAmount());
                                        @endphp
                                        @permit('admin.customer.payment.index')
                                        <a href="{{ route('admin.customer.payment.index', $customer->id) }}" @class([ 'btn btn-sm btn-outline--info' , 'disabled'=> $totalReceivable == 0,
                                            ])>
                                            <i class="las la-money-bill-wave-alt"></i>@lang('Payment')
                                        </a>
                                        @endpermit
                                        <a href="{{ route('admin.customer.view' , $customer->id) }}" class="btn btn-sm btn-outline-success">
                                            <i class="la la-eye"></i> @lang('View')
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($customers->hasPages())
            <div class="card-footer py-4">
                @php echo paginateLinks($customers) @endphp
            </div>
            @endif
        </div>
    </div>
</div>
<!-- Create Update Modal -->
<div class="modal fade" id="cuModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>

            <form action="{{ route('admin.customer.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>@lang('Name')</label>
                                <input type="text" name="name" class="form-control" autocomplete="off" value="{{ old('name') }}" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control " name="email" value="{{ old('email') }}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Book Let No.</label>
                                <input type="text" class="form-control " name="booklet_no" value="{{ old('booklet_no') }}" required>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Mobile')
                                    <i class="fa fa-info-circle text--primary" title="@lang('Type the mobile number including the country code. Otherwise, SMS won\'t send to that number.')">
                                    </i>
                                </label>
                                <input type="number" name="mobile" value="{{ old('mobile') }}" class="form-control ">
                            </div>
                        </div>
                        <div class="form-group col-lg-12">
                            <label for="opening_balance">Opening Balance</label>
                            <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance') }}">
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>@lang('Address')</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                            </div>
                        </div>
                    </div>

                </div>
                @permit('admin.customer.store')
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                </div>
                @endpermit
            </form>
        </div>
    </div>
</div>
{{-- [MODIFIED] Detailed Advance Modal --}}
<div class="modal fade" id="advanceModal">
    <div class="modal-dialog modal-lg" role="document"> {{-- Using modal-lg for better layout --}}
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>

            <form action="{{ route('admin.customer.advance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id">

                <div class="modal-body">
                    {{-- [NEW] Transaction Type Selector --}}
                    <div class="form-group">
                        <label>@lang('Transaction Type')</label>
                        <select name="transaction_type" class="form-control" required>
                            <option value="advance">@lang('Pay Advance / Money Out')</option>
                            <option value="payment">@lang('Receive Payment / Money In')</option>
                        </select>
                    </div>
                    <!-- <input type="hidden" name="transaction_type" value="payment"> -->

                    <hr> {{-- Visual separator --}}
                    <div class="row">

                        {{-- Left Column --}}
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>@lang('Payment Method')</label>
                                <select name="payment_method" class="form-control" id="paymentMethodSelect" required>
                                    <option value="cash">@lang('Cash')</option>
                                    <option value="bank">@lang('Bank')</option>
                                    <option value="both">@lang('Both')</option>
                                </select>
                            </div>

                            {{-- This field will be shown for 'cash' or 'both' --}}
                            <div class="form-group mb-3" id="cash_field">
                                <label>@lang('Cash Amount')</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                    <input type="number" step="any" name="amount_cash" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        {{-- Right Column --}}
                        <div class="col-md-6">
                            {{-- This field will be shown for 'bank' or 'both' --}}
                            <div class="form-group mb-3" id="bank_field" style="display: none;">
                                <label for="bank_id">@lang('Bank Name')</label>
                                <select name="bank_id" class="form-control">
                                    <option value="" selected>@lang('Select Bank')</option>
                                    {{-- Loop through banks passed from the controller or use a View Composer --}}
                                    @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- This field will be shown for 'bank' or 'both' --}}
                            <div class="form-group mb-3" id="bank_amount_field" style="display: none;">
                                <label>@lang('Bank Amount')</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                    <input type="number" step="any" name="amount_bank" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Remarks --}}
                    <div class="form-group">
                        <label class="form-label">@lang('Remarks')</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="@lang('Optional remarks')"></textarea>
                    </div>
                </div>

                @permit('admin.customer.advance.store')
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit Advance')</button>
                </div>
                @endpermit
            </form>
        </div>
    </div>
</div>
{{-- IMPORT MODAL --}}
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('Import Customer')</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="la la-times" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="{{ route('admin.customer.import') }}" id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <div class="alert alert-warning p-3" role="alert">
                            <p>
                                - @lang('Format your CSV the same way as the sample file below.') <br>
                                - @lang('The number of columns in your CSV should be the same as the example below.')<br>
                                - @lang('Valid fields Tip: make sure name of fields must be following: name, email, mobile, address')<br>
                                - @lang("Required all field's, Unique Field's (email, mobile) column cell must not be empty.")<br>
                            </p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="fw-bold">@lang('Select File')</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <div class="mt-1">
                            <small class="d-block">
                                @lang('Supported files:') <b class="fw-bold">@lang('csv')</b>
                            </small>
                            <small>
                                @lang('Download sample template file from here')
                                <a href="{{ asset('assets/files/sample/customer.csv') }}" title="@lang('Download csv file')" class="text--primary"
                                    download>
                                    <b>@lang('customer.csv')</b>
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
                @permit('admin.customer.import')
                <div class="modal-footer">
                    <button type="Submit" class="btn btn--primary w-100 h-45">@lang('Import')</button>
                </div>
                @endpermit
            </form>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<x-search-form />
@permit('admin.customer.store')
<button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New Customer')">
    <i class="la la-plus"></i>@lang('Add New')
</button>
@endpermit



{{-- Other buttons... --}}
@permit('admin.customer.notification.all')
<a class="btn btn-sm btn-outline--info" href="{{ route('admin.customer.notification.all') }}"><i class="la la-bell"></i>
    @lang('Notification to All')
</a>
@endpermit

@php
$params = request()->all();
@endphp
@permit(['admin.customer.pdf', 'admin.customer.csv', 'admin.customer.import'])
<div class="btn-group">
    <button type="button" class="btn btn-outline--success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        @lang('Action')
    </button>
    <ul class="dropdown-menu">
        @permit('admin.customer.pdf')
        <li>
            <a class="dropdown-item" href="{{ route('admin.customer.pdf', $params) }}"><i class="la la-download"></i>@lang('Download PDF')</a>
        </li>
        @endpermit
        @permit('admin.customer.pdf')
        <li>
            <a class="dropdown-item" href="{{ route('admin.customer.csv', $params) }}"><i class="la la-download"></i>@lang('Download CSV')</a>
        </li>
        @endpermit
        @permit('admin.customer.import')
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
        // Main script for the Advance Modal
        $('.advanceModalBtn').on('click', function() {
            var modal = $('#advanceModal');
            let customerId = $(this).data('customer_id');
            let customerName = $(this).data('customer_name');

            modal.find('input[name=customer_id]').val(customerId);
            modal.find('.modal-title').text(`@lang('Advance From') ${customerName}`);

            modal.find('form')[0].reset();

            // --- Logic for showing/hiding fields ---
            // Trigger the change event immediately to set the initial state (cash)
            $('#paymentMethodSelect').trigger('change');

            modal.modal('show');
        });

        // Listener for the payment method dropdown
        $('#paymentMethodSelect').on('change', function() {
            var method = $(this).val();
            var cashField = $('#cash_field');
            var bankField = $('#bank_field');
            var bankAmountField = $('#bank_amount_field');

            if (method === 'cash') {
                cashField.show();
                bankField.hide();
                bankAmountField.hide();
                // Make cash amount required, others not
                cashField.find('input').prop('required', true);
                bankAmountField.find('input').prop('required', false);
                bankField.find('select').prop('required', false);
            } else if (method === 'bank') {
                cashField.hide();
                bankField.show();
                bankAmountField.show();
                // Make bank fields required, cash not
                cashField.find('input').prop('required', false);
                bankAmountField.find('input').prop('required', true);
                bankField.find('select').prop('required', true);
            } else if (method === 'both') {
                cashField.show();
                bankField.show();
                bankAmountField.show();
                // Make all fields required
                cashField.find('input').prop('required', true);
                bankAmountField.find('input').prop('required', true);
                bankField.find('select').prop('required', true);
            }
        });
    })(jQuery);
</script>
@endpush
@push('script')
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the hidden form to delete
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const table = document.querySelector('.table--light.table');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  const headers = table.querySelectorAll('thead th.sortable');

  headers.forEach(th => {
    th.addEventListener('click', function() {
      const colIndex = parseInt(th.dataset.colIndex, 10);
      const type = th.dataset.type || 'string';
      const newDir = th.dataset.dir === 'asc' ? 'desc' : 'asc';

      // reset other headers
      headers.forEach(h => {
        h.dataset.dir = '';
        const hi = h.querySelector('.sort-ind');
        if (hi) hi.textContent = '↕';
      });

      th.dataset.dir = newDir;

      const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.querySelectorAll('td').length);

      rows.sort((a, b) => {
        const ta = a.children[colIndex]?.textContent.trim() ?? '';
        const tb = b.children[colIndex]?.textContent.trim() ?? '';

        let cmp = 0;
        if (type === 'number') {
          const na = parseFloat(ta.replace(/[^0-9.\-]/g, '')) || 0;
          const nb = parseFloat(tb.replace(/[^0-9.\-]/g, '')) || 0;
          cmp = na - nb;
        } else {
          // string comparison with numeric-aware locale compare
          cmp = ta.localeCompare(tb, undefined, { sensitivity: 'base', numeric: true });
        }

        return newDir === 'asc' ? cmp : -cmp;
      });

      rows.forEach(r => tbody.appendChild(r)); // reattach in new order

      const icon = th.querySelector('.sort-ind');
      if (icon) icon.textContent = newDir === 'asc' ? '▲' : '▼';

      // Optional: re-number S.N. column after sort (uncomment if you want sequential S.N.)
      /*
      let start = 1;
      rows.forEach((row, i) => {
        const snCell = row.children[0];
        if (snCell) snCell.textContent = start + i;
      });
      */
    });
  });
});
</script>

@endpush