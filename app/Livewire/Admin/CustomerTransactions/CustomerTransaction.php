<?php

namespace App\Livewire\Admin\CustomerTransactions;

use App\Models\CustomerTransaction as ModalCustomerTransaction;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Traits\ReversesCustomerTransaction;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerTransaction extends Component
{
    use WithPagination, ReversesCustomerTransaction;

    public $customerId;
    public $customer;
    public $search = '';
    public $perPage = 20;
    public $startDate;
    public $endDate;




    public function mount($customerId)
    {

        $this->customerId = $customerId;

        $this->customer = Customer::findOrFail($customerId);
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


    public function generateInvoice($customerId, $startDate = null, $endDate = null, $search = null)
    {
        $directory = 'customer_pdf';
        $customer  = Customer::find($customerId);
        // Build query
        $query = ModalCustomerTransaction::query()
            ->where('customer_id', $customerId);

        if (!empty($search)) {
            $query->where('customer_name', 'like', '%' . $search . '%');
        }

        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->get();

        // Generate PDF
        $pdf = Pdf::loadView('admin.partials.customer-pdf', [
            'pageTitle' => 'Customer Invoice',
            'transactions' => $transactions,
            'customer' => $customer,
        ])->setOption('defaultFont', 'Arial');

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }



        $filename = 'customer_invoice_' . now()->format('Ymd_His') . '.pdf'; // Unique filename
        $filepath = $directory . '/' . $filename;


        // Save the PDF to storage
        // Storage::disk('public')->put($filepath, $pdf->output());

        file_put_contents(storage_path('app/public/' . $filepath), $pdf->output());

        $this->dispatch('notify', status: 'success', message: 'Customer PDF generated successfully!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }
    public function confirmReverse($id)
    {
        // We dispatch the event that our JavaScript listener is waiting for.
        $this->dispatch('confirmReverse', id: $id);
    }
    public function reverseTransaction($id,$reason)
    {
        $transaction = ModalCustomerTransaction::find($id);

        if (!$transaction) {
            $this->dispatch('notify', type: 'error', message: 'Transaction not found.');
            return;
        }

        try {
            $this->reverseCustomerTransaction($transaction,$reason);
            $this->dispatch('notify', type: 'success', message: 'Transaction reversed successfully!');
        } catch (\Exception $e) {

            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }


    public function render()
    {
        $transactions = ModalCustomerTransaction::with(['customer', 'bank'])
            ->where('customer_id', $this->customerId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('customer', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('bank', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.customer-transactions.customer-transaction', [
            'transactions' => $transactions,
        ]);
    }
}
