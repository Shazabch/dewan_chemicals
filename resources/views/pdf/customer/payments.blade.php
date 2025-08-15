@extends('pdf.layouts.master2')

@section('content')

    <div class="list--row mb-15px">
        <div class="float-left">
            <h6>@lang('Customer Info')</h6>
            <p class="mb-5px">@lang('Name'): {{ $customer->name }}</p>
            <p class="mb-5px">@lang('Mobile'): {{ $customer->mobile }}</p>
        </div>

        <div class="float-right">
            <h6>@lang('Payment Info')</h6>
            <p class="mb-5px">@lang('Receivable'): {{ showAmount($customer->totalReceivableAmount()) }}</p>
            <p class="mb-5px">@lang('Payable'): {{ showAmount($customer->totalPayableAmount()) }}</p>
        </div>
    </div>

    @php
        $totalReceivable = $customer->totalReceivableAmount() - $customer->totalPayableAmount();
    @endphp

    <table class="table table-striped">
        <thead>
            <tr>
                <th>@lang('S.N.')</th>
                <th>@lang('Invoice No.')</th>
                <th>@lang('Reason')</th>
                <th>@lang('Amount')</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i = 0;
            @endphp

            @forelse($sales as $sale)
                @php
                    if ($sale->due_amount == 0) {
                        continue;
                    }
                    $i++;
                @endphp
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $sale->invoice_no }} </td>

                    <td>@lang('Sale')</td>

                    <td>
                        @if ($sale->due_amount < 0)
                            <span> - {{ showAmount(abs($sale->due_amount)) }} </span>
                        @else
                            <span> {{ showAmount($sale->due_amount) }} </span>
                        @endif
                    </td>
                </tr>

                @if ($sale->saleReturn)
                    @php
                        if ($sale->saleReturn->due_amount == 0) {
                            continue;
                        }
                    @endphp
                    <tr>
                        <td> {{ ++$i }} </td>
                        <td>{{ $sale->invoice_no }} </td>
                        <td>@lang('Sale Return')</td>
                        <td>
                            @if ($sale->saleReturn->due_amount < 0)
                                <span> {{ showAmount(abs($sale->saleReturn->due_amount)) }} </span>
                            @else
                                <span> - {{ showAmount(abs($sale->saleReturn->due_amount)) }} </span>
                            @endif
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                </tr>
            @endforelse
        </tbody>


        @if ($totalReceivable < 0)
            <tr>
                <td class="fw-bold" colspan="3">
                    @lang('Total Payable to') {{ $customer->name }}
                </td>

                <td class="fw-bold">
                    <span>{{ showAmount(abs($totalReceivable)) }}</span>
                </td>
            </tr>
        @else
            <tr>
                <td class="fw-bold" colspan="3">
                    @lang('Total Receivable from') {{ $customer->name }}
                </td>

                <td class="fw-bold">
                    <span>{{ showAmount(abs($totalReceivable)) }} </span>
                </td>
            </tr>
        @endif
    </table>
@endsection
