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
                                    <label class="form-label">@lang('Supplier')</label>
                                    <input class="form-control" type="text" value="{{ $supplier_name }}" disabled>
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6" wire:ignore>
                                <div class="form-group">
                                    <label>@lang('Date')</label>
                                    <input class="form-control timepicker" name="return_date_picker_input" id="return_date_picker_input"
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
                                            <th>@lang('QTY')</th>
                                            <th>@lang('Return Qty')<span class="text--danger">*</span></th>
                                             <th>@lang('Weight')</th>
                                            <th>@lang('Return Weight (kg)')</th>
                                            <th>@lang('Price')</th>
                                            <th>@lang('Total')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($products as $index => $product)
                                            @php $isKg = (strtolower($product['unit_name'] ?? 'pcs') === 'kg' || strtolower($product['unit_name'] ?? 'pcs') === 'kilogram'); @endphp
                                            <tr>
                                                <td>{{ $product['name'] }}</td>
                                                <td>
                                                     <span class="fw-bold">Purchase: {{ $product['purchase_quantity'] }}</span>
                                                <br>
                                                <small>Stock: {{ $product['stock_quantity'] }}</small>

                                                </td>

                                                <td class="text-start" width="18%">
                                                    <div class="input-group">
                                                        <input class="form-control quantity"
                                                               wire:model.live.debounce.500ms="products.{{ $index }}.quantity"
                                                               type="number" min="0"
                                                               title="{{ $isKg ? 'For KG items, use Return Weight field. Quantity here might represent units like bags.' : '' }}">
                                                        <span class="input-group-text">{{ $product['unit_name'] }}</span>
                                                    </div>
                                                    @error('products.'.$index.'.quantity') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                                </td>
                                                <td>
                                                    @if(strtolower($product['unit_name']) == 'kg')
                                                <span class="fw-bold">Purchase: {{ $product['purchase_weight'] }}</span>
                                                <br>
                                                <small>Stock: {{ $product['stock_weight'] }}</small>
                                                @endif
                                                </td>
                                                <td class="text-start" width="18%">
                                                    @if($isKg)
                                                        <div class="input-group">
                                                            <input class="form-control net_weight"
                                                                   wire:model.live.debounce.500ms="products.{{ $index }}.net_weight"
                                                                   type="number" step="any" min="0">
                                                            <span class="input-group-text">@lang('kg')</span>
                                                        </div>
                                                        @error('products.'.$index.'.net_weight') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                                    @else
                                                        N/A
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
                                                <td colspan="7" class="text-center">@lang('No products in this purchase to return.')</td>
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
                                @error('products') <span class="text-danger d-block mt-2">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Rest of the form (note, discount, totals, submit button) remains the same --}}
                        <div class="row">
                            <div class="col-md-8 col-sm-6">
                                <div class="form-group">
                                    <label>@lang('Return Note')</label>
                                    <textarea class="form-control" wire:model.lazy="note"></textarea>
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
                                                <input class="form-control" wire:model.live.debounce.500ms="fine" type="number" step="any" min="0">
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
                                            <label>@lang('Receivable Amount')</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control receivable_amount" type="number" value="{{ getAmount($receivableAmount) }}" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($editMode)
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>@lang('Received Amount')</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                    <input class="form-control" type="number" value="{{ getAmount($receivedAmount) }}" disabled>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label>@lang('Due Amount')</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                    <input class="form-control due_amount" type="number" value="{{ getAmount($dueAmount) }}" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
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

    {{-- Script includes remain the same --}}
    @pushOnce('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
    @endPushOnce

    @pushOnce('style-lib')
    <link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
    @endPushOnce

    @push('style')
    <style>
        .table td {
            padding: 10px 15px !important;
            white-space: unset;
        }
    </style>
    @endpush

    @push('script')
    <script>
        document.addEventListener('livewire:init', () => {
            function initDatePickerForPurchaseReturn() {
                const datePickerInput = $('#return_date_picker_input');
                if (datePickerInput.length && !datePickerInput.data('daterangepicker')) {
                    datePickerInput.daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        autoUpdateInput: false,
                        timePicker: false,
                        locale: {
                            format: 'YYYY-MM-DD'
                        }
                    });
                    datePickerInput.on('apply.daterangepicker', function(ev, picker) {
                        @this.set('return_date', picker.startDate.format('YYYY-MM-DD'));
                        $(this).val(picker.startDate.format('YYYY-MM-DD'));
                    });
                    datePickerInput.on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                        @this.set('return_date', null);
                    });
                    let initialDate = @this.get('return_date');
                    if (initialDate) {
                        datePickerInput.data('daterangepicker').setStartDate(moment(initialDate, 'YYYY-MM-DD'));
                        datePickerInput.data('daterangepicker').setEndDate(moment(initialDate, 'YYYY-MM-DD'));
                        datePickerInput.val(initialDate);
                    }
                }
            }
            initDatePickerForPurchaseReturn();
            Livewire.hook('morph.updated', ({ el, component }) => {
                if ($(el).find('#return_date_picker_input').length && !$(el).find('#return_date_picker_input').data('daterangepicker')) {
                    initDatePickerForPurchaseReturn();
                }
            });
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    initDatePickerForPurchaseReturn();
                }
            });
        });
    </script>
    @endpush
</div>