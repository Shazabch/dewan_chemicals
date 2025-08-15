@extends('pdf.layouts.master2')

@section('content')
    <table class="table table-striped">
        <thead>
            <tr>
                <th>@lang('S.N.')</th>
                <th>@lang('Name')</th>
                <th>@lang('Mobile')</th>
                <th>@lang('Opening Balance')</th>
                <th>@lang('Payable')</th>
                <th>@lang('Receivable')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suppliers as $supplier)
                <tr>
                    <td> {{ $loop->iteration }} </td>
                    <td> {{ $supplier?->name }} </td>
                    <td> {{ $supplier?->mobile }} </td>
                    <td> {{ $supplier?->email }} </td>
                     <td>{{ number_format($supplier->opening_balance, 2) }}</td>
                    <td> {{ showAmount($supplier->totalPurchaseDueAmount()) }}</td>
                    <td> {{ showAmount($supplier->totalPurchaseReturnDueAmount()) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
