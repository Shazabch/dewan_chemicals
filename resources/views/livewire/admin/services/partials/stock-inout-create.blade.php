<div class="row gy-3">
    <div class="col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <form wire:submit.prevent="saveStock">
                    <div class="row">
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label>@lang('Invoice No:')</label>
                                <input class="form-control" name="tracking_id" readonly type="text" wire:model="tracking_id"
                                    required>
                            </div>
                        </div>
                        {{--

                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Title')</label>
                                <input type="text" class="form-control" wire:model="title" placeholder="@lang('Title')" required>
                            </div>
                        </div>
                         --}}
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Warehouse')</label>
                                @if($selected_stock_id)
                                <x-select2
                                    id="vc-select-select-were"
                                    disabled="true"
                                    dataArray="warehouses"
                                    wire:model="warehouse_id"
                                    placeholder="Select one"
                                    :allowAdd="false" />
                                @else
                                <x-select2
                                    id="vc-select-select-were"
                                    dataArray="warehouses"
                                    wire:model="warehouse_id"
                                    placeholder="Select one"
                                    :allowAdd="false" />
                                @endif

                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group" id="supplier-wrapper">
                                <label class="form-label">@lang('Vendor / Client')</label>
                                @if($selected_stock_id)
                                <x-select2
                                    id="vc-select-select-cv"
                                    disabled="true"
                                    dataArray="users"
                                    wire:model="user_id"
                                    placeholder="Select a Client"
                                    :allowAdd="false" />
                                @else
                                <x-select2
                                    id="vc-select-select-cv"
                                    dataArray="users"
                                    wire:model="user_id"
                                    placeholder="Select a Client"
                                    :allowAdd="false" />
                                @endif
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <label>@lang('Date')</label>
                            <input type="date" class="form-control" wire:model="date">
                            @error('date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>


                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Vehicle Number')</label>
                                <input type="text" class="form-control" wire:model="vehicle_number" placeholder="@lang('Vehicle Number')" required>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Driver Name')</label>
                                <input type="text" class="form-control" wire:model="driver_name" placeholder="@lang('Driver Name')" required>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Driver Contact')</label>
                                <input type="text" class="form-control" wire:model="driver_contact" placeholder="@lang('Driver Contact')" required>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Labour / Loading')</label>
                                <input type="number" class="form-control" wire:model="labour" placeholder="@lang('Labour')" required>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Fare')</label>
                                <input type="number" class="form-control" wire:model="fare" placeholder="@lang('Fare')">
                            </div>
                        </div>

                    </div>
                    @foreach ($stockItems as $index => $item)
                    <div class="card shadow-sm mt-1">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-xl-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Product')</label>
                                        <x-select2
                                            id="product-select-{{ $index }}-select"
                                            dataArray="products"
                                            wire:model="stockItems.{{ $index }}.product_id"
                                            placeholder="Select a product"
                                            :allowAdd="false" />
                                    </div>
                                </div>
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Quantity')</label>
                                        {{-- USE DEBOUNCE INSTEAD OF LIVE --}}
                                        <input type="number" class="form-control" min="0" wire:model.live.debounce.700ms="stockItems.{{ $index }}.quantity" placeholder="@lang('Quantity')" required>
                                    </div>
                                </div>
                                @if($item['is_kg'])
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Weight')</label>
                                        {{-- USE DEBOUNCE INSTEAD OF LIVE --}}
                                        <input type="number" class="form-control" min="0" wire:model.live.debounce.700ms="stockItems.{{ $index }}.net_weight" placeholder="@lang('Weight')" required>
                                    </div>
                                </div>
                                @endif
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Service Charges')</label>
                                        {{-- USE DEBOUNCE INSTEAD OF LIVE --}}
                                        <input type="number" class="form-control" min="0" wire:model.live.debounce.700ms="stockItems.{{ $index }}.unit_price" placeholder="@lang('Service Charges')" required>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Amount')</label>
                                        {{-- CORRECTION: Use value attribute for readonly fields, not wire:model --}}
                                        <input type="text" class="form-control" value="{{ number_format($item['total_amount'] ?? 0, 2) }}" readonly placeholder="@lang('Total Amount')">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" wire:click="addItem" class="btn btn--primary me-2">
                                    <i class="las la-plus"></i> Add More
                                </button>

                                @if(count($stockItems) > 1)
                                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-danger">
                                    <i class="las la-times"></i>
                                </button>
                                @endif
                            </div>

                        </div>
                    </div>
                    @endforeach

                    <div class="d-flex justify-content-end mb-3 mt-3 mx-4">

                        <h5>Grand Total : {{ number_format($this->recalculateTotalAmount(),2) }}</h5>

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
                        <button class="btn btn--primary" type="submit">@lang('Save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>