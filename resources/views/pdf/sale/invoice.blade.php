@extends('pdf.layouts.master2')

@section('content')
<style>
    .flex-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .summary-content {
        border: 1px solid #ccc;
        padding: 10px;
        width: 300px;
    }

    .summary-content p {
        margin: 0 0 5px;
    }

    .summary-content .clearfix {
        display: flex;
        justify-content: space-between;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th,
    table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    tr {
        page-break-inside: avoid;
    }
</style>

<div class="list--row mb-15px">
    <div class="float-left">
        <h6 class="title">@lang('Bill To')</h6>
        <p class="mb-5px">@lang('Name'): {{ $customer->name }}</p>
        <p class="mb-5px">@lang('Mobile'): {{ $customer->mobile }}</p>
        <p class="mb-5px">@lang('Email'): {{ $customer->email }}</p>
        <p class="mb-5px">@lang('Address'): {{ $customer->address }}</p>
    </div>

    <div class="float-right">
        <h6 class="mb-5px">@lang('Bill From')</h6>
        <p class="strong">{{ __(gs('site_name')) }}</p>
        <p class="mb-5px">@lang('Invoice No.'): #<b>{{ $sale->invoice_no }}</b></p>
        <p class="mb-5px">@lang('Date'): {{ showDateTime($sale->sale_date, 'd F Y') }}</p>
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
        @forelse($sale->saleDetails as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="fw-bold">{{ $item->product->name }}</td>
            <td>{{ $item->product->sku }}</td>
            <td>{{ $item->quantity }} @if($item->product->unit->name && strtolower($item->product->unit->name)!='kg') {{ $item->product->unit->name}} @endif </td>
            <td>{{ $item->net_weight ?? 0 }} @if($item->product->unit->name && strtolower($item->product->unit->name)=='kg') {{ $item->product->unit->name}} @endif</td>
            <td>{{ showAmount($item->price) }}</td>
            <td>{{ showAmount($item->total) }}</td>
        </tr>
        @empty
        <tr>
            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="list--row mb-15px mt-3">

    <div class="float-right border list--row summary-content">
        <div class="border-bottom clearfix">
            <p class="float-left"> @lang('Subtotal')</p>
            <p class="float-right">{{ showAmount($sale->total_price) }}</p>
        </div>

        <div class="border-bottom clearfix">
            <p class="float-left">@lang('Lessed')</p>
            <p class="float-right">{{ showAmount($sale->discount_amount) }}</p>
        </div>

        <div class="border-bottom clearfix">
            <p class="float-left">@lang('Grand Total')</p>
            <p class="float-right">{{ showAmount($sale->receivable_amount) }}</p>
        </div>

        <div class="border-bottom clearfix">
            <p class="float-left">
                @lang('Received')
            </p>
            <p class="float-right">{{ showAmount($sale->received_amount) }}</p>
        </div>

        <div class="clearfix">
            <p class="float-left">
                @if ($sale->due_amount >= 0)
                @lang('Receivable')
                @else
                @lang('Payable')
                @endif
            </p>
            <p class="float-right strong">{{ showAmount(abs($sale->due_amount)) }}</p>
        </div>
    </div>
</div>

@endsection