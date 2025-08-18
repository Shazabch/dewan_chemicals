<div>
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-end align-items-start gap-2 flex-wrap">

            {{-- Date Filters --}}
            <input type="date" class="form-control w-auto" wire:model="startDate" placeholder="Start Date">
            <input type="date" class="form-control w-auto" wire:model="endDate" placeholder="End Date">

            {{-- Search Bar --}}
            <div class="input-group w-50">
                <span class="input-group-text bg--primary">
                    <i class="fas fa-search text-white"></i>
                </span>
                <input type="text" class="form-control" placeholder="Search by Bank or Supplier" wire:model.debounce.500ms="search">
            </div>

            {{-- Clear All Button --}}
            @if($search || $startDate || $endDate)
            <button class="btn btn-outline--primary" wire:click="clearFilters">
                <i class="fas fa-times me-1"></i> Clear All
            </button>
            @endif
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-end">
            <a href="{{ route('admin.supplier.invoice', ['supplierId' => $supplier->id]) }}" class="btn  btn-outline--primary" target="_blank">
                Download PDF
            </a>
        </div>
    </div>

    <div class="container mt-4">

        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="bg--primary text-white">
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Opening Balance</th>
                        <th>Credit</th>
                        <th>Debit</th>
                        <th>Closing Balance</th>
                        <th>Source</th>
                        <th>Bank</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $transaction)
                    <tr @include('partials.bank-history-color', ['id'=> $transaction->id])>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $transaction->supplier->name ?? 'N/A' }}</td>
                        <td>{{ number_format($transaction->opening_balance, 2) }}</td>
                        <td class="text-success">{{ number_format($transaction->credit_amount, 2) }}</td>
                        <td class="text-danger">{{ number_format($transaction->debit_amount, 2) }}</td>
                        <td>{{ number_format($transaction->closing_balance, 2) }}</td>
                        <td>{{ $transaction->source ?? '-' }}</td>
                        <td>{{ $transaction->bank->name ?? 'N/A' }}</td>
                        <td>{{ $transaction->created_at->format('d-m-Y h:i A') }}</td>
                        <td>
                            @if($transaction->source == 'Advance to Supplier')
                            @if(!$transaction->reversed_at)
                            <button type="button" class="btn btn-sm btn-outline--danger"
                                wire:click="confirmReverse({{ $transaction->id }})"
                                title="Reverse Transaction">
                                <i class="las la-undo"></i> Reverse
                            </button>
                            @else
                            <span class="badge badge--warning">Reversed</span>
                            <br>
                            <small>{{ showDateTime($transaction->reversed_at) }}</small>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (if applicable) --}}
        {{-- <div class="d-flex justify-content-center mt-3">
                {{ $transactions->links() }}
    </div> --}}
</div>
<script>
    window.addEventListener('notify', event => {
        Swal.fire({
            icon: event.detail.type, // 'success', 'error'
            title: event.detail.type.toUpperCase(),
            text: event.detail.message,
            timer: 2000,
            showConfirmButton: false
        });
    });
</script>
<script>
    (function($) {
        "use strict";

        window.addEventListener('confirmReverse', event => {
            const {
                id
            } = event.detail[0] || event.detail;

            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to reverse this transaction.",
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Reason for Reversal',
                inputPlaceholder: 'Enter a reason for this reversal (optional)...',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reverse it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('reverseTransaction', id, result.value);
                }
            });
        });
    })(jQuery);
</script>
</div>