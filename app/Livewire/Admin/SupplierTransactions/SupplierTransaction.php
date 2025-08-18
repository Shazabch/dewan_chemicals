<?php

namespace App\Livewire\Admin\SupplierTransactions;

use App\Models\Supplier;
use App\Models\SupplierTransaction as ModalSupplierTransaction;
use App\Traits\ReversesSupplierTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierTransaction extends Component
{
    use WithPagination,ReversesSupplierTransaction;

    public $supplierId;
    public $supplier;

    public $search = '';
    public $startDate = null;
    public $endDate = null;

    public function mount($id)
    {
        $this->supplierId = $id;
        $this->supplier = Supplier::findOrFail($id);
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
    public function generateInvoice($supplierId, $startDate = null, $endDate = null, $search = null)
    {
        $directory = 'supplier_pdf';
        $supplier  = Supplier::find($supplierId);
        // Build query
        $query = ModalSupplierTransaction::query()
            ->where('supplier_id', $supplierId);

        if (!empty($search)) {
            $query->where('supplier_name', 'like', '%' . $search . '%');
        }

        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->get();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.supplier.supplier-transactions-pdf', [
            'pageTitle' => 'Supplier Invoice',
            'transactions' => $transactions,
            'supplier' => $supplier,
        ])->setOption('defaultFont', 'Arial');


        // Ensure directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = 'supplier_invoice_' . now()->format('Ymd_His') . '.pdf';
        $filepath = $directory . '/' . $filename;

        // Save the PDF to storage
        file_put_contents(storage_path('app/public/' . $filepath), $pdf->output());

        // Update path in DB
        // ModalSupplierTransaction::where('supplier_id', $supplierId)
        //     ->update(['pdf_path' => $filepath]);

        $this->dispatch('notify', status: 'success', message: 'Supplier PDF generated successfully!');

        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }
    public function confirmReverse($id)
    {

        $this->dispatch('confirmReverse', id: $id);
    }
    public function reverseTransaction($id, $reason)
    {
        $transaction = ModalSupplierTransaction::find($id);

        if (!$transaction) {
            $this->dispatch('notify', type: 'error', message: 'Transaction not found.');
            return;
        }

        try {
            $this->reverseSupplierTransaction($transaction, $reason);
            $this->dispatch('notify', type: 'success', message: 'Transaction reversed successfully!');
        } catch (\Exception $e) {

            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }


    public function render()
    {
        $transactions = ModalSupplierTransaction::with(['supplier', 'bank'])
            ->where('supplier_id', $this->supplierId)
            ->when($this->search, function ($query) {

                $query->whereHas('bank', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('supplier', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
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

        return view('livewire.admin.supplier-transactions.supplier-transaction', [
            'transactions' => $transactions,
        ]);
    }
}
