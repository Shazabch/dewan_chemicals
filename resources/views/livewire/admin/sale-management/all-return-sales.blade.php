<div>
    <div class="row gy-3">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form wire:submit.prevent="saveReturn">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group">
                                    <label>@lang('Invoice No:')</label>
                                    <input class="form-control" type="text" value="{{ $invoice_no }}" disabled>
                                </div>
                            </div>

                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Customer')</label>
                                    <input class="form-control" type="text" value="{{ $customer_name }}" disabled>
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6" wire:ignore> {{-- wire:ignore for daterangepicker --}}
                                <div class="form-group">
                                    <label>@lang('Date')</label>
                                    <input class="form-control timepicker" name="return_date" id="return_date_picker"
                                        type="text" value="{{ $return_date }}" autocomplete="off"  @if ($editMode) disabled @endif>
                                    @error('return_date') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Warehouse')</label>
                                    <input class="form-control" type="text" value="{{ $warehouse_name }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="table-responsive">
                                <table class="productTable table border">
                                    <thead class="border bg--dark">
                                        <tr>
                                            <th>@lang('Name')</th>
                                            <th>@lang('Qty')</th>
                                            <th class="qty-field">@lang('Return Qty')<span class="text--danger">*</span></th>
                                            <th>@lang('Weight')</th>
                                            <th class="qty-field">@lang('Return Weight')<span class="text--danger">*</span></th>
                                            <th>@lang('Price')</th>
                                            <th>@lang('Total')</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($products as $index => $product)
                                        <tr>
                                            <td>{{ $product['name'] }}</td>
                                            <td>
                                                <span class="fw-bold">Sale: {{ $product['sale_quantity'] }}</span>
                                                <br>
                                                <small>Stock: {{ $product['stock_quantity'] }}</small>
                                            </td>

                                            <td class="text-start">
                                                <input wire:model.live.debounce.500ms="products.{{ $index }}.product_id" type="hidden">
                                                <input wire:model.live.debounce.500ms="products.{{ $index }}.price" type="hidden">

                                                <div class="input-group">
                                                    <input class="form-control quantity"
                                                        wire:model.live.debounce.500ms="products.{{ $index }}.quantity"
                                                        type="number" min="0"
                                                        data-original-sale-qty="{{ $product['sale_quantity'] }}"
                                                        data-original-stock-qty="{{ $product['stock_quantity'] }}">
                                                    <span class="input-group-text">{{ $product['unit_name'] }}</span>
                                                </div>
                                                @error('products.'.$index.'.quantity') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                            </td>

                                            <td>
                                                @if(strtolower($product['unit_name']) == 'kg')
                                                <span class="fw-bold">Sale: {{ $product['sale_weight'] }}</span>
                                                <br>
                                                <small>Stock: {{ $product['stock_weight'] }}</small>
                                                @endif
                                            </td>
                                            <td class="text-start">
                                                @if(strtolower($product['unit_name']) == 'kg')
                                                <div class="input-group">
                                                    <input class="form-control net_weight"
                                                        wire:model.live.debounce.500ms="products.{{ $index }}.net_weight"
                                                        type="number" min="0"
                                                        data-original-sale-qty="{{ $product['sale_weight'] }}"
                                                        data-original-stock-qty="{{ $product['stock_weight'] }}">
                                                    <span class="input-group-text">{{ $product['unit_name'] }}</span>
                                                </div>
                                                @error('products.'.$index.'.net_weight') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                                @endif
                                            </td>

                                            <td>
                                                {{ showAmount($product['price']) }}
                                            </td>
                                            <td>
                                                {{ showAmount($product['total'] ?? 0) }}
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center">@lang('No products in this sale.')</td>
                                        </tr>
                                        @endforelse

                                        @if (!blank($products))
                                        <tr>
                                            <td class="text-end fw-bold" colspan="100%">
                                                @lang('Total Price'): {{ showAmount($grandTotal) }}
                                            </td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                                @error('products') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 col-sm-6">
                                <div class="form-group">
                                    <label>@lang('Note')</label>
                                    <textarea class="form-control" wire:model="note"></textarea>
                                    @error('note') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-6">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label> @lang('Discount')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control" wire:model.live.debounce.500ms="discount" type="number" step="any" min="0">
                                            </div>
                                            @error('discount') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                     <div class="col-sm-12">
                                        <div class="form-group">
                                            <label> @lang('Fine')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control" wire:model.live.debounce.500ms="fine" type="number" step="any">
                                            </div>
                                            @error('fine') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                     <div class="col-sm-12">
                                        <div class="form-group">
                                            <label> @lang('Return Expense')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control" wire:model.live.debounce.500ms="return_expense" type="number" step="any" min="0">
                                            </div>
                                            @error('return_expense') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>


                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label>@lang('Payable to Customer')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control return_amount" type="number" value="{{ getAmount($payableAmount) }}" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($editMode)
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label>@lang('Paid Amount (for this return)')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                {{-- This field typically isn't editable here, it's for info or managed elsewhere --}}
                                                <input class="form-control" type="number" value="{{ getAmount($paidAmount) }}" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label>@lang('Due Amount (for this return)')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control due_amount" type="number" value="{{ getAmount($dueAmount) }}" disabled>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <button class="btn btn--primary w-100 h-45 submit-btn" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>@lang('Submit')</span>
                            <span wire:loading>@lang('Processing')...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
    @endpush

    @push('style-lib')
    <link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
    @endpush

    @push('style')
    <style>
        .table td {
            white-space: unset;
            padding: 8px 15px;
        }

        .qty-field {
            min-width: 180px;
            width: 180px
        }

        /* Adjusted width */
    </style>
    @endpush

    @push('script')
    <script>
        document.addEventListener('livewire:init', () => {
            // Initialize datepicker
            function initDatePicker() {
                if ($('.timepicker').length) {
                    $('.timepicker').daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        autoUpdateInput: false, // Set to false to manually update
                        timePicker: false,
                        locale: {
                            format: 'YYYY-MM-DD'
                        }
                    }, function(start, end, label) {
                        // On apply, set the Livewire property
                        @this.set('return_date', start.format('YYYY-MM-DD'));
                        // Manually update the input field's value
                        $('#return_date_picker').val(start.format('YYYY-MM-DD'));
                    });

                    // If there's an initial value, set it
                    let initialDate = $('#return_date_picker').val();
                    if (initialDate) {
                        $('#return_date_picker').data('daterangepicker').setStartDate(moment(initialDate, 'YYYY-MM-DD'));
                        $('#return_date_picker').data('daterangepicker').setEndDate(moment(initialDate, 'YYYY-MM-DD'));
                        $('#return_date_picker').val(initialDate); // Ensure it's displayed
                    }


                    $('#return_date_picker').on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                        @this.set('return_date', null);
                    });
                }
            }

            initDatePicker(); // Initial call

            // Re-initialize if Livewire re-renders the component containing the datepicker
            // This might be needed if you have conditional rendering that removes/adds the datepicker
            Livewire.hook('morph.updated', ({
                el,
                component
            }) => {
                if ($(el).find('.timepicker').length && !$(el).find('.timepicker').data('daterangepicker')) {
                    initDatePicker();
                }
            });
        });
    </script>
    @endpush
</div>