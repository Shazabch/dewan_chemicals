<div>
    <div class="row mb-3">
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
                    placeholder="Search by Source or Data Model"
                    wire:model.live="search">
            </div>

            {{-- Clear All --}}
            @if($search || $startDate || $endDate)
            <button class="btn btn-outline--primary" wire:click="clearFilters">
                <i class="fas fa-times me-1"></i> Clear All
            </button>
            @endif
            <button type="button" wire:click="savePdf" class="btn btn-sm btn--primary" data-modal_title="@lang('Download PDF')">
                <i
                    class="la la-download"></i>@lang('Download PDF')
                <span wire:loading wire:target="savePdf">
                    <i class="spinner-border  spinner-border-sm  text--primary"></i>

                </span>
            </button>
            <!-- <button type="button" wire:click="newBankToBank" class="btn btn-sm btn-outline--primary " data-modal_title="@lang('Bank To Bank')">
                <i class="las la-plus"></i>@lang('Transfer')
            </button> -->
        </div>
    </div>


    <div class="table-responsive table-responsive--lg">
        <table class="table table--light style--two bg-white">
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
                    <td>
                        <a href="#" wire:click="redirectDataModel({{ $transaction->module_id }},'{{ $transaction->data_model }}')">{{ $transaction->source }}</a>
                    </td>
                    <td>{{ showDateTime($transaction->created_at, 'd M, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div id="bankToBankModal" class="modal fade" tabindex="-1" role="dialog" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg--primary text-white">
                    <h5 class="modal-title text-center w-100 text-white"><span class="type"></span> <span class="text-white">Bank To Bank Transfer</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form wire:submit.prevent="saveTransfer">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>From Bank</label>
                                    <input type="text" class="form-control" value="{{ $fromBankName }}" readonly>
                                    @error('fromBank')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Balance</label>
                                    <input type="text" class="form-control" value="{{ $fromBankBalance }}" readonly>
                                    @error('fromBankBalance')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>To Bank</label>
                                    <select class="form-control" wire:model.live='toBank' required>
                                        <option value="">Select Bank</option>
                                        @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->account_number }})</option>
                                        @endforeach
                                    </select>
                                    @error('toBank')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>


                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <div class="input-group">
                                        <button class="input-group-text">{{ gs('cur_sym') }}</button>
                                        <input type="number" class="form-control" wire:model.live='amount' step="any" required>
                                    </div>
                                    @error('amount') <small class="text-danger">{{ $message }}</small> @enderror

                                </div>
                            </div>
                        </div>
                        <!-- ////// Show all Errors  -->
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="row">
                            <!-- // submit -->
                            <div class="col-md-12">
                                <button type="submit" class="btn btn--primary w-100">
                                    <i class="las la-save"></i> Save
                                </button>
                            </div>
                        </div>

                </form>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('open-modal-bank', () => {
            $('#bankToBankModal').modal('show'); // Open the modal
        });

        window.addEventListener('close-modal-bank', () => {
            $('#bankToBankModal').modal('hide'); // Close the modal
        });
    </script>
</div>

</div>