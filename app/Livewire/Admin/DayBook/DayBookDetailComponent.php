<?php

namespace App\Livewire\Admin\DayBook;

use App\Models\CustomerTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\DailyBookDetail;
use App\Models\SupplierTransaction;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class DayBookDetailComponent extends Component
{
    public $bookDetails = [];
    public $dailyBookDate;
    public $opening_balance;
    public $closing_balance;
    public $source;
    public $search = '';
    public function mount($date)
    {
        $this->dailyBookDate = $date;
        $this->loadBookDetails();
    }

    function loadBookDetails()
    {

        $query = DailyBookDetail::query();
        if (!empty($this->search)) {
            $query->where('source', 'like', '%' . $this->search . '%');
        }
        $query->whereDate('date', $this->dailyBookDate)->orderBy('id', 'desc');
        $this->bookDetails = $query->get();
        $this->calculateBalances();
    }
    public function updatedSource()
    {
        $this->loadBookDetails();
    }
    public function updatedSearch()
    {
        $this->loadBookDetails();
    }

    public function calculateBalances()
    {
        $details = DailyBookDetail::whereDate('date', $this->dailyBookDate)
            ->orderBy('date', 'desc')
            ->get();

        if ($details->isEmpty()) {
            $this->opening_balance = 0;
            $this->closing_balance = 0;
        } else {
            $this->opening_balance = $details->first()?->opening_balance ?? 0;
            $this->closing_balance = $details->last()?->closing_balance ?? 0;
        }
    }
    public function generatePdf($date)
    {
        $directory = 'daybook_pdf';

        // Get book details again to ensure fresh data
        $query = DailyBookDetail::query()
            ->whereDate('date', $date);

        // if (!empty($this->search)) {
        //     $query->where('source', 'like', '%' . $this->search . '%');
        // }


        $bookDetails = $query->orderBy('id', 'asc')->get();

        if ($bookDetails->isEmpty()) {
            $opening_balance = 0;
            $closing_balance = 0;
        } else {
            $opening_balance = $bookDetails->first()?->opening_balance ?? 0;
            $closing_balance = $bookDetails->last()?->closing_balance ?? 0;
        }
        // Generate PDF
        $pdf = Pdf::loadView('admin.partials.daybook-pdf', [
            'pageTitle' => 'Day Book Report',
            'bookDetails' => $bookDetails,
            'dailyBookDate' => $date,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
        ])->setOption('defaultFont', 'Arial');

        // Ensure directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = 'day_book_' . now()->format('Ymd_His') . '.pdf';
        $filepath = $directory . '/' . $filename;

        // Save PDF
        file_put_contents(storage_path('app/public/' . $filepath), $pdf->output());

        // Optional: store path in DB if needed

        // Notify & return download
        $this->dispatch('notify', status: 'success', message: 'Day Book PDF generated!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }


    public function render()
    {
        return view('livewire.admin.day-book.day-book-detail-component');
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
