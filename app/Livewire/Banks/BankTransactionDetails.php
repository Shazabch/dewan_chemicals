<?php

namespace App\Livewire\Banks;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\CustomerTransaction;
use App\Models\SupplierTransaction;
use Livewire\Component;
use Livewire\WithPagination;

class BankTransactionDetails extends Component
{
    use WithPagination;
    public $bankId;
    public $bank;
    public $perPage = 20;


    public $search = '';
    public $startDate = null;
    public $endDate = null;
    public function mount($bankId)
    {

        $this->bankId = $bankId;

        $this->bank = Bank::findOrFail($bankId);
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->startDate = null;
        $this->endDate = null;
    }

    public function render()
    {
        $transactions = BankTransaction::where('bank_id', $this->bankId)
            ->where(function ($query) {
                $query->when($this->search, function ($q) {
                    $q->where('source', 'like', '%' . $this->search . '%')
                        ->orWhere('data_model', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->startDate, function ($query) {
                $query->whereDate('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query) {
                $query->whereDate('created_at', '<=', $this->endDate);
            })
            ->latest()
            ->paginate(20);

        return view('livewire.banks.bank-transaction-details', [
            'transactions' => $transactions,
        ]);
    }

    public function redirectDataModel($id, $dataModel)
    {

        switch ($dataModel) {

            case 'Sale':
                return redirect()->to('/admin/manage/sale/?module_id=' . $id . '#module_id_' . $id);
                break;
            case 'Purchase':
                return  redirect()->to('/admin/manage/purchase/?module_id=' . $id . '#module_id_' . $id);
                break;
            case 'Expense':
                return redirect()->to('/admin/manage/expense/?module_id=' . $id . '#module_id_' . $id);
                break;
            case 'Stock':
                return redirect()->to('/admin/services/stock-in/?module_id=' . $id . '#module_id_' . $id);
                break;
            case 'CustomerTransaction':
                $customerTransaction = CustomerTransaction::find($id);
                if ($customerTransaction) {
                    $customerId = $customerTransaction->customer_id;
                    return redirect()->to('/admin/customer/view/' . $customerId . '?module_id=' . $id . '#module_id_' . $id);
                }
                break;
            case 'SupplierTransaction':
                $cupplierTransaction = SupplierTransaction::find($id);
                if ($cupplierTransaction) {
                    $supplierId = $cupplierTransaction->supplier_id;
                    return redirect()->to('/admin/supplier/view/' . $supplierId . '?module_id=' . $id . '#module_id_' . $id);
                }

                break;
        }
    }
}
