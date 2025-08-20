@extends('pdf.layouts.master2')

@section('content')
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; }
        .container { width: 100%; margin: 0 auto; }
        .header, .footer { text-align: center; }
        .header h1 { margin: 0; }
        .details, .earnings, .deductions, .summary { width: 100%; margin-bottom: 20px; }
        .details table, .summary table { width: 100%; }
        .earnings table, .deductions table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; }
        .earnings th, .earnings td, .deductions th, .deductions td { border: 1px solid #ddd; }
        .summary .net-pay { font-size: 16px; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; }
    </style>

    <div class="container">
        <!-- <div class="header">
            <h1>Your Company Name</h1>
            <p>123 Company Address, City, State, 12345</p>
            <h2>Payslip</h2>
        </div> -->

        <div class="details">
            <table>
                <tr>
                    <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
                    <td><strong>Pay Period:</strong> {{ $salary->pay_period_start->format('M d, Y') }} - {{ $salary->pay_period_end->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Employee ID:</strong> {{ $salary->user->id }}</td>
                    <td><strong>Payment Date:</strong> {{ $salary->payment_date ? $salary->payment_date->format('M d, Y') : 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div style="width: 48%; float: left; margin-right: 2%;">
            <div class="earnings">
                <table>
                    <thead>
                        <tr><th colspan="2" style="text-align: left;">Earnings</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Base Salary</td>
                            <td class="text-right">{{ number_format($salary->base_salary, 2) }}</td>
                        </tr>
                        @if($salary->allowances)
                            @foreach($salary->allowances as $name => $amount)
                            <tr>
                                <td>{{ Str::title($name) }}</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                            </tr>
                            @endforeach
                        @endif
                        <tr class="total-row">
                            <td>Gross Salary</td>
                            <td class="text-right">{{ number_format($salary->gross_salary, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="width: 48%; float: right;">
             <div class="deductions">
                <table>
                    <thead>
                        <tr><th colspan="2">Deductions</th></tr>
                    </thead>
                    <tbody>
                         @if($salary->deductions)
                            @foreach($salary->deductions as $name => $amount)
                            <tr>
                                <td>{{ Str::title($name) }}</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>No Deductions</td>
                                <td class="text-right">0.00</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td>Total Deductions</td>
                            <td class="text-right">{{ number_format(collect($salary->deductions)->sum(), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="clear: both;"></div>

        <div class="summary">
            <br><br>
            <table>
                <tr>
                    <td class="net-pay">NET PAY:</td>
                    <td class="text-right net-pay">{{ number_format($salary->net_salary, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($salary->notes && false)
        <div class="notes">
            <h6>Notes:</h6>
            <p>{{ $salary->notes }}</p>
        </div>
        @endif


    </div>
@endsection