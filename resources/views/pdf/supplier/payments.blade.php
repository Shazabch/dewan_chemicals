@extends('pdf.layouts.master2')

@section('content')

    <div class="list--row mb-15px">
        <div class="float-left">
            <h6>@lang('Supplier Info')</h6>
            <p class="mb-5px">@lang('Name'): {{ $supplier?->name }}</p>

            <p class="mb-5px">@lang('Mobile'): {{ $supplier?->mobile }}</p>
        </div>

        <div class="float-right">
            <h6>@lang('Payment Info')</h6>
            <p class="mb-5px">@lang('Receivable'): {{ showAmount($supplier->totalReceivableAmount()) }}</p>
            <p class="mb-5px">@lang('Payable'): {{ showAmount($supplier->totalPayableAmount()) }}</p>
        </div>
    </div>

    @php
        $totalReceivable = $supplier->totalReceivableAmount() - $supplier->totalPayableAmount();
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

            @forelse($purchases as $purchase)
                @php
                    if ($purchase->due_amount == 0) {
                        continue;
                    }
                    $i++;
                @endphp
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $purchase->invoice_no }} </td>

                    <td>@lang('Purchase')</td>

                    <td>
                        @if ($purchase->due_amount < 0)
                            <span> - {{ showAmount(abs($purchase->due_amount)) }} </span>
                        @else
                            <span> {{ showAmount($purchase->due_amount) }} </span>
                        @endif
                    </td>
                </tr>

                @if ($purchase->purchaseReturn)
                    @php
                        if ($purchase->purchaseReturn->due_amount == 0) {
                            continue;
                        }
                    @endphp
                    <tr>
                        <td> {{ ++$i }} </td>
                        <td>{{ $purchase->invoice_no }} </td>
                        <td>@lang('Purchase Return')</td>
                        <td>
                            @if ($purchase->purchaseReturn->due_amount < 0)
                                <span> {{ showAmount(abs($purchase->purchaseReturn->due_amount)) }} </span>
                            @else
                                <span> - {{ showAmount(abs($purchase->purchaseReturn->due_amount)) }} </span>
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
                    @lang('Total Payable to') {{ $supplier->name }}
                </td>

                <td class="fw-bold">
                    <span>{{ showAmount(abs($totalReceivable)) }}</span>
                </td>
            </tr>
        @else
            <tr>
                <td class="fw-bold" colspan="3">
                    @lang('Total Receivable from') {{ $supplier->name }}
                </td>

                <td class="fw-bold">
                    <span>{{ showAmount(abs($totalReceivable)) }} </span>
                </td>
            </tr>
        @endif
    </table>
@endsection
