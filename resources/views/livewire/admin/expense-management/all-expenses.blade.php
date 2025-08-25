<div>
    <!-- Add New Expense Button -->
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-outline--primary mb-2" wire:click="openModal">Add New Expense</button>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table--light style--two custom-data-table table">
                            <thead>
                                <tr>
                                    <th>@lang('S.N.')</th>
                                    <th>@lang('Reason')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Note')</th>
                                    <th>@lang('Source')</th>

                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                <tr @include('partials.bank-history-color', ['id'=> $expense->id])>
                                    <td>{{ $expenses->firstItem() + $loop->index }}</td>
                                    <td>{{ $expense->expenseType->name }}</td>
                                    <td>{{ showDateTime($expense->date_of_expense, 'd M, Y') }}</td>
                                    <td>{{ showAmount($expense->amount) }}</td>
                                    <td>{{ strLimit($expense->note, 35) }}</td>
                                    <td>
                                        <span class="fw-bold"> {{ $expense->dataModel ? $expense->dataModel->invoice_no : '' }}</span>
                                        <span class="fw-bold"> {{ $expense->dataModel ? $expense->dataModel->tracking_id : '' }}</span>
                                        <br>
                                        <small>{{ $expense->getDataModelNameAttribute() }}</small>



                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline--primary"
                                            wire:click="edit({{ $expense->id }})">
                                            <i class="la la-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline--danger"
                                            onclick="triggerDelete({{ $expense->id }})">
                                            <i class="la la-trash"></i>
                                        </button>
                                        @if(!$expense->reversed_at)
                                        <button type="button" class="btn btn-sm btn-outline--danger"
                                            wire:click="confirmReverse({{ $expense->id }})"
                                            title="Reverse Transaction">
                                            <i class="las la-undo"></i> Reverse
                                        </button>
                                        @else
                                        <span class="badge badge--warning">Reversed</span>
                                        <br>
                                        <small>{{ showDateTime($expense->reversed_at) }}</small>
                                        @endif

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>

                @if ($expenses->hasPages())
                <div class="card-footer py-4">
                    @php echo paginateLinks($expenses) @endphp
                </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="cuModal" tabindex="-1" role="dialog" aria-labelledby="cuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form wire:submit.prevent="store">
                <div class="modal-content">
                    <div class="modal-header bg--primary te">
                        <h5 class="modal-title text-center w-100 text-white" id="cuModalLabel">Add New Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Expense Type --}}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>@lang('Type')</label>
                                <select class="form-control" wire:model="expense_type_id" required>
                                    <option value="">@lang('Select One')</option>
                                    @foreach ($categories as $item)
                                    <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                    @endforeach
                                </select>
                                @error('expense_type_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- Date --}}
                            <div class="form-group col-md-6">
                                <label>@lang('Date of Expense')</label>
                                <input type="date" class="form-control" wire:model="date_of_expense">
                                @error('date_of_expense') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        {{-- Bank --}}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>@lang('Bank Name')</label>
                                <select class="form-control" wire:model="bank_id">
                                    <option value="">@lang('Select Bank')</option>
                                    @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- Amount --}}
                            <div class="form-group col-md-6">
                                <label>@lang('Amount')</label>
                                <div class="input-group">
                                    <button class="input-group-text">{{ gs('cur_sym') }}</button>
                                    <input type="number" class="form-control" wire:model="amount" step="any" required>
                                </div>
                                @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="form-group col-md-12">
                            <label>@lang('Note')</label>
                            <textarea class="form-control" wire:model="note" rows="5"></textarea>
                            @error('note') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn--primary h-45 w-100 permit" type="submit">@lang('Submit')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table or other content for the expenses -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
                </div>
                <div class="modal-body">
                    Do you really want to delete this expense type? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" wire:click="delete">Yes, Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
@if ($confirmingDelete)
<script>
    if (confirm("Are you sure to delete this expense type?")) {
        @this.call('delete');
    }
</script>
@endif
@endpush
@push('script')
<script>
    function triggerDelete(expId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the product.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e3342f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.set('deleteId', expId); // Set the expense ID to be deleted
                @this.call('delete');
            }
        });
    };
    window.addEventListener('open-modal', () => {
        $('#cuModal').modal('show'); // Open the modal
    });

    window.addEventListener('close-modal', () => {
        $('#cuModal').modal('hide'); // Close the modal
    });
</script>
@endpush
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