@extends('pdf.layouts.master2')


@section('content')
<div class="list--row mb-15px">
    <div class="float-left">
        <p class="mb-5px"><strong>Title:</strong> {{ $selectedStock->title }}</p>
        <p class="mb-5px"><strong>Warehouse:</strong> {{ $selectedStock->warehouse->name }}</p>
        <p class="mb-5px"><strong>@lang('Vendor'):</strong> {{ $selectedStock->user?->name }}</p>
        @if($selectedStock->stock_type=='out')
        <p class="mb-5px"><strong>@lang('Client'):</strong> {{ $selectedStock->userClient?->name }}</p>
        @endif
        <p class="mb-5px"><strong>Labour:</strong> {{ $selectedStock->labour }}</p>
        <p class="mb-5px"><strong>Vehicle Number:</strong> {{ $selectedStock->vehicle_number }}</p>
        <p class="mb-5px"><strong>Driver Name:</strong> {{ $selectedStock->driver_name }}</p>
    </div>
    <div class="float-right">

        <p class="mb-1 "><strong>Total :</strong> {{ number_format($stockTotalAmount,2) }}</p>
        <p class="mb-1 "><strong>Received :</strong> {{ number_format($selectedStock->recieved_amount,2) }}</p>
        <p class="mb-1 "><strong>Due :</strong> {{ number_format($selectedStock->due_amount,2) }}</p>
    </div>
</div>
</div>

<div class="card mt-4">



    <div class="card-body">
        <table class="table table--light style--two bg--white">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Weight</th>
                    <th>Service Charges</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($selectedStock->stockInOuts as $entry)
                <tr>
                    <td>{{ $entry->product?->name }}</td>
                    <td>{{ $entry->quantity }}</td>
                    <td>{{ $entry->net_weight ?? 0 }} @if($entry->product->unit->name && strtolower($entry->product->unit->name)=='kg') {{ $entry->product->unit->name}} @endif</td>
                    <td>{{ $entry->unit_price }}</td>
                    <td>{{ $entry->created_at }}</td>
                    <td>{{ $entry->total_amount }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection