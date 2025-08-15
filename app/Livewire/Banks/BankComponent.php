<?php

namespace App\Livewire\Banks;

use App\Models\Bank;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class BankComponent extends Component
{
    public $pageTitle;
    public  $bank;

    protected $rules = [
        'bank.name' => 'required',
        'bank.account_number' => 'required',
        'bank.account_holder' => 'required',
        'bank.iban' => 'required',
        'bank.raast_id' => 'required',
        'bank.opening_balance' => 'required',
        'bank.current_balance' => 'nullable',
    ];

    protected $messages = [
        'bank.name.required' => 'The bank name is required.',
        'bank.account_number.required' => 'The account number is required.',
        'bank.account_holder.required' => 'The account holder is required.',
        'bank.iban.required' => 'The IBAN is required.',
        'bank.raast_id.required' => 'The Raast ID is required.',
        'bank.opening_balance.required' => 'The opening balance is required.',
        'bank.current_balance.required' => 'The current balance is required.',
    ];

    public function mount(Request $req)
    {
        $this->resetForm();
        $this->pageTitle = 'All Banks';
    }
    public function savePdf()
    {

        $banks =  Bank::searchable(['name', 'account_number', 'account_holder', 'raast_id'])->orderBy('id', 'desc')->get();
        $directory = 'banks_pdf';
        // Generate PDF
        $pdf = Pdf::loadView('pdf.banks.list', [
            'pageTitle' => $this->pageTitle . ' Invoice',
            'banks' => $banks,
        ])->setOption('defaultFont', 'Arial');

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        $filename = 'pdf_' . now()->format('Ymd_His') . '.pdf'; // Unique filename
        $filepath = $directory . '/' . $filename;

        // Save the PDF to storage
        Storage::disk('public')->put($filepath, $pdf->output());

        $this->dispatch('notify', status: 'success', message: 'PDF generated successfully!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }

    public function editEntry($id)
    {
        $bank = Bank::find($id);
        $this->bank = $bank->toArray();
    }

    public function newEntry()
    {
        $this->resetForm();
        $this->dispatch('open-modal', ['modalId' => 'bankModal']);
    }
    public function resetForm()
    {
        $this->bank = [
            'name' => '',
            'account_number' => '',
            'account_holder' => '',
            'iban' => '',
            'raast_id' => '',
            'opening_balance' => 0,
            'current_balance' => 0,
        ];
    }




    public function saveEntry()
    {
        $this->validate();
        Bank::updateOrCreate(
            ['account_number' => $this->bank['account_number']], // Condition to check for existing entry
            $this->bank // Data to update or create
        );
        $this->resetForm();
        $this->dispatch('notify', status: 'success', message: 'Bank created successfully');
        $this->dispatch('close-modal');
    }
    public function confirmDelete($bankId)
    {

        $this->dispatcht('swal:confirm', [
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
        $banks =  Bank::searchable(['name', 'account_number', 'account_holder', 'raast_id'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('livewire.banks.bank-component')->with([
            'banks' => $banks,
        ]);
    }
}
