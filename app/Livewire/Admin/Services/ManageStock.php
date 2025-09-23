<?php

namespace App\Livewire\Admin\Services;

use App\Models\Bank;
use App\Models\Customer;
use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\ServiceStockDetail;
use App\Models\Stock;
use App\Models\StockInOut;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Traits\DailyBookEntryTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Traits\HandlesBankPayments;
use Livewire\Component;
use App\Traits\ManagesExpenseTransactions;

class ManageStock extends Component
{
    use HandlesBankPayments;
    use DailyBookEntryTrait;
    use ManagesExpenseTransactions;

    public $stocks = [];
    public $users = [];
    public $userClients = [];
    public $note = '';
    public $selected_id;
    public ?string $user_identifier = ''; // The wire:model property, e.g., "Supplier-15"
    public $products = [];
    public $suppliers = [];
    public $clients = [];
    public $warehouses = [];

    public $searchTerm = '';
    public $startDate = '';
    public $endDate = '';
    public $isCreating = false;
    public $editMode = false;
    public $warehouse_id;

    public $stockItems = [];
    public $title;
    public $date;
    public $stock_type;

    public $selectedStock = null;
    public $showDetails = false;


    public $user_id;
    public $user_client_id;
    public $driver_name;
    public $driver_contact;
    public $vehicle_number;
    public $labour;
    public $fare;

    public $searchTermDetails = '';
    public $startDateDetails = '';
    public $endDateDetails = '';
    public $selected_stock_id = null;


    public $banks = [];
    public $paymentStock = null;
    public $modal_rec_amount;
    public $bankId;
    public $modal_payment_method = '';
    public $modal_rec_bank;
    public $modal_receivable_amount;
    public $modal_title = '';
    public $tracking_id;

    public $expense_type_id, $date_of_expense, $exp_amount, $bank_id, $expense_id;
    public $categories = [];





    protected function rules()
    {
        $rules = [
            'stock_type' => 'required|in:in,out',
            'product_id' => 'required|integer',

        ];
        return $rules;
    }

    protected function rulesPayment()
    {
        return [
            'modal_payment_method' => 'required|string',

            'modal_rec_amount' => [
                Rule::requiredIf(function () {
                    return in_array($this->modal_payment_method, ['cash', 'both']);
                }),


            ],

            'modal_rec_bank' => [
                Rule::requiredIf(function () {
                    return in_array($this->modal_payment_method, ['bank', 'both']);
                }),


            ],
        ];
    }
    protected function messages()
    {
        return [
            'modal_rec_amount.required' => 'Cash amount is required when payment method is cash or both.',
            'modal_rec_bank.required'   => 'Bank amount is required when payment method is bank or both.',
            'stockItems.*.product_id.required'   => 'Pleae Select Product.',
        ];
    }

    public function mount($type)
    {
        $this->stock_type = $type;
        $this->loadStocks();
    }
    public function loadStocks()
    {

        $this->stocks = Stock::with(['warehouse', 'user'])->where('stock_type', $this->stock_type)
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
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->orderBy('tracking_id', 'desc')
            ->get();
    }
    public function createStock()
    {
        $this->resetForm();

        $this->isCreating = !$this->isCreating;
        $this->showDetails = false;
        $this->tracking_id = Stock::generateTrackingId($this->stock_type);
        $this->users = [];
        $this->userClients = [];
        $this->date = now()->format('Y-m-d');
        $this->getProducts();
        $this->stockItems = [
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 0, 'total_amount' => 0, 'net_weight' => 0, 'is_kg' => false]
        ];
        $this->users = $this->loadUsers();
        $this->userClients = $this->loadUserClients();

        $this->warehouses =  Warehouse::active()->orderBy('name')->get();
        $this->warehouses = $this->warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'text' => $warehouse->name,
            ];
        })->toArray();
    }
    public function updated($name, $value)
    {
        if ($name === 'searchTerm' || $name === 'startDate' || $name === 'endDate') {
            $this->loadStocks();
        }
        if ($name === 'searchTermDetails' || $name === 'startDateDetails' || $name === 'endDateDetails') {
            $this->viewDetails($this->selected_stock_id);
        }
        if ($name === 'warehouse_id') {
            $this->getProducts();
            $this->users = $this->loadUsers();
        }
        if (str_starts_with($name, 'stockItems.')) {
            $index = explode('.', $name)[1];
            $product_id = (float)$this->stockItems[$index]['product_id'];
            $quant = (float)$this->stockItems[$index]['quantity'];
            $net_weight = (float)$this->stockItems[$index]['net_weight'];
            $unit_price = (float)$this->stockItems[$index]['unit_price'];
            if ($product_id) {
                $product = Product::find($product_id);
                if ($product && $product->unit && strtolower($product->unit->name) == 'kg') {
                    $this->stockItems[$index]['is_kg'] = true;
                    $this->stockItems[$index]['total_amount'] = $net_weight * $unit_price;
                } else {
                    $this->stockItems[$index]['is_kg'] = false;
                    $this->stockItems[$index]['total_amount'] = $quant * $unit_price;
                }
            }
            $this->recalculateTotalAmount();
        }
        if ($name === 'expense_type_id') {
            $this->expense_type_id = (int)$value;
            $expenseType = ExpenseType::find($this->expense_type_id);
            if ($expenseType && $expenseType->name == 'Fare') {
                $this->exp_amount = $this->fare;
            } elseif ($expenseType && $expenseType->name == 'Loading') {
                $this->exp_amount = $this->labour;
            }
        }
    }
    public function getProducts()
    {
        $warehouse = $this->warehouse_id;
        // if (!$warehouse) {
        //     $this->products = [];
        //     $this->dispatch('notify', status: 'error', message: 'Select Whereouse First');
        // }
        $this->products = Product::all()->map(fn($p) => [
            'id' => $p->id,
            'text' => $p->name,
        ])->toArray();
    }
    public function editDetails($stockId)
    {
        $this->resetForm();

        $this->isCreating = !$this->isCreating;
        $this->showDetails = false;
        $this->users = [];
        $this->users = $this->loadUsers();
        $this->userClients = $this->loadUserClients();
        $this->getProducts();
        $this->warehouses =  Warehouse::active()->orderBy('name')->get();
        $this->warehouses = $this->warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'text' => $warehouse->name,
            ];
        })->toArray();
        $this->selected_stock_id = $stockId;

        $searchTermDetails = '%' . $this->searchTermDetails . '%'; // assuming this is coming from Livewire or input
        $start = $this->startDateDetails ? Carbon::parse($this->startDateDetails)->startOfDay() : null;
        $end = $this->endDateDetails ? Carbon::parse($this->endDateDetails)->endOfDay() : null;

        $this->selectedStock = Stock::with(['stockInOuts' => function ($query) use ($searchTermDetails, $start, $end) {
            $query->whereHas('product', function ($q) use ($searchTermDetails) {
                $q->where('name', 'like', $searchTermDetails);
            })->with('product');
            if ($start && $end) {
                $query->whereBetween('date', [$start, $end]);
            } elseif ($start) {
                $query->where('date', '>=', $start);
            } elseif ($end) {
                $query->where('date', '<=', $end);
            }
        }])->find($this->selected_stock_id);
        $this->title = $this->selectedStock->title;
        $this->date = $this->selectedStock->date;
        $this->warehouse_id = $this->selectedStock->warehouse_id;
        $this->stock_type = $this->selectedStock->stock_type;
        $this->labour = $this->selectedStock->labour;
        $this->fare = $this->selectedStock->fare;
        $this->vehicle_number = $this->selectedStock->vehicle_number;
        $this->driver_name = $this->selectedStock->driver_name;
        $this->driver_contact = $this->selectedStock->driver_contact;

        $user_id = $this->selectedStock->user_id ?? null;
        $user_model = $this->selectedStock->user_model ?? null;
        $this->user_id = $user_model . '-' . $user_id;

        $user_client_id = $this->selectedStock->user_client_id ?? null;
        $user_client_model = $this->selectedStock->user_client_model ?? null;
        $this->user_client_id = $user_client_model . '-' . $user_client_id;
        $this->stockItems = [];

        foreach ($this->selectedStock->stockInOuts as $item) {
            $this->stockItems[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_amount' => $item->total_amount,
                'net_weight' => $item->net_weight ?? 0,
                'is_kg' => strtolower($item->product->unit->name) === 'kg', // fallback if unit missing
            ];
        }
    }
    public function addItem()
    {
        $this->stockItems[] = ['product_id' => null, 'quantity' => 1, 'unit_price' => 0, 'total_amount' => 0, 'net_weight' => 0, 'is_kg' => false];
    }

    public function removeItem($index)
    {
        unset($this->stockItems[$index]);
        $this->stockItems = array_values($this->stockItems); // reindex
    }
    public function saveStock()
    {

        $this->validate([
            'title' => 'nullable|string',
            'date' => 'required',
            'stock_type' => 'required|in:in,out',
            'warehouse_id' => 'required|integer',
            'user_id' => 'required',
            'user_client_id' => 'nullable',
            'labour' => 'nullable',
            'fare' => 'nullable',
            'vehicle_number' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'driver_contact' => 'nullable|string',
            'stockItems.*.product_id' => 'required|integer',
            'stockItems.*.quantity' => 'required|numeric|min:1',
        ]);

        list($selecteduserModel, $selecteduserId) = explode('-', $this->user_id, 2);
        if ($this->stock_type == 'out') {
            list($selecteduserClientModel, $selecteduserClientId) = explode('-', $this->user_client_id, 2);
        }

        /////////////////  Check Stock Availability for all Products
        foreach ($this->stockItems as $item) {
            $availableStock = $this->checkAvailableStock($item['product_id'], $this->warehouse_id, $selecteduserId, $selecteduserModel);
            $availableStockWeight = $this->checkAvailableStockWeight($item['product_id'], $this->warehouse_id, $selecteduserId, $selecteduserModel);
            if ($this->stock_type == 'out') {
                ///// Product
                $product = Product::find($item['product_id']);
                if ($product) {
                    $productName = $product->name;

                    if ($product->unit && strtolower($product->unit->name) == 'kg' && ($availableStockWeight < $item['net_weight'] || $availableStockWeight == 0)) {
                        $this->dispatch('notify', status: 'error', message: 'Insufficient Weight for product: ' . $productName);
                        return;
                    } else if ($availableStock < $item['quantity']) {
                        $this->dispatch('notify', status: 'error', message: 'Insufficient Quantity for product: ' . $productName);
                        return;
                    }
                } else {
                    $productName = 'Unknown Product';
                }
            }
        }

        // Create or update stock
        if ($this->selected_stock_id) {
            $stock = Stock::findOrFail($this->selected_stock_id);
            $stock->update([
                'title' => $this->title,
                'date' => $this->date,
                'stock_type' => $this->stock_type,
                'warehouse_id' => $this->warehouse_id,
                'user_id' => $selecteduserId,
                'user_model' => $selecteduserModel,
                'user_client_id' => $selecteduserClientId ?? null,
                'user_client_model' => $selecteduserClientModel ?? null,
                'labour' => $this->labour,
                'fare' => $this->fare,
                'vehicle_number' => $this->vehicle_number,
                'driver_name' => $this->driver_name,
                'driver_contact' => $this->driver_contact,
            ]);
            foreach ($stock->stockInOuts as $stk) {

                $this->updateServiceStockOld($stk->product_id, $stk->quantity, $this->stock_type, $this->warehouse_id, $selecteduserId, $selecteduserModel, $stk->net_weight);
            }
            // Delete old items
            $stock->stockInOuts()->delete();
        } else {
            $stock = Stock::create([
                'title' => $this->title,
                'tracking_id' => $this->tracking_id,
                'date' => $this->date,
                'stock_type' => $this->stock_type,
                'warehouse_id' => $this->warehouse_id,
                'user_id' => $selecteduserId,
                'user_model' => $selecteduserModel,
                'user_client_id' => $selecteduserClientId ?? null,
                'user_client_model' => $selecteduserClientModel ?? null,
                'labour' => $this->labour,
                'fare' => $this->fare,
                'vehicle_number' => $this->vehicle_number,
                'driver_name' => $this->driver_name,
                'driver_contact' => $this->driver_contact,
            ]);
        }
        foreach ($this->stockItems as $item) {
            StockInOut::create([
                'stock_id' => $stock->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'net_weight' => $item['net_weight'],
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total_amount'],
            ]);

            // Update service stock
            $this->updateServiceStock(
                $item['product_id'],
                $item['quantity'],
                $this->stock_type,
                $this->warehouse_id,
                $selecteduserId,
                $selecteduserModel,
                $item['net_weight']
            );
        }
        // Save items

        $stock->total_amount = $this->recalculateTotalAmount();
        $stock->due_amount = $this->recalculateTotalAmount();
        $stock->save();

        $this->dispatch('notify', status: 'success', message: $this->selected_stock_id ? 'Stock updated successfully' : 'Stock created successfully');
        $this->isCreating = false;
        // Reset state
        $this->resetForm();
        $this->loadStocks();
    }

    public function resetForm()
    {

        $this->selected_stock_id = null;
        $this->title = '';
        $this->date = '';
        $this->warehouse_id = null;
        $this->user_id = null;
        $this->user_client_id = null;
        $this->labour = '';
        $this->fare = '';
        $this->vehicle_number = '';
        $this->driver_name = '';
        $this->driver_contact = '';
        $this->stockItems = [
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 0, 'total_amount' => 0, 'net_weight' => 0, 'is_kg' => false]
        ];
    }
    public function viewDetails($stockId)
    {
        $this->selected_stock_id = $stockId;

        $searchTermDetails = '%' . $this->searchTermDetails . '%'; // assuming this is coming from Livewire or input
        $start = $this->startDateDetails ? Carbon::parse($this->startDateDetails)->startOfDay() : null;
        $end = $this->endDateDetails ? Carbon::parse($this->endDateDetails)->endOfDay() : null;

        $this->selectedStock = Stock::with(['stockInOuts' => function ($query) use ($searchTermDetails, $start, $end) {
            $query->whereHas('product', function ($q) use ($searchTermDetails) {
                $q->where('name', 'like', $searchTermDetails);
            })->with('product');
            if ($start && $end) {
                $query->whereBetween('date', [$start, $end]);
            } elseif ($start) {
                $query->where('date', '>=', $start);
            } elseif ($end) {
                $query->where('date', '<=', $end);
            }
        }])->find($this->selected_stock_id);

        $this->showDetails = true;
        $this->isCreating = false;
    }
    public function openExpenseModal($id)
    {
        $this->selected_stock_id = $id ?? null;
        $selectedStock = Stock::find($id);
        $this->fare = $selectedStock->fare ?? 0;
        $this->labour = $selectedStock->labour ?? 0;
        // Ensure these categories exist
        $requiredCategories = ['Fare', 'Loading'];

        foreach ($requiredCategories as $category) {
            ExpenseType::firstOrCreate(['name' => $category]);
        }

        // Now fetch them
        $this->categories = ExpenseType::orderBy('name')
            ->whereIn('name', $requiredCategories)
            ->get();

        $this->banks = Bank::all();
        $this->expense_type_id = null;
        $this->date_of_expense = now()->format('Y-m-d');
        $this->exp_amount = 0.00;

        $this->dispatch('open-expense-modal');
    }
    public function storeExpense()
    {
        $this->validate([
            'expense_type_id' => 'required|exists:expense_types,id',
            'date_of_expense' => 'required|date',
            'exp_amount'      => 'required|numeric|min:1',
            'bank_id'         => 'nullable|exists:banks,id',
        ]);
        if ($this->exp_amount <= 0) {
            $this->dispatch('notify', status: 'error', message: 'Expense amount must be greater than zero.');
            return;
        }


        $this->createOrUpdateExpenseTransaction(
            $this->expense_type_id,
            $this->date_of_expense,
            $this->exp_amount,
            $this->bank_id,
            $this->note,
            $this->selected_id,
            'App\Models\Stock',
            $this->selected_stock_id
        );
        $notification = $this->selected_id ? 'Expense updated successfully' : 'Expense added successfully';
        $this->dispatch('close-modal');
        $this->dispatch('notify', status: 'success', message: $notification);

        $this->reset(['expense_type_id', 'date_of_expense', 'exp_amount', 'note', 'bank_id', 'expense_id']);
    }

    public function recalculateTotalAmount()
    {

        $total = 0;
        foreach ($this->stockItems as $item) {
            $total += $item['total_amount'];
        }
        return $total;
    }
    public function stockTotalAmount()
    {
        $stock = Stock::with('stockInOuts')->find($this->selectedStock->id);
        if (!$stock) {
            return 0;
        }
        $total = 0;
        foreach ($stock->stockInOuts as $item) {
            $total += $item->total_amount;
        }
        return $total;
    }
    // In your Livewire Component

    private function loadUsers(): array
    {
        $formattedUsers = [];

        // Get data from models
        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();
        $clients = Customer::select('id', 'name')->orderBy('name')->get();

        // Add Suppliers to the array with a unique identifier
        foreach ($suppliers as $supplier) {
            // Key: "Supplier-12", Value: "Supplier Name (Vendor)"
            if ($this->checkUserActive($supplier->id, 'Supplier')) {
                $formattedUsers[] = [
                    'id'   => 'Supplier-' . $supplier->id,
                    'text' => $supplier->name . ' (Supplier)',
                ];
            }
        }

        // Add Clients to the array with a unique identifier
        foreach ($clients as $client) {
            // Key: "Customer-8", Value: "Client Name (Client)"
            if ($this->checkUserActive($client->id, 'Customer')) {
                $formattedUsers[] = [
                    'id'   => 'Customer-' . $client->id,
                    'text' => $client->name . ' (Customer)',
                ];
            }
        }

        return $formattedUsers;
    }
    private function loadUserClients(): array
    {
        $formattedUsers = [];

        // Get data from models
        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();
        $clients = Customer::select('id', 'name')->orderBy('name')->get();

        // Add Suppliers to the array with a unique identifier
        foreach ($suppliers as $supplier) {

            $formattedUsers[] = [
                'id'   => 'Supplier-' . $supplier->id,
                'text' => $supplier->name . ' (Supplier)',
            ];
        }

        // Add Clients to the array with a unique identifier
        foreach ($clients as $client) {

            $formattedUsers[] = [
                'id'   => 'Customer-' . $client->id,
                'text' => $client->name . ' (Customer)',
            ];
        }

        return $formattedUsers;
    }
    public function checkUserActive($user_id, $user_model)
    {


        $selectedStock = $this->checkAvailableStockForWere($user_id, $user_model);

        if ($selectedStock == 0 && $this->stock_type == 'out') {
            return false;
        } else {
            return true;
        }
    }
    public function updateServiceStock($product_id, $quantity, $stock_type, $warehouse_id, $user_id, $user_model, $net_weight)
    {
        $stockDetail = ServiceStockDetail::where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('user_id', $user_id)
            ->where('user_model', $user_model)
            ->first();

        if ($stockDetail) {
            if ($stock_type == 'in') {
                $stockDetail->increment('quantity', $quantity);
                $stockDetail->increment('net_weight', $net_weight);
            } else {
                $stockDetail->decrement('quantity', $quantity);
                $stockDetail->decrement('net_weight', $net_weight);
            }
        } else {
            ServiceStockDetail::create([
                'product_id' => $product_id,
                'warehouse_id' => $warehouse_id,
                'user_id' => $user_id,
                'user_model' => $user_model,
                'quantity' => ($stock_type == 'in') ? $quantity : -$quantity,
                'net_weight' => ($stock_type == 'in') ? $net_weight : -$net_weight,
            ]);
        }
    }
    public function updateServiceStockOld($product_id, $quantity, $stock_type, $warehouse_id, $user_id, $user_model, $net_weight)
    {
        $stockDetail = ServiceStockDetail::where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('user_id', $user_id)
            ->where('user_model', $user_model)
            ->first();

        if ($stockDetail) {
            if ($stock_type == 'out') {
                $stockDetail->increment('quantity', $quantity);
                $stockDetail->increment('net_weight', $net_weight);
            } else {
                $stockDetail->decrement('quantity', $quantity);
                $stockDetail->decrement('net_weight', $net_weight);
            }
        }
    }
    public function checkAvailableStockForWere($user_id, $user_model)
    {
        $stockDetail = ServiceStockDetail::where('warehouse_id', $this->warehouse_id)
            ->where('user_id', $user_id)
            ->where('user_model', $user_model)
            ->sum('quantity');

        if ($stockDetail) {
            return $stockDetail ?? 0;
        }
        return 0;
    }
    public function checkAvailableStock($product_id, $warehouse_id, $user_id, $user_model)
    {
        $stockDetail = ServiceStockDetail::where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('user_id', $user_id)
            ->where('user_model', $user_model)
            ->first();

        if ($stockDetail) {
            return $stockDetail->quantity ?? 0;
        }
        return 0;
    }
    public function checkAvailableStockWeight($product_id, $warehouse_id, $user_id, $user_model)
    {
        $stockDetail = ServiceStockDetail::where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('user_id', $user_id)
            ->where('user_model', $user_model)
            ->first();

        if ($stockDetail) {
            return $stockDetail->net_weight ?? 0;
        }
        return 0;
    }
    public function clearFilters()
    {
        $this->searchTerm = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->loadStocks();
    }
    public function clearFiltersDetails()
    {
        $this->searchTermDetails = '';
        $this->startDateDetails = '';
        $this->endDateDetails = '';
        $this->viewDetails($this->selected_stock_id);
    }
    public function closeDetails()
    {
        $this->showDetails = false;
        $this->selectedStock = null;
        $this->startDate = '';
        $this->startDateDetails = '';
        $this->endDate = '';
        $this->endDateDetails = '';
        $this->searchTerm = '';
        $this->searchTermDetails = '';
        $this->selected_stock_id = null;
        $this->loadStocks();
    }
    public function stockPDF()
    {

        $directory = 'stock_' . $this->stock_type . '_pdf';
        // Generate PDF
        $pdf = Pdf::loadView('pdf.services.stock-in-out', [
            'pageTitle' => 'Stock ' . $this->stock_type . ' Invoice',
            'selectedStock' => $this->selectedStock,
            'stockTotalAmount' => $this->stockTotalAmount(),
        ])->setOption('defaultFont', 'Arial');

        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        $filename = 'stock_' . $this->stock_type . '_invoice_' . now()->format('Ymd_His') . '.pdf'; // Unique filename
        $filepath = $directory . '/' . $filename;

        // Save the PDF to storage
        Storage::disk('public')->put($filepath, $pdf->output());

        $this->dispatch('notify', status: 'success', message: 'PDF generated successfully!');
        return response()->download(storage_path('app/public/' . $filepath), $filename);
    }
    public function payMentModal($id)
    {
        $this->banks = Bank::where('name', '!=', 'Cash')->get();
        $this->paymentStock = Stock::find($id);
        if (!$this->paymentStock) {
            $this->dispatch('notify', status: 'error', message: 'Stock not found');
            return;
        }
        $this->modal_title = $this->paymentStock->title;
        $this->modal_receivable_amount = $this->paymentStock->due_amount;
        $this->modal_payment_method = 'cash';
        $this->modal_rec_bank = 0.00;
        $this->modal_rec_amount = 0.00;
        $this->dispatch('openPaymentModal');
    }


    public function submitPayment()
    {

        $this->validate($this->rulesPayment());

        $stock = $this->paymentStock;

        $amount_cash = $this->modal_rec_amount;
        $amount_bank = $this->modal_rec_bank;
        $amount = $this->modal_payment_method == 'cash' ? $amount_cash : $amount_bank;
        $amount = $this->modal_payment_method == 'bank' ? $amount_bank : $amount_cash;
        $amount = $this->modal_payment_method == 'both' ? $amount_cash + $amount_bank : $amount;
        $amount = abs($amount);

        $stock->recieved_amount += $amount;
        $stock->due_amount -= $amount;
        $stock->cash_amount += $amount_cash;
        $stock->bank_amount += $amount_bank;
        $stock->bank_id = $this->bankId;
        $stock->save();

        $dataModal = $this->stock_type == 'in' ? 'StockIn' : 'StockOut';

        $this->handlePaymentTransaction(
            $this->modal_payment_method,
            $amount_cash,
            $amount_bank,
            $this->bankId,
            $stock->id,
            $dataModal,
            'debit'
        );

        $this->handleDailyBookEntries($amount_cash, $amount_bank, 'debit', $this->modal_payment_method, $dataModal, $stock->id);

        session()->flash('success', 'Payment updated successfully!');
        $this->dispatch('notify', status: 'success', message: 'Payment updated successfully!');
        $this->loadStocks();
        $this->paymentStock = null;
        $this->modal_rec_amount = 0.00;
        $this->modal_rec_bank = 0.00;
        $this->modal_payment_method = '';
        $this->modal_rec_bank = 0.00;
        $this->modal_receivable_amount = 0.00;
        $this->modal_title = '';
        $this->bankId = null;

        $this->dispatch('closePaymentModal');
    }
    public function render()
    {
        $this->dispatch('re-init-select-2-component');

        return view('livewire.admin.services.manage-stock');
    }
}
