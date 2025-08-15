@extends('pdf.layouts.master2')

@section('content')
<table class="table table-striped">
    <thead>
        <tr>
            <th>S.N</th>
            <th>Name</th>
            <th>Account #</th>
            <th>Account Holder</th>
            <th>Iban</th>
            <th>Raast Id</th>
            <th>Current Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($banks as $bank)
        <tr>
            <td>{{ $loop->iteration  }}</td>
            <td>{{ $bank->name  }}</td>
            <td>{{ $bank->account_number}}</td>
            <td>{{ $bank->account_holder  }}</td>
            <td>{{ $bank->iban  }}</td>
            <td>{{ $bank->raast_id  }}</td>
            <td>{{ $bank->current_balance  }}</td>

        </tr>
        @endforeach
    </tbody>
</table>
@endsection