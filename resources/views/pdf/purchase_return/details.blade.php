@extends('pdf.layouts.master2')

@section('content')
    <div class="list--row mb-15px">
        <div class="float-left">
            <h6 class="title">@lang('Supplier Info')</h6>
            <p class="mb-5px">@lang('Name'): {{ $supplier?->name }}</p>
            <p class="mb-5px">@lang('Mobile'): {{ $supplier?->mobile }}</p>
            <p class="mb-5px">@lang('Email'): {{ $supplier?->email }}</p>
        </div>

        <div class="float-right">
            <h6>@lang('Company'): {{ $supplier->company_name }}</h6>
            <p class="mb-5px">@lang('Invoice No.'): #<b>{{ $purchaseReturn->purchase->invoice_no }}</b></p>
            <p class="mb-5px">@lang('Date'): {{ showDateTime($purchaseReturn->return_date, 'd F Y') }}</p>
            <p class="mb-5px">@lang('Warehouse'): {{ $purchaseReturn->purchase->warehouse->name }}</p>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>@lang('S.N.')</th>
                <th>@lang('Name')</th>
                <th>@lang('SKU')</th>
                <th>@lang('Quantity')</th>
                <th>@lang('Weight')</th>
                <th>@lang('Unit Price')</th>
                <th>@lang('Total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseReturn->details as $return)
                <tr>
                    <td>{{ $loop->iteration }} </td>
                    <td class="fw-bold">{{ $return->product->name }}</td>
                    <td>{{ $return->product->sku }} </td>
                    <td>{{ $return->quantity }} @if($return->product->unit->name && strtolower($return->product->unit->name)!='kg') {{ $return->product->unit->name}} @endif </td>
                    <td>{{ $return->net_weight ?? 0 }} @if($return->product->unit->name && strtolower($return->product->unit->name)=='kg') {{ $return->product->unit->name}} @endif</td>
                    <td>{{ showAmount($return->price) }}</td>
                    <td>{{ showAmount($return->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="list--row mb-15px mt-3">

        <div class="float-right border list--row summary-content">
            <div class="border-bottom clearfix">
                <p class="float-left"> @lang('Subtotal')</p>
                <p class="float-right">{{ showAmount($purchaseReturn->total_price) }}</p>
            </div>

            <div class="border-bottom clearfix">
                <p class="float-left">@lang('Lessed')</p>
                <p class="float-right">{{ showAmount($purchaseReturn->discount_amount) }}</p>
            </div>

            <div class="border-bottom clearfix">
                <p class="float-left">@lang('Grand Total')</p>
                <p class="float-right">{{ showAmount($purchaseReturn->receivable_amount) }}</p>
            </div>

            <div class="border-bottom clearfix">
                <p class="float-left">
                    @lang('Received')
                </p>
                <p class="float-right">{{ showAmount($purchaseReturn->received_amount) }}</p>
            </div>

            <div class="clearfix">
                <p class="float-left">
                    @if ($purchaseReturn->due_amount >= 0)
                        @lang('Receivable')
                    @else
                        @lang('Payable')
                    @endif
                </p>
                <p class="float-right strong">{{ showAmount(abs($purchaseReturn->due_amount)) }}</p>
            </div>
        </div>
    </div>
@endsection
