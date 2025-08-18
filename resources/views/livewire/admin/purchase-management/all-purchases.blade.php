<div>
    <div class="d-flex mb-30 flex-wrap gap-3 justify-content-end align-items-center">
        <div class="d-flex flex-wrap justify-content-end gap-2 align-items-center breadcrumb-plugins">
            <x-search-form dateSearch='yes' />
            @permit('admin.purchase.create')
            <button type="button" wire:click.prevent="createPurchase" class="btn btn-sm btn-outline--primary m-2">
                <i class="las la-plus"></i>{{ $isCreating ? __('Close') : __('Add New') }}
            </button>
            @endpermit
            @permit(['admin.purchase.pdf', 'admin.purchase.csv'])

            @php
            $params = request()->all();
            @endphp
            <div class="btn-group">
                <button class="btn btn-outline--success dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                    @lang('Action')
                </button>
                <ul class="dropdown-menu">
                    @permit('admin.purchase.pdf')
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.purchase.pdf', $params) }}"><i
                                class="la la-download"></i>@lang('Download PDF')</a>
                    </li>
                    @endpermit
                    @permit('admin.purchase.csv')
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.purchase.csv', $params) }}"><i
                                class="la la-download"></i>@lang('Download CSV')</a>
                    </li>
                    @endpermit

                </ul>
            </div>
            @endpermit
        </div>
    </div>

    @if (!$isCreating)
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card bg--transparent">
                    <div class="card-body p-0 ">
                        <div class="table-responsive--md table-responsive">
                            <table class="table table--light style--two bg--white">
                                <thead>
                                    <tr>
                                        <th>@lang('Invoice No.') | @lang('Date')</th>
                                        <th>@lang('Supplier') | @lang('Mobile')</th>
                                        <!-- <th>@lang('Driver Name') | @lang('Contact')</th> -->
                                        <th>@lang('Vehicle No') | @lang('Fare')</th>
                                        <th>@lang('Loading') | @lang('Unloading')</th>
                                        <th> @lang('Warehouse') </th>
                                        <th>@lang('Total Amount') </th>
                                        <!-- <th>@lang('Paid') | @lang('Due')</th> -->
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($purchases as $purchase)
                                    <tr @include('partials.bank-history-color', ['id'=> $purchase->id])>
                                        <td>
                                            @if ($purchase->return_status == 1)
                                            <small><i class="fa fa-circle text--danger" title="@lang('Returned')" aria-hidden="true"></i></small>
                                            @endif
                                            <span class="fw-bold">
                                                {{ $purchase->invoice_no }}
                                            </span>
                                            <br>
                                            <small>{{ showDateTime($purchase->purchase_date, 'd M, Y') }}</small>
                                        </td>

                                        <td>
                                            <span class="text--primary fw-bold"> {{ $purchase->supplier?->name }}</span>
                                            <br>
                                            {{ $purchase->supplier?->mobile }}
                                        </td>
                                        {{-- <td>
                                            <span class="text--success fw-bold"> {{ $purchase->driver_name }}</span>
                                        <br>
                                        {{ $purchase->driver_contact }}
                                        </td> --}}
                                        <td>
                                            {{ $purchase->vehicle_number }}
                                            <br>
                                            {{ $purchase->fare }}
                                        </td>
                                        <td>
                                            {{ $purchase->loading }}

                                        </td>
                                        <td>
                                            {{ $purchase->warehouse->name }}
                                            <!-- <br>
                                            <span class="fw-bold">{{ showAmount($purchase->total_price) }}</span> -->


                                        </td>
                                        <td>
                                            {{-- {{ showAmount($purchase->discount_amount) }} --}}
                                            <!-- <br> -->
                                            <span class="fw-bold">{{ showAmount($purchase->payable_amount) }}</span>
                                        </td>
                                        {{--
                                        <td>
                                            {{ showAmount($purchase->paid_amount) }}

                                        <br>

                                        @if ($purchase->due_amount < 0)
                                            <span class="text--danger fw-bold" title="@lang('Receivable from Supplier')">
                                            - {{ showAmount(abs($purchase->due_amount)) }}
                                            </span>
                                            @else
                                            <span class="fw-bold" title="@lang('Payable to Supplier')">
                                                {{ showAmount($purchase->due_amount) }}
                                            </span>
                                            @endif
                                            </td>
                                            --}}

                                            <td>
                                                <div class="button--group">
                                                    @if ($purchase->return_status !== 1)
                                                    @permit('admin.purchase.edit')
                                                    <a wire:click.prevent="editPurchase({{ $purchase->id }})"
                                                        class="btn btn-sm btn-outline--primary ms-1">
                                                        <i class="la la-pen"></i> @lang('Edit')
                                                    </a>
                                                    @endpermit
                                                    @endif
                                                    <button class="btn btn-sm btn-outline--info ms-1 dropdown-toggle" data-bs-toggle="dropdown" type="button"
                                                        aria-expanded="false">
                                                        <i class="la la-ellipsis-v"></i>@lang('More')
                                                    </button>

                                                    <div class="dropdown-menu">
                                                        @permit('admin.supplier.payment.store')
                                                        <li>
                                                            <a wire:click="openExpenseModal({{$purchase->id}})" href="javascript:void(0)"
                                                                class="dropdown-item">
                                                                <i class="la la-money-bill-wave"></i> @lang('Pay Expense')
                                                            </a>
                                                        </li>
                                                        <a class="dropdown-item paymentModalBtn" wire:click="payMentModal({{$purchase->id}})" data-supplier="{{ $purchase->supplier?->name }}"
                                                            data-invoice="{{ $purchase->invoice_no }}" data-id="{{ $purchase->id }}"
                                                            data-due_amount="{{ $purchase->due_amount }}" href="javascript:void(0)">
                                                            @if ($purchase->due_amount < 0)
                                                                <i class="la la-hand-holding-usd"></i>
                                                                @lang('Receive Payment')
                                                                @elseif($purchase->due_amount > 0)
                                                                <i class="la la-money-bill-wave"></i>
                                                                @lang('Give Payment')
                                                                @endif
                                                        </a>
                                                        @endpermit

                                                        @permit('admin.purchase.return.items')
                                                        @if ($purchase->return_status == 0 && $purchase->due_amount > 0)
                                                        <li>
                                                            <a class="dropdown-item editBtn"
                                                                href="{{ route('admin.purchase.return.items', $purchase->id) }}">
                                                                <i class="la la-undo"></i> @lang('Return Purchase')
                                                            </a>
                                                        </li>
                                                        @endif
                                                        @endpermit
                                                        @permit('admin.purchase.return.edit')
                                                        @if ($purchase->return_status)
                                                        <li>
                                                            <a class="dropdown-item editBtn"
                                                                href="{{ route('admin.purchase.return.edit', $purchase->purchaseReturn->id) }}">
                                                                <i class="la la-undo"></i> @lang('View Return Details')
                                                            </a>
                                                        </li>
                                                        @endif
                                                        @endpermit
                                                        @permit('admin.purchase.invoice.pdf')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.purchase.invoice.pdf', $purchase->id) }}">
                                                                <i class="la la-download"></i> @lang('Download Details')
                                                            </a>
                                                        </li>
                                                        @endpermit
                                                    </div>
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
                    {{-- @if ($purchases->hasPages())
                        <div class="card-footer py-4">
                            @php echo  paginateLinks($purchases) @endphp
                        </div>
                    @endif --}}
                </div>
                <!-- card end -->
            </div>
        </div>
    </div>
    @else
    <div class="row gy-3">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form wire:submit.prevent="savePurchase">
                        <div class="row mb-3">
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group">
                                    <label>@lang('Invoice No:')</label>
                                    <input class="form-control" name="invoice_no"  type="text" wire:model="invoice_no"
                                        required>
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group" id="supplier-wrapper">
                                    <label class="form-label">@lang('Supplier')</label>
                                    <x-select2
                                        id="product-select-select-supplier"
                                        dataArray="suppliers"
                                        wire:model="supplier_id"
                                        placeholder="Select a supplier"
                                        :allowAdd="false" />

                                </div>
                            </div>

                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group">
                                    <label>@lang('Date')</label>
                                     <x-date-picker wire-model="purchase_date" placeholder="Purchase Date" />

                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Warehouse')</label>
                                    <x-select2
                                        id="product-select-select-warehouse"
                                        dataArray="warehouses"
                                        wire:model="warehouse_id"
                                        placeholder="Select a warehouse"
                                        :allowAdd="false" />
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div>
                                    <div class="form-group position-relative">
                                        <label>Search Product</label>
                                        <x-select2
                                            id="product-select-select-purchase"
                                            dataArray="searchAbleProducts"
                                            wire:model="selected_product_id"
                                            placeholder="Select a product"
                                            :allowAdd="false" />
                                        @if(false)
                                        <label>Search Product</label>
                                        <input type="text" class="form-control" wire:model.live.debounce.500ms="searchQuery" placeholder="Product Name or SKU">
                                        <div class="card position-absolute w-100 z-50" style="max-height: 200px; overflow-y: auto;">
                                            <ul class="list-group">
                                                @foreach ($searchResults as $product)
                                                <li class="list-group-item list-group-item-action" wire:click="addProduct({{ $product->id }})" style="cursor: pointer;">
                                                    {{ $product->name }} ({{ $product->sku }})
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="table-responsive table-responsive--lg">
                                    <table class="productTable table border">
                                        <thead class="border bg--dark">
                                            <tr>
                                                <th>@lang('Name')</th>
                                                <th>@lang('Quantity')<span class="text--danger">*</span></th>
                                                <th>@lang('Weight')</th>
                                                <th>@lang('Price')<span class="text--danger">*</span></th>
                                                <th>@lang('Total')</th>
                                                <th>@lang('Action')</th>
                                            </tr>
                                        </thead>
                                        <tbody>


                                            @foreach ($products as $index => $product)
                                            <tr>
                                                <td>{{ $product['name'] }}</td>
                                                {{-- <td>{{ $product['stock'] }}</td> --}}
                                                <td>
                                                    <input type="number" wire:model.live.debounce.500ms="products.{{ $index }}.quantity" class="form-control">
                                                    @error("products.$index.quantity") <small class="text-danger">{{ $message }}</small> @enderror
                                                </td>
                                                <td>
                                                    @if($product['unit'] == 'KG' || $product['unit'] == 'Kg' || $product['unit'] == 'kg')
                                                    <input type="number" wire:model.live.debounce.500ms="products.{{ $index }}.net_weight" class="form-control">
                                                    {{ $product['unit'] }}
                                                    @error("products.$index.net_weight") <small class="text-danger">{{ $message }}</small> @enderror
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="text" wire:model.live.debounce.500ms="products.{{ $index }}.price" class="form-control">
                                                    @error("products.$index.price") <small class="text-danger">{{ $message }}</small> @enderror
                                                </td>

                                                <td>
                                                    {{ number_format($products[$index]['total'], 2) }}
                                                </td>

                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger" wire:click="removeProduct({{ $index }})">
                                                        @lang('Remove')
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach

                                        </tbody>
                                    </table>

                                </div>
                            </div>
                            <div class="row">
                                {{-- Vehicle & Driver Info Row --}}
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Vehicle Number')</label>
                                        <input class="form-control" wire:model.defer="vehicle_number" type="text">
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Driver Name')</label>
                                        <input class="form-control" wire:model.defer="driver_name" type="text">
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Driver Contact')</label>
                                        <input class="form-control" wire:model.defer="driver_contact" type="text">
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Fare')</label>
                                        <input class="form-control" wire:model.defer="fare" type="number" step="any">
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Loading / Unloading')</label>
                                        <input class="form-control" type="number" step="any" wire:model.defer="loading"></input>
                                    </div>
                                </div>

                                {{-- Note & Pricing Row --}}
                                <div class="col-md-7 col-sm-6">
                                    <div class="form-group">
                                        <label>@lang('Note')</label>
                                        <textarea class="form-control" wire:model.defer="note" rows="8">{{ old('note', @$purchase->note) }}</textarea>
                                    </div>
                                </div>

                                <div class="col-md-5 col-sm-6">
                                    <label>@lang('Total Price') ( {{number_format($total_price,2)}} )</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                        <input class="form-control" type="number" wire:model.live.debounce.700ms="total_price" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>@lang('Discount')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control" type="number" step="any" wire:model.live.debounce.500ms="discount">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>@lang('Payable Amount') ( {{number_format($payable_amount,2)}} )</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control" type="number" wire:model.live.debounce.700ms="payable_amount" disabled>
                                        </div>
                                    </div>

                                    @isset($purchase)
                                    <div class="form-group">
                                        <label>@lang('Paid Amount')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control" wire:model.live.debounce.500ms="paid_amount" type="number" disabled>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>@lang('Due Amount') ( {{number_format($due_amount,2)}} )</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control" type="number" wire:model.live.debounce.700ms="due_amount" readonly>
                                        </div>
                                    </div>
                                    @endisset
                                </div>
                            </div>


                            {{-- Submit --}}
                            <div class="mt-4">
                                @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                <button class="btn btn--primary" type="submit">@lang('Save Purchase')</button>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="modal fade" wire:ignore.self id="paymentModal" role="dialog" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Payment')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form wire:submit.prevent="submitPayment">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Left Column -->
                                <div class="form-group mb-3">
                                    <label>@lang('Invoice No.')</label>
                                    <input class="form-control " wire:model="modal_invoice_no" type="text" readonly>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="amountType"></label>
                                    <div class="input-group">
                                        <button class="input-group-text" type="button">{{ gs('cur_sym') }}</button>
                                        <input class="form-control" wire:model="modal_payable_amount" type="text" readonly>
                                    </div>
                                </div>
                                @if($modal_payment_method=='bank' || $modal_payment_method=='both')
                                <div class="form-group mb-3" id="bankNameField">
                                    <label for="bank_id">@lang('Bank Name')</label>
                                    <select name="bank_id" id="bank_id" class="form-control" wire:model="bankId">
                                        <option value="" selected>@lang('Select Bank')</option>
                                        @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <!-- Right Column -->
                                <div class="form-group mb-3">
                                    <label>@lang('Supplier')</label>
                                    <input class="form-control" wire:model="modal_supplier_name" type="text" readonly>
                                </div>

                                <div class="form-group mb-3">
                                    <label>@lang('Payment Method')</label>
                                    <select wire:model.live.debounce.500ms="modal_payment_method" class="form-control" id="paymentMethodSelect" required>
                                        <option value="" disabled selected>@lang('Select Payment Method')</option>
                                        <option value="">----- choose -----</option>
                                        <option value="cash">@lang('Cash')</option>
                                        <option value="bank">@lang('Bank')</option>
                                        <option value="both">@lang('Both')</option>
                                    </select>
                                </div>
                                @if($modal_payment_method=='bank' || $modal_payment_method=='both')
                                <div class="form-group mb-3" id="receivedAmountField">
                                    <label>@lang('Paid Amount Bank')</label>
                                    <div class="input-group">
                                        <button type="button" class="input-group-text">{{ gs('cur_sym') }}</button>
                                        <input type="number" step="any"
                                            wire:model="modal_rec_bank" name="received_amount_bank" class="form-control">
                                    </div>
                                    @error('modal_rec_bank')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                @endif
                                @if($modal_payment_method == 'cash' || $modal_payment_method == 'both')
                                <div class="form-group mb-3" id="receivedAmount">
                                    <label>@lang('Paid Amount Cash')</label>
                                    <div class="input-group">
                                        <button class="input-group-text" type="button">{{ gs('cur_sym') }}</button>
                                        <input class="form-control" wire:model="modal_paid_amount" type="number" step="any" required>
                                    </div>
                                    @error('modal_paid_amount')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                @endif
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
    <div wire:ignore.self class="modal fade" id="cuModal" tabindex="-1" role="dialog" aria-labelledby="cuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form wire:submit.prevent="storeExpense">
                <div class="modal-content">
                    <div class="modal-header bg--primary te">
                        <h5 class="modal-title text-center w-100 text-white" id="cuModalLabel">Add New Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Expense Type --}}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>@lang('Type')</label>
                                <select class="form-control" wire:model.live.debounce.500ms="expense_type_id" required>
                                    <option value="">@lang('Select One')</option>
                                    @foreach ($categories as $item)
                                    <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                    @endforeach
                                </select>
                                @error('expense_type_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- Date --}}
                            <div class="form-group col-md-6">
                                <label>@lang('Date of Expense')</label>
                                <input type="date" class="form-control" wire:model="date_of_expense">
                                @error('date_of_expense') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        {{-- Bank --}}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>@lang('Bank Name')</label>
                                <select class="form-control" wire:model="bank_id">
                                    <option value="">@lang('Select Bank')</option>
                                    @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- Amount --}}
                            <div class="form-group col-md-6">
                                <label>@lang('Amount')</label>
                                <div class="input-group">
                                    <button class="input-group-text">{{ gs('cur_sym') }}</button>
                                    <input type="number" class="form-control" wire:model="exp_amount" readonly step="any" required>
                                </div>
                                @error('exp_amount') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="form-group col-md-12">
                            <label>@lang('Note')</label>
                            <textarea class="form-control" wire:model="note" rows="5"></textarea>
                            @error('note') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn--primary h-45 w-100 permit" type="submit">@lang('Submit')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('script')
    <script>
        (function($) {
            "use strict";
            $(document).on('click', '.paymentModalBtn', function() {
                var modal = $('#paymentModal');
                modal.modal('show');
            });
        })(jQuery);
        window.addEventListener('open-expense-modal', () => {
            $('#cuModal').modal('show'); // Open the modal
        });

        window.addEventListener('close-modal', () => {
            $('#cuModal').modal('hide'); // Close the modal
        });
    </script>
    @endpush
</div>