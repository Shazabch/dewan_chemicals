<?php

namespace App\Livewire\Admin\Services;

use App\Models\Customer;
use App\Models\ServiceStockDetail;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

use Livewire\Component;

class ManageStockDetails extends Component
{

    public $stocks = [];
    public $clientStocks = [];
    public $clientStocktransfersSent = [];
    public $clientStocktransfersReceived = [];
    public $searchTerm = '';
    public $searchDetails = '';
    public $startDate = '';
    public $endDate = '';
    public $selectedStock = null;
    public $selectedUser = null;
    public $showDetails = false;
    public $selected_user_id = null;
    public $selected_user_model = null;
    public $modelType = null;
    public $users = [];

    public function mount($modelType)
    {
        $this->modelType = $modelType;
        if ($this->modelType == 'client') {
            $this->loadClientReport();
        } else {
            $this->loadStockDetails();
        }
    }
    public function loadClientReport()
    {

        $this->users = [];
        ///Apply Filters To Customers and Suppliers
        $suppliers = Supplier::query()
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->get();
        $clients = Customer::query()
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->get();

        foreach ($suppliers as $supplier) {
            if ($this->checkUserActive($supplier->id, 'Supplier')) {
                $this->users[] = [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'model' => 'Supplier',
                ];
            }
        }
        foreach ($clients as $client) {
            if ($this->checkUserActive($client->id, 'Customer')) {
                $this->users[] = [
                    'id' => $client->id,
                    'name' => $client->name,
                    'model' => 'Customer',
                ];
            }
        }
    }
    public function loadStockDetails()
    {
        $this->stocks = ServiceStockDetail::where('quantity', '>', 0)
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    })
                        ->orWhereHas('warehouse', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })

            ->with(['product', 'warehouse'])
            ->groupBy(['user_id', 'user_model'])
            ->orderBy('created_at', 'desc')
            ->selectRaw('sum(quantity) as quantity,count(id) as product_count, user_id, user_model')
            ->get();
    }
    public function updated($name, $value)
    {
        if ($name === 'searchTerm') {
            if ($this->modelType == 'client') {
                $this->loadClientReport();
            } else {
                $this->loadStockDetails();
            }
        }
        if ($name === 'searchDetails' || $name === 'startDate' || $name === 'endDate') {
            $this->viewDetails($this->selected_user_id, $this->selected_user_model);
        }
    }
    public function viewDetails($user_id, $user_model)
    {
        $this->selected_user_id = $user_id;
        $this->selected_user_model = $user_model;
        // Fetch records
        $query = ServiceStockDetail::query();
        $query->when($this->searchDetails, function ($query) {
            $query->where(function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchDetails . '%');
                });
            });
        });
        $query->where('user_id', $user_id)->where('user_model', $user_model);
        $this->selectedStock = $query->get();

        // Check if any record was found
        if ($this->selectedStock->isEmpty()) {
            // Handle the case where no data is found
            session()->flash('error', 'No stock details found for the given user.');
            $this->showDetails = false;
            return;
        }

        // Proceed safely now that we know at least one record exists
        $this->selectedUser = $this->selectedStock[0]->user ?? null;

        // Validate if user exists
        if (!$this->selectedUser) {
            session()->flash('error', 'User associated with the stock could not be found.');
            $this->showDetails = false;
            return;
        }

        $this->showDetails = true;
    }
    public function checkUserActive($user_id, $user_model)
    {

        $query = ServiceStockDetail::query();
        $query->where('user_id', $user_id)->where('user_model', $user_model);
        $this->selectedStock = $query->get();

        if ($this->selectedStock->isEmpty()) {
           return false;

            // $query = Stock::query();
            // $query->where('user_client_id', $user_id)->where('user_client_model', $user_model);
            // $Stock = $query->get();
            // if ($Stock->isEmpty()) {
            //     return false;
            // } else {
            //     return true;
            // }
        } else {
            return true;
        }
    }
    public function closeDetails()
    {
        $this->showDetails = false;
        $this->selectedStock = null;
        $this->selectedUser = null;
        $this->searchDetails = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->selected_user_id = null;
        $this->selected_user_model = null;
        if ($this->modelType == 'client') {
            $this->loadClientReport();
        } else {
            $this->loadStockDetails();
        }
    }
    public function clearFilters()
    {
        $this->searchTerm = '';
        $this->startDate = '';
        $this->endDate = '';
        if ($this->modelType == 'client') {
            $this->loadClientReport();
        } else {
            $this->loadStockDetails();
        }
    }
    public function viewClientReport($user_id, $user_model)
    {
        $this->selected_user_id = $user_id;
        $this->selected_user_model = $user_model;
        $query = ServiceStockDetail::query();
        $query->when($this->searchDetails, function ($query) {
            $query->where(function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchDetails . '%');
                });
            });
        });
        $query->where('user_id', $user_id)->where('user_model', $user_model);
        $this->selectedStock = $query->get();

        if ($this->selectedStock->isEmpty()) {
            // Handle the case where no data is found

            $this->dispatch('notify', status: 'error', message: 'No stock details found for the given user.');
            $this->showDetails = false;
            return;
        }

        // Proceed safely now that we know at least one record exists
        $this->selectedUser = $this->selectedStock[0]->user ?? null;

        // Validate if user exists
        if (!$this->selectedUser) {
            $this->dispatch('notify', status: 'error', message: 'User associated with the stock could not be found.');

            $this->showDetails = false;
            return;
        }
        $this->clientStock();
        $this->clientStocktransfersSent();
        $this->clientStocktransfersReceived();

        $this->showDetails = true;
    }
    public function clientStock()
    {

        $this->clientStocks = Stock::with(['warehouse', 'user', 'stockInOuts'])
            ->where(function ($query) {
                $query->where('title', 'like', '%' . $this->searchTerm . '%');
                $query->orWhere('tracking_id', 'like', '%' . $this->searchTerm . '%');
                $query->orWhereHas('warehouse', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%');
                })->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $startDate = Carbon::parse($this->startDate)->startOfDay();
                $endDate = Carbon::parse($this->endDate)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->where('user_id', $this->selected_user_id)->where('user_model', $this->selected_user_model)->get();
    }
    public function clientStocktransfersSent()
    {
        $this->clientStocktransfersSent = StockTransfer::with([
            'fromWarehouse',
            'toWarehouse',
            'fromUser',
            'toUser',
            'stockTransferDetails'
        ])
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('fromUser', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    })
                        ->orWhereHas('toUser', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->when($this->startDate, function ($query) {
                $query->whereDate('created_at', '>=', Carbon::parse($this->startDate));
            })
            ->when($this->endDate, function ($query) {
                $query->whereDate('created_at', '<=', Carbon::parse($this->endDate));
            })
            ->orderBy('created_at', 'desc')
            ->where(function ($query) {
                $query->where('from_user_id', $this->selected_user_id)->where('from_user_model', $this->selected_user_model);
            })
            ->get();
    }
    public function clientStocktransfersReceived()
    {
        $this->clientStocktransfersReceived = StockTransfer::with([
            'fromWarehouse',
            'toWarehouse',
            'fromUser',
            'toUser',
            'stockTransferDetails'
        ])
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('fromUser', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    })
                        ->orWhereHas('toUser', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->when($this->startDate, function ($query) {
                $query->whereDate('created_at', '>=', Carbon::parse($this->startDate));
            })
            ->when($this->endDate, function ($query) {
                $query->whereDate('created_at', '<=', Carbon::parse($this->endDate));
            })
            ->orderBy('created_at', 'desc')
            ->where(function ($query) {
                $query->where('to_user_id', $this->selected_user_id)->where('to_user_model', $this->selected_user_model);
            })
            ->get();
    }
    public function stockPDF()
    {

        $directory = 'stock_details_pdf';
        // Generate PDF
        $pdf = Pdf::loadView('pdf.services.stock-details', [
            'pageTitle' => 'Stock Detail Invoice',
            'selectedUser' => $this->selectedUser,
            'selectedStock' => $this->selectedStock,
        ])->setOption('defaultFont', 'Arial');

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = 'stock_details_invoice_' . now()->format('Ymd_His') . '.pdf'; // Unique filename
        $filepath = $directory . '/' . $filename;

        // Save the PDF to storage
        Storage::disk('public')->put($filepath, $pdf->output());
        // ServiceStockDetail::where('customer_id', $customerId)
        // ->update(['pdf_path' => $filepath]);

        $this->dispatch('notify', status: 'success', message: 'PDF generated successfully!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }
    public function clientReportPDF()
    {

        $directory = 'client_stock_report_pdf';
        // Generate PDF
        $pdf = Pdf::loadView('pdf.services.stock-client-report', [
            'pageTitle' => 'Client Stock Report',
            'clientStocktransfersSent' => $this->clientStocktransfersSent,
            'clientStocktransfersReceived' => $this->clientStocktransfersReceived,
            'selectedUser' => $this->selectedUser,
            'clientStocks' => $this->clientStocks,
            'selectedStock' => $this->selectedStock,
        ])->setOption('defaultFont', 'Arial');

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = 'client_stock_report_' . now()->format('Ymd_His') . '.pdf'; // Unique filename
        $filepath = $directory . '/' . $filename;

        // Save the PDF to storage
        Storage::disk('public')->put($filepath, $pdf->output());

        $this->dispatch('notify', status: 'success', message: 'PDF generated successfully!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }

    public function render()
    {
        return view('livewire.admin.services.manage-stock-details');
    }
}
