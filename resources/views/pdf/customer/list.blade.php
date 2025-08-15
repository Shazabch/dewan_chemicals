@extends('pdf.layouts.master2')

@section('content')
<table class="table table-striped">
    <thead>
        <tr>
            <th>@lang('S.N.')</th>
            <th>@lang('Name')</th>
            <th>@lang('Mobile')</th>
            <th>Opening Balance</th>
            <th>@lang('Receivable')</th>
            <th>@lang('Payable')</th>
        </tr>
    </thead>
    <tbody>
        @forelse($customers as $customer)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->mobile }}</td>
            <td>{{ number_format($customer->opening_balance, 2) }}</td>
            <td>{{ showAmount($customer->totalReceivableAmount()) }}</td>
            <td>{{ showAmount($customer->totalPayableAmount()) }}</td>
        </tr>
        @empty
        <tr>
            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection