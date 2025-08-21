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
            <h6>@lang('Company'): {{ $supplier?->company_name }}</h6>
            <p class="mb-5px">@lang('Invoice No.'): #<b>{{ $purchase->invoice_no }}</b></p>
            <p class="mb-5px">@lang('Date'): {{ showDateTime($purchase->purchase_date, 'd F Y') }}</p>
            <p class="mb-5px">@lang('Warehouse'): {{ $purchase->warehouse->name }}</p>
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
            @foreach ($purchase->purchaseDetails as $value)
                <tr>
                    <td>{{ $loop->iteration }} </td>
                    <td>{{ $value->product->name }}</td>
                    <td>{{ $value->product->sku }} </td>
                     <td>{{ $value->quantity }} @if($value->product->unit->name && strtolower($value->product->unit->name)!='kg') {{ $value->product->unit->name}} @endif </td>
                    <td>{{ $value->net_weight ?? 0 }} @if($value->product->unit->name && strtolower($value->product->unit->name)=='kg') {{ $value->product->unit->name}} @endif</td>
                    <td>{{ showAmount($value->price) }}</td>
                    <td>{{ showAmount($value->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="list--row mb-15px mt-3">

        <div class="float-right border list--row summary-content">
            <div class="border-bottom clearfix">
                <p class="float-left">@lang('Subtotal')</p>
                <p class="float-right">{{ showAmount($purchase->total_price) }}</p>
            </div>

            <div class="border-bottom clearfix">
                <p class="float-left">@lang('Discount')</p>
                <p class="float-right">{{ showAmount($purchase->discount_amount) }}</p>
            </div>

            <div class="border-bottom clearfix">
                <p class="float-left">@lang('Grand Total')</p>
                <p class="float-right">{{ showAmount($purchase->payable_amount) }}</p>
            </div>

            <div class="border-bottom clearfix">
                <p class="float-left">
                    @lang('Paid')
                </p>
                <p class="float-right">{{ showAmount($purchase->paid_amount) }}</p>
            </div>

            <div class="clearfix">
                <p class="float-left">
                    @if ($purchase->due_amount >= 0)
                        @lang('Payable')
                    @else
                        @lang('Receivable')
                    @endif
                </p>
                <p class="float-right strong">{{ showAmount(abs($purchase->due_amount)) }}</p>
            </div>
        </div>
    </div>
@endsection
