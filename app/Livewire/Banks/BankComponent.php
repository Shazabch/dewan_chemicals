<?php

namespace App\Livewire\Banks;

use App\Models\Bank;
use Illuminate\Http\Request;
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
         $this->bank = [
                'name' => '',
                'account_number' => '',
                'account_holder' => '',
                'iban' => '',
                'raast_id' => '',
                'opening_balance' => '',
            ];
        $this->pageTitle = 'All Banks';
    }


    public function editEntry($id)
    {
        $bank = Bank::find($id);
        $this->bank = $bank->toArray();
    }

    public function newEntry()
    {
        $this->bank = [
            'name' => '',
            'account_number' => '',
            'account_holder' => '',
            'iban' => '',
            'raast_id' => '',
            'opening_balance' => '',
        ];
    }

    public function saveEntry()
    {
        $this->validate();
        Bank::updateOrCreate(
            ['account_number' => $this->bank['account_number']], // Condition to check for existing entry
            $this->bank // Data to update or create
        );
        $this->bank = [
            'name' => '',
            'account_number' => '',
            'account_holder' => '',
            'iban' => '',
            'raast_id' => '',
            'opening_balance' => '',
        ];
        $this->dispatch('notify', status: 'success', message: 'Bank created successfully');
        $this->dispatch('close-modal');

    }
  
    public function render()
    {
        $banks =  Bank::searchable(['name', 'account_number' , 'account_holder' , 'raast_id'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('livewire.banks.bank-component')->with([
            'banks' => $banks,
        ]);
    }
}
