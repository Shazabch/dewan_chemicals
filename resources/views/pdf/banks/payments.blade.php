@extends('pdf.layouts.master2')

@section('content')

<div class="list--row mb-15px">
    <div class="float-left">
        <h6>@lang('Bank Info')</h6>
        <p class="mb-5px">@lang('Name'): {{ $bank->name }}</p>
        <p class="mb-5px">@lang('Account'): {{ $bank->account_number }}</p>
        <p class="mb-5px">@lang('Holder'): {{ $bank->account_holder }}</p>
    </div>

    <div class="float-right">

        <p class="mb-5px">@lang('Iban'): {{ $bank->iban }}</p>
        <p class="mb-5px">@lang('Balance'): {{ $bank->current_balance }}</p>
    </div>
</div>



<table class="table table-striped">
    <thead>
        <tr>
            <th>S.No.</th>
            <th>Opening Balance</th>
            <th>Closing Balance</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Amount</th>
            <th>Source</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $index => $transaction)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ showAmount($transaction->opening_balance) }}</td>
            <td>{{ showAmount($transaction->closing_balance) }}</td>
            <td>{{ showAmount($transaction->debit) }}</td>
            <td>{{ showAmount($transaction->credit) }}</td>
            <td>{{ showAmount($transaction->amount) }}</td>
            <td> {{ $transaction->source }} </td>
            <td>{{ showDateTime($transaction->created_at, 'd M, Y') }}</td>
        </tr>
        @endforeach
</table>
@endsection