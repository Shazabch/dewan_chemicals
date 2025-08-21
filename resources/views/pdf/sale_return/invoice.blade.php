@extends('pdf.layouts.master2')

@section('content')
<div class="list--row mb-15px">
    <div class="float-left">
        <h6 class="title">@lang('Bill To')</h6>
        <p class="mb-5px">@lang('Name'): {{ $customer->name }}</p>
        <p class="mb-5px">@lang('Mobile'): {{ $customer->mobile }}</p>
        <p class="mb-5px">@lang('Email'): {{ $customer->email }}</p>
        <p class="mb-5px">@lang('Address'): {{ $customer->address }}</p>
    </div>

    <div class="float-right">
        <h6 class="mb-5px">@lang('Bill From') </h6>
        <p class="strong">{{ __(gs('site_name')) }}</p>
        <p class="mb-5px">@lang('Invoice No.'): #<b>{{ $sale->invoice_no }}</b></p>
        <p class="mb-5px">@lang('Date'): {{ showDateTime($saleReturn->return_date, 'd F Y') }}</p>
        <p class="mb-5px">@lang('Warehouse'): {{ $sale->warehouse->name }}</p>
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
        @foreach ($saleReturn->details as $return)
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
            <p class="float-left">@lang('Subtotal')</p>
            <p class="float-right">{{ showAmount($saleReturn->total_price) }}</p>
        </div>

        <div class="border-bottom clearfix">
            <p class="float-left">@lang('Lessed')</p>
            <p class="float-right">{{ showAmount($saleReturn->discount_amount) }}</p>
        </div>

        <div class="border-bottom clearfix">
            <p class="float-left"> @lang('Grand Total')</p>
            <p class="float-right">{{ showAmount(abs($saleReturn->payable_amount)) }}</p>
        </div>

        <div class="border-bottom clearfix">
            <p class="float-left">
                @lang('Paid')
            </p>
            <p class="float-right">{{ showAmount($saleReturn->paid_amount) }}</p>
        </div>

        <div class="clearfix">
            <p class="float-left">
                @if ($saleReturn->due_amount >= 0)
                @lang('Payable')
                @else
                @lang('Receivable')
                @endif
            </p>

            <p class="float-right strong">{{ showAmount(abs($saleReturn->due_amount)) }}</p>
        </div>
    </div>
</div>
@endsection