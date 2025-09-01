<?php

namespace App\Livewire\Banks;

use App\Models\Bank;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Validation\Rule; // Import Rule for validation

class BankComponent extends Component
{
    public $pageTitle;
    public  $bank;
    public $bankTranfer = false;
    public $transfer_amount = 0;
    public $fromBank;
    public $toBank;

    protected function rules()
    {
        if ($this->bankTranfer) {
            return [
                'fromBank' => [
                    'required',
                    'numeric',
                    // Ensure the 'fromBank' is different from 'toBank'
                    Rule::notIn([$this->toBank]),
                    function ($attribute, $value, $fail) {
                        $bank = Bank::find($value);
                        if (!$bank) {
                            $fail('The selected transfer from bank is invalid.');
                        }
                    },
                ],
                'toBank' => [
                    'required',
                    'numeric',
                    // Ensure the 'toBank' is different from 'fromBank'
                    Rule::notIn([$this->fromBank]),
                    function ($attribute, $value, $fail) {
                        $bank = Bank::find($value);
                        if (!$bank) {
                            $fail('The selected transfer to bank is invalid.');
                        }
                    },
                ],
                'transfer_amount' => [
                    'required',
                    'numeric',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        $fromBank = Bank::find($this->fromBank);
                        if ($fromBank && $value > $fromBank->current_balance) {
                            $fail('Insufficient funds in the transfer from bank.');
                        }
                    },
                ],
            ];
        } else {
            return [
                'bank.name' => 'required',
                'bank.account_number' => 'required',
                'bank.account_holder' => 'required',
                'bank.iban' => 'required',
                // 'bank.raast_id' => 'required', // Uncomment if needed
                'bank.opening_balance' => 'required|numeric|min:0',
                'bank.current_balance' => 'nullable|numeric',
            ];
        }
    }

    protected function messages()
    {
        if ($this->bankTranfer) {
            return [
                'fromBank.required' => 'Please select the bank to transfer from.',
                'fromBank.numeric' => 'The transfer from bank must be a number.',
                'fromBank.not_in' => 'The transfer from bank and transfer to bank cannot be the same.',
                'toBank.required' => 'Please select the bank to transfer to.',
                'toBank.numeric' => 'The transfer to bank must be a number.',
                'toBank.not_in' => 'The transfer from bank and transfer to bank cannot be the same.',
                'transfer_amount.required' => 'The transfer amount is required.',
                'transfer_amount.numeric' => 'The transfer amount must be a number.',
                'transfer_amount.min' => 'The transfer amount must be at least 1.',
            ];
        } else {
            return [
                'bank.name.required' => 'The bank name is required.',
                'bank.account_number.required' => 'The account number is required.',
                'bank.account_holder.required' => 'The account holder is required.',
                'bank.iban.required' => 'The IBAN is required.',
                // 'bank.raast_id.required' => 'The Raast ID is required.', // Uncomment if needed
                'bank.opening_balance.required' => 'The opening balance is required.',
                'bank.opening_balance.numeric' => 'The opening balance must be a number.',
                'bank.opening_balance.min' => 'The opening balance cannot be negative.',
                'bank.current_balance.numeric' => 'The current balance must be a number.',
            ];
        }
    }

    public function mount(Request $req)
    {
        $this->resetForm();
        $this->pageTitle = 'All Banks';
    }

    public function savePdf()
    {
        $banks = Bank::searchable(['name', 'account_number', 'account_holder', 'raast_id'])->orderBy('id', 'desc')->get();
        $directory = 'banks_pdf';
        $pdf = Pdf::loadView('pdf.banks.list', [
            'pageTitle' => $this->pageTitle . ' Invoice',
            'banks' => $banks,
        ])->setOption('defaultFont', 'Arial');

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        $filename = 'pdf_' . now()->format('Ymd_His') . '.pdf';
        $filepath = $directory . '/' . $filename;

        Storage::disk('public')->put($filepath, $pdf->output());

        $this->dispatch('notify', status: 'success', message: 'PDF generated successfully!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }

    public function editEntry($id)
    {
        $bank = Bank::find($id);
        $this->bank = $bank->toArray();
        $this->bankTranfer = false; // Ensure this is false when editing a bank
        $this->dispatch('open-modal', ['modalId' => 'cuModal']); // Use cuModal since it's defined in the view
    }

    public function newEntry()
    {
        $this->resetForm();
        $this->bankTranfer = false;
        $this->dispatch('open-modal', ['modalId' => 'cuModal']); // Use cuModal
    }

    public function bankTransfer()
    {
        $this->resetForm();
        $this->bankTranfer = true;
        $this->dispatch('open-modal', ['modalId' => 'cuModal']); // Use cuModal
    }

    public function resetForm()
    {
        $this->bank = [
            'name' => '',
            'account_number' => '',
            'account_holder' => '',
            'iban' => '',
            // 'raast_id' => '', // Uncomment if needed
            'opening_balance' => 0,
            'current_balance' => 0,
        ];
        $this->fromBank = null;
        $this->toBank = null;
        $this->transfer_amount = 0;
    }

    public function saveEntry()
    {
        $this->validate();


        if (!isset($this->bank['id'])) { // Check if it's a new entry
            $this->bank['current_balance'] = $this->bank['opening_balance'];
        }

        Bank::updateOrCreate(
            ['id' => $this->bank['id'] ?? null], // Use 'id' for existing records
            $this->bank
        );
        $this->resetForm();
        $this->dispatch('notify', status: 'success', message: 'Bank ' . (isset($this->bank['id']) ? 'updated' : 'created') . ' successfully!');

        $this->dispatch('close-modal');
    }
    public function saveTransfer()
    {
        $this->validate();

        // Handle bank transfer logic
        $fromBank = Bank::find($this->fromBank);
        $toBank = Bank::find($this->toBank);

        if ($fromBank && $toBank) {
            // Deduct from fromBank
            $fromBank->current_balance -= $this->transfer_amount;
            $fromBank->save();

            // Add to toBank
            $toBank->current_balance += $this->transfer_amount;
            $toBank->save();

            // You might want to create a transaction record here for auditing
            // For example:
            // Transaction::create([
            //     'from_bank_id' => $fromBank->id,
            //     'to_bank_id' => $toBank->id,
            //     'amount' => $this->transfer_amount,
            //     'type' => 'bank_transfer',
            //     'description' => 'Bank to bank transfer',
            // ]);

            $this->resetForm();
            $this->dispatch('notify', status: 'success', message: 'Bank transfer completed successfully!');
        } else {
            $this->dispatch('notify', status: 'error', message: 'One or both selected banks not found.');
        }


        $this->dispatch('close-modal');
    }
    public function confirmDelete($bankId)
    {
        $this->dispatch('swal:confirm', [ // Corrected event name
            'bankId' => $bankId,
            'title' => 'Are you sure?',
            'text' => "You won't be able to revert this!",
        ]);
    }

    public function deleteEntry($id)
    {
        $bank = Bank::find($id);
        if ($bank) {
            $bank->delete();
            $this->dispatch('notify', status: 'success', message: 'Bank deleted successfully');
        } else {
            $this->dispatch('notify', status: 'error', message: 'Bank not found');
        }
    }

    public function render()
    {
        $banks = Bank::searchable(['name', 'account_number', 'account_holder', 'raast_id'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('livewire.banks.bank-component')->with([
            'banks' => $banks,
        ]);
    }
}
