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
</div>
</div>