<div>
    {{-- Header Section --}}
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h4>Salary History</h4>
        </div>
        <div class="col-md-6 text-end">
            <button wire:click="openCreateModal" class="btn btn--primary">
                <i class="fas fa-plus me-1"></i> Add New Payslip
            </button>
        </div>
    </div>

    {{-- Session Messages --}}
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Salary History Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Pay Period</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salaries as $salary)
                        <tr @include('partials.bank-history-color', ['id'=> $salary->id])>
                            <td>{{ $salary->pay_period_start->format('F, Y') }}</td>
                            <td><strong>{{ number_format($salary->net_salary, 2) }}</strong></td>
                            <td>
                                <span class="badge @if($salary->status == 'paid') bg-success @else bg-info @endif text-capitalize">
                                    {{ $salary->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline--primary" wire:click="openViewModal({{ $salary->id }})"><i class="fas fa-eye "></i></button>

                                @if($salary->status != 'paid')
                                <button class="btn btn-sm btn--primary" wire:click="openEditModal({{ $salary->id }})"><i class="fas fa-pencil"></i></button>


                                <!-- ========================================================== -->
                                <!-- == NEW BUTTON ADDED HERE == -->
                                <!-- ========================================================== -->
                                <button class="btn btn-sm btn-outline-success"
                                    wire:click="confirmPayment({{ $salary->id }})">
                                    Mark as Paid
                                </button>
                                @endif

                                <!-- CHANGED: Now uses wire:click -->
                                <button class="btn btn-sm btn-outline-info"
                                    wire:click="downloadPayslip({{ $salary->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="downloadPayslip({{ $salary->id }})">
                                    <span wire:loading.remove wire:target="downloadPayslip({{ $salary->id }})">
                                        <i class="fas fa-download"></i> PDF
                                    </span>
                                    <span wire:loading wire:target="downloadPayslip({{ $salary->id }})">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    </span>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No salary records found for this staff member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($salaries->hasPages())
        <div class="card-footer">
            {{ $salaries->links() }}
        </div>
        @endif
    </div>

    {{-- View/Edit/Create Modal --}}
    @if($showModal)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($modalMode === 'create') Generate Payslip
                        @elseif($modalMode === 'edit') Edit Payslip
                        @else View Payslip
                        @endif
                        for {{ $user->name }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <form wire:submit.prevent="savePayslip">
                    <div class="modal-body">
                        {{-- Fieldset disables all form elements within it when in 'view' mode --}}
                        <fieldset @if($modalMode==='view' ) disabled @endif>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="pay_period_start" class="form-label">Pay Period (Month)</label>
                                    <input type="month" id="pay_period_start" class="form-control" wire:model.defer="pay_period_start">
                                    @error('pay_period_start') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="base_salary" class="form-label">Base Salary</label>
                                    <input type="number" step="0.01" id="base_salary" class="form-control" wire:model.defer="base_salary">
                                    @error('base_salary') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <hr>
                            <h6>Allowances</h6>
                            @foreach($allowances as $index => $allowance)
                            <div class="row align-items-center mb-2">
                                <div class="col-5">
                                    <input type="text" class="form-control" placeholder="Allowance Name" wire:model.defer="allowances.{{ $index }}.name">
                                </div>
                                <div class="col-5">
                                    <input type="number" step="0.01" class="form-control" placeholder="Amount" wire:model.defer="allowances.{{ $index }}.amount">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="removeAllowance({{ $index }})">&times;</button>
                                </div>
                            </div>
                            @endforeach
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="addAllowance">+ Add Allowance</button>

                            <hr>
                            <h6>Deductions</h6>
                            @foreach($deductions as $index => $deduction)
                            <div class="row align-items-center mb-2">
                                <div class="col-5">
                                    <input type="text" class="form-control" placeholder="Deduction Name" wire:model.defer="deductions.{{ $index }}.name">
                                </div>
                                <div class="col-5">
                                    <input type="number" step="0.01" class="form-control" placeholder="Amount" wire:model.defer="deductions.{{ $index }}.amount">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="removeDeduction({{ $index }})">&times;</button>
                                </div>
                            </div>
                            @endforeach
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="addDeduction">+ Add Deduction</button>

                            <hr>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" class="form-control" rows="3" wire:model.defer="notes"></textarea>
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            @if($modalMode === 'view') Close @else Cancel @endif
                        </button>
                        @if($modalMode !== 'view')
                        <button type="submit" class="btn btn--primary text-light">
                            <span wire:loading.remove wire:target="savePayslip">
                                @if($modalMode === 'create') Generate @else Save Changes @endif
                            </span>
                            <span wire:loading wire:target="savePayslip">Saving...</span>
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    <!-- ========================================================== -->
    <!-- == NEW MODAL ADDED HERE FOR PAYMENT CONFIRMATION == -->
    <!-- ========================================================== -->
    @if($showPaymentModal)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Salary Payment</h5>
                    <button type="button" class="btn-close" wire:click="closePaymentModal"></button>
                </div>
                <form wire:submit.prevent="processPayment">
                    <div class="modal-body">
                        <p>You are about to mark the salary for <strong>{{ $payingSalary->user->name }}</strong> for the period of <strong>{{ $payingSalary->pay_period_start->format('F, Y') }}</strong> as paid.</p>

                        <div class="alert alert-info d-flex justify-content-between p-4">
                             <div>
                                 Amount: <strong>{{ number_format($payingSalary->net_salary, 2) }}</strong>
                             </div>
                             <div>
                                Method: <strong>Cash</strong>
                             </div>


                        </div>

                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" wire:model.defer="payment_date">
                            @error('payment_date') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="payment_notes" class="form-label">Notes (For transaction log)</label>
                            <textarea class="form-control" id="payment_notes" rows="3" wire:model.defer="payment_notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">Cancel</button>
                        <button type="submit" class="btn btn--primary">
                            <span wire:loading.remove wire:target="processPayment">
                                <i class="fas fa-check-circle me-1"></i> Confirm Payment
                            </span>
                            <span wire:loading wire:target="processPayment">
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>