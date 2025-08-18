<div>
    <div class="row">
        <div class="col-md-12 d-flex justify-content-end align-items-start gap-2">

            {{-- Date: Start --}}
            <div class="input-group w-auto">
                <span class="input-group-text bg--primary text-white">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <input type="date" class="form-control custom-date-input" wire:model.live="startDate" placeholder="Start Date">
            </div>

            {{-- Date: End --}}
            <div class="input-group w-auto">
                <span class="input-group-text bg--primary text-white">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <input type="date" class="form-control custom-date-input" wire:model.live="endDate" placeholder="End Date">
            </div>
            {{-- Search Input --}}
            <div class="input-group w-50">
                <span class="input-group-text bg--primary">
                    <i class="fas fa-search text-white"></i>
                </span>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Search by Customer or Bank"
                    wire:model.live="search">
            </div>

            {{-- Clear All --}}
            @if($search || $startDate || $endDate)
            <button class="btn btn-outline--primary" wire:click="clearFilters">
                <i class="fas fa-times me-1"></i> Clear All
            </button>
            @endif
        </div>
        <div class="col-md-12 d-flex justify-content-end align-items-start  mt-3">
            <!-- <a href="{{ route('admin.customers.pdf', [
                    'search' => $search,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'customer_id' => $customerId,
                ]) }}" class="btn btn-outline--primary">
                    View PDF
                </a> -->
            <button wire:click="generateInvoice('{{ $customerId }}', '{{ $startDate }}', '{{ $endDate }}', '{{ $search }}')" class="btn btn--primary">
                Download PDF
                <span wire:loading wire:target="generateInvoice">
                    <i class="spinner-border  spinner-border-sm  text--primary"></i>

                </span>
            </button>
        </div>
    </div>


    <div class="container mt-2">

        <div class="table-responsive">
            <table class="table table-hover table-striped ">
                <thead class="bg--primary text-white">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Opening Balance</th>
                        <th>Credit</th>
                        <th>Debit</th>
                        <th>Closing Balance</th>
                        <th>Source</th>
                        <th>Bank</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $transaction)
                    <tr @include('partials.bank-history-color', ['id'=> $transaction->id])>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $transaction->customer->name ?? 'N/A' }}</td>
                        <td>{{ number_format($transaction->opening_balance, 2) }}</td>
                        <td class="text-success">{{ number_format($transaction->credit_amount, 2) }}</td>
                        <td class="text-danger">{{ number_format($transaction->debit_amount, 2) }}</td>
                        <td>{{ number_format($transaction->closing_balance, 2) }}</td>
                        <td>{{ $transaction->source ?? '-' }}</td>
                        <td>{{ $transaction->bank->name ?? 'N/A' }}</td>
                        <td>{{ $transaction->created_at->format('d-m-Y h:i A') }}</td>
                        <td>
                            @if($transaction->source == 'Advance Received')
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

        <div class="d-flex justify-content-end mt-3">
            {{ $transactions->links() }}
        </div>

    </div>
    <style>
        .pagination {
            justify-content: end;
        }

        .pagination .page-link {
            color: #0d6efd;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
    </style>
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
            const { id } = event.detail[0] || event.detail;

            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to reverse this transaction.",
                icon: 'warning',

                // --- [NEW] Add an input for the reason ---
                input: 'textarea',
                inputLabel: 'Reason for Reversal',
                inputPlaceholder: 'Enter a reason for this reversal (optional)...',

                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reverse it!',

                // --- [NEW] Validate the input if needed ---
                // For example, to make the reason mandatory:
                // inputValidator: (value) => {
                //     if (!value) {
                //         return 'You must provide a reason for the reversal!'
                //     }
                // }

            }).then((result) => {
                // Check if the user confirmed (clicked the 'Yes' button)
                if (result.isConfirmed) {

                    // --- [MODIFIED] Pass the input value along with the ID ---
                    // 'result.value' contains the text from the textarea
                    @this.call('reverseTransaction', id, result.value);
                }
            });
        });
    })(jQuery);
</script>