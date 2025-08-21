@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card bg--transparent">
                <div class="card-body p-0 ">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two bg-white">
                            <thead>
                                <tr>
                                    <th>@lang('Invoice No.') | @lang('Date')</th>
                                    <th>@lang('Customer') | @lang('Mobile')</th>
                                    <th>@lang('Warehouse') | @lang('Total Amount')</th>
                                    <th>@lang('Discount') | @lang('Payable')</th>
                                    <th>@lang('Paid') | @lang('Due')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($saleReturns as $return)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">
                                                {{ $return->sale->invoice_no }}
                                            </span>

                                            <br>
                                            <small>{{ showDateTime($return->return_date, 'd M, Y') }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text--primary">{{ $return->customer->name }}</span>
                                            <br>
                                            {{ $return->customer->mobile }}
                                        </td>
                                        <td>
                                            {{ $return->sale->warehouse->name }}
                                            <br>
                                            <span class="fw-bold"> {{ showAmount($return->total_price) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ showAmount($return->discount_amount) }}
                                            <br>
                                            <span class="fw-bold">
                                                {{ showAmount($return->payable_amount) }}</span>
                                        </td>

                                        <td>
                                            {{ showAmount($return->paid_amount) }}
                                            <br>
                                            @if ($return->due_amount < 0)
                                                <span class="text--danger fw-bold" title="@lang('Receivable from Customer')">
                                                    - {{ showAmount(abs($return->due_amount)) }}
                                                </span>
                                            @else
                                                <span class="fw-bold" title="@lang('Payable to Customer')">
                                                    {{ showAmount($return->due_amount) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="button--group">
                                                @permit('admin.sale.return.edit')
                                                    <a class="btn btn-sm btn-outline--primary ms-1 editBtn" href="{{ route('admin.sale.return.edit', $return->id) }}"><i
                                                           class="las la-pen"></i> @lang('Edit')
                                                    </a>
                                                @endpermit
                                                <button class="btn btn-sm btn-outline--info ms-1 dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                                                    <i class="la la-ellipsis-v"></i>@lang('More')
                                                </button>
                                                <ul class="dropdown-menu">
                                                    @permit('admin.customer.payment.payable.store')
                                                        @if ($return->due_amount)
                                                            <li>
                                                                <a class="dropdown-item paymentModalBtn" data-customer_id="{{ $return->customer_id }}" data-customer="{{ $return->customer->name }}" data-invoice="{{ $return->sale->invoice_no }}" data-id="{{ $return->id }}" data-due_amount="{{ $return->due_amount }}" href="javascript:void(0)">
                                                                    @if ($return->due_amount > 0)
                                                                        <i class="la la-money-bill-wave"></i>
                                                                        @lang('Give Payment')
                                                                    @elseif($return->due_amount < 0)
                                                                        <i class="la la-hand-holding-usd"></i>
                                                                        @lang('Receive Payment')
                                                                    @endif
                                                                </a>
                                                            </li>
                                                        @endif
                                                    @endpermit
                                                    @permit('admin.sale.return.invoice.pdf')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.sale.return.invoice.pdf', $return->id) }}"><i
                                                                   class="la la-download"></i>
                                                                @lang('Download Invoice')
                                                            </a>
                                                        </li>
                                                    @endpermit

                                                </ul>
                                            </div>
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
                @if ($saleReturns->hasPages())
                    <div class="card-footer py-4">
                        @php echo  paginateLinks($saleReturns) @endphp
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
    <!-- Start Payment Modal  -->
    <div class="modal fade" id="paymentModal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Payment')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Invoice No.')</label>
                            <input class="form-control invoice-no" type="text" readonly>
                        </div>
                        <div class="form-group">
                            <label>@lang('Customer')</label>
                            <input class="form-control customer-name" type="text" readonly>
                        </div>
                        <div class="form-group">
                            <label class="amountType"></label>
                            <div class="input-group">
                                <button class="input-group-text" type="button">{{ gs('cur_sym') }}</button>
                                <input class="form-control payable_amount" type="text" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="payment-type"></label>
                            <div class="input-group">
                                <button class="input-group-text" type="button">{{ gs('cur_sym') }}</button>
                                <input class="form-control" name="amount" type="number" step="any" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary h-45 w-100 permit" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Start Payment Modal  -->
@endsection

@push('style')
    <style>
        .table-responsive {
            min-height: 400px;
            background: transparent
        }

        .card {
            box-shadow: none;
        }
    </style>
@endpush

@push('breadcrumb-plugins')
    <x-search-form dateSearch='yes' />
    @php
        $params = request()->all();
    @endphp
    @permit(['admin.sale.return.pdf', 'admin.sale.return.csv'])
        <div class="btn-group">
            <button class="btn btn-outline--success dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                @lang('Action')
            </button>
            <ul class="dropdown-menu">
                @permit('admin.sale.return.pdf')
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.sale.return.pdf', $params) }}"><i
                               class="la la-download"></i>@lang('Download PDF')</a>
                    </li>
                @endpermit
                @permit('admin.sale.return.csv')
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.sale.return.csv', $params) }}"><i
                               class="la la-download"></i>@lang('Download CSV')</a>
                    </li>
                @endpermit

            </ul>
        </div>
    @endpermit
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $(document).on('click', '.paymentModalBtn', function() {
                var modal = $('#paymentModal');
                let data = $(this).data();
                var due = parseFloat(Math.abs(data.due_amount)).toFixed(2);

                let amountTypeLabel = modal.find('.amountType')
                let payingReceivingLabel = modal.find('.payment-type')

                if (parseFloat(data.due_amount).toFixed(2) > 0) {
                    amountTypeLabel.text(`@lang('Payable Amount')`);
                    payingReceivingLabel.text(`@lang('Paying Amount')`);
                } else {
                    amountTypeLabel.text(`@lang('Receivable Amount')`);
                    payingReceivingLabel.text(`@lang('Receiving Amount')`);
                }

                modal.find('.invoice-no').val(`${data.invoice}`);
                modal.find('.customer-name').val(`${data.customer}`);

                modal.find('.payable_amount').val(`${due}`);
                let form = modal.find('form')[0];
                form.action = `{{ route('admin.customer.payment.payable.store', '') }}/${data.id}`
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
