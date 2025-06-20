<div class="row gy-3">
    <div class="col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <form wire:submit.prevent="saveStock">
                    <div class="row">
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Title')</label>
                                <input type="text" class="form-control" wire:model="title" placeholder="@lang('Title')" required>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Warehouse')</label>
                                <select class="form-control select2" wire:model.live="warehouse_id" data-minimum-results-for-search="-1" required>
                                    <option value="">@lang('Select One')</option>
                                    @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected($warehouse->id == @$item->warehouse_id)>
                                        {{ __($warehouse->name) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group" id="supplier-wrapper">
                                <label class="form-label">@lang('Vendor / Client')</label>
                                <select class="select2 form-control" wire:model="user_id" required>
                                    <option value="" selected>@lang('Select One')</option>
                                    @foreach ($users as $index => $user)
                                    <option value="{{ $index }}" @selected($index==@$item->user_id)>
                                        {{ __($user['name']) }}
                                    </option>

                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Labour')</label>
                                <input type="text" class="form-control" wire:model="labour" placeholder="@lang('Labour')" required>
                            </div>
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
                                        <input type="number" class="form-control" min="0" wire:model.live="stockItems.{{ $index }}.quantity" placeholder="@lang('Quantity')" required>
                                    </div>
                                </div>
                                @if($item['is_kg'])
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Weight')</label>
                                        <input type="number" class="form-control" min="0" wire:model.live="stockItems.{{ $index }}.net_weight" placeholder="@lang('Weight')" required>
                                    </div>
                                </div>
                                @endif
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Service Charges')</label>
                                        <input type="number" class="form-control" min="0" wire:model.live="stockItems.{{ $index }}.unit_price" placeholder="@lang('Service Charges')" required>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Amount')</label>
                                        <input type="text" class="form-control" wire:model.live="stockItems.{{ $index }}.total_amount" readonly placeholder="@lang('Total Amount')">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                @if($loop->first)
                                <button type="button" wire:click="addItem" class="btn btn--primary">Add More</button>
                                @elseif($loop->last)
                                <button type="button" wire:click="addItem" class="btn btn--primary">Add More</button>
                                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-danger"><i class="las la-times"></i></button>
                                @else
                                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-danger"><i class="las la-times"></i></button>
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