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