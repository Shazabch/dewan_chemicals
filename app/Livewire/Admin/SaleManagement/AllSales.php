<?php

namespace App\Livewire\Admin\SaleManagement;

use App\Models\Sale;
use Livewire\Component;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\SaleDetail;
use App\Models\Action;
use App\Livewire\Admin\CustomerTransactions\CustomerTransaction;
use App\Livewire\Admin\WareHouse\WareHouseDetail;
use App\Models\Action as ModelsAction;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\CustomerPayment;
use App\Models\CustomerTransaction as ModelsCustomerTransaction;
use App\Models\ExpenseType;
use App\Models\SaleDetails;
use App\Models\WareHouseDetailHistory;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Traits\DailyBookEntryTrait;
use App\Traits\ManagesExpenseTransactions;
use Illuminate\Notifications\Action as NotificationsAction;

class AllSales extends Component
{
    use DailyBookEntryTrait;
    use ManagesExpenseTransactions;

    public $sales = [];
    public $banks = [];
    public $selectedSale = null;
    public $saleDetails = [];
    public $isEditing = false;
    public $isCreating = false;
    public $isDeleting = false;
    public $searchTerm = '';
    public $searchByDate;
    public $perPage = 10;

    public $successMessage;

    public $invoice_no;
    public $customer_id;
    public $sale_date;
    public $warehouse_id;


    public $searchQuery = '';
    public $searchResults = [];
    public $products = [];

    public $customers = [];
    public $warehouses = [];

    public $discount = 0;
    public $total_price = 0;
    public $note = '';

    public $received_amount = 0;
    public $due_amount = 0;
    public $receivable_amount = 0;
    public $vehicle_number = '';
    public $driver_name = '';
    public $driver_contact = '';
    public $fare = 0;
    public $loading = 0;

    public $editMode = false;
    public $saleId = null;

    public $searchInput = '';

    public $paymentSale;
    public $modal_invoice_no;
    public $modal_customer_name;
    public $modal_rec_amount;
    public $bankId;
    public $modal_payment_method = '';
    public $modal_rec_bank;
    public $modal_receivable_amount;
    public $searchAbleProducts = [];
    public $selected_product_id = null;

    public $expense_type_id, $date_of_expense, $exp_amount, $bank_id, $expense_id;
    public $categories = [];
    public $selected_id;


    protected function rules()
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
        ];
    }

    public function mount()
    {

        $this->loadSales();
    }
    public function loadSales()
    {
        $this->sales = Sale::with(['customer', 'warehouse'])
            ->where(function ($query) {
                $query->where('invoice_no', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('customer', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    })
                    ->orWhereHas('warehouse', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    });
            })
            ->when($this->searchByDate, function ($query) {
                // Parse the date range
                [$start, $end] = explode(' - ', $this->searchByDate);
                $startDate = Carbon::parse($start)->startOfDay();
                $endDate = Carbon::parse($end)->endOfDay();

                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->orderBy('invoice_no','desc')
            ->get();
    }


    public function createSale()
    {
        $this->resetForm();
        $this->saleId = null;
        $this->isCreating = !$this->isCreating;
        $this->selectedSale = null;
        $this->saleDetails = [];

        $this->sale_date = now()->format('d-m-Y');
        $this->customers = Customer::select('id', 'name', 'mobile')->get();
        $this->customers = $this->customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'text' => $customer->name,
            ];
        })->toArray();
        $this->warehouses =  Warehouse::active()->orderBy('name')->get();
        $this->warehouses = $this->warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'text' => $warehouse->name,
            ];
        })->toArray();
        $lastSale      = Sale::orderBy('invoice_no', 'DESC')->first();
        $lastInvoiceNo = $lastSale->invoice_no ?? 0;
        $this->invoice_no = generateInvoiceNumber($lastInvoiceNo);

    }
    public function editSale($id)
    {
        $this->saleId = $id ?? null;
        $this->isCreating = !$this->isCreating;
        $this->editMode = true;
        $this->saleId = $id;
        $this->loadSale($id);
        $this->selectedSale = Sale::find($id);
        $this->customers = Customer::select('id', 'name', 'mobile')->get();
        /// map Customers to include only id and name
        $this->customers = $this->customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'text' => $customer->name,
            ];
        })->toArray();
        $this->searchQuery = '';
        $this->warehouses =  Warehouse::active()->orderBy('name')->get();
        $this->warehouses = $this->warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'text' => $warehouse->name,
            ];
        })->toArray();
        $this->getProductsSearchable();
    }
    public function loadSale($id)
    {
        $sale = Sale::with('saleDetails')->findOrFail($id);

        $this->invoice_no = $sale->invoice_no;
        $this->customer_id = $sale->customer_id;
        $this->warehouse_id = $sale->warehouse_id;
        $this->sale_date =Carbon::createFromFormat('Y-m-d', $sale->sale_date)->format('d-m-Y') ;
        $this->note = $sale->note;
        $this->discount = $sale->discount_amount;
        $this->total_price = $sale->total_price;
        $this->received_amount = $sale->received_amount;
        $this->receivable_amount = $sale->receivable_amount;
        $this->vehicle_number = $sale->vehicle_number;
        $this->driver_name = $sale->driver_name;
        $this->driver_contact = $sale->driver_contact;
        $this->fare = $sale->fare;
        $this->loading = $sale->loading;

        $this->due_amount = $sale->due_amount;

        $this->products = [];

        foreach ($sale->saleDetails as $item) {
            $stock = $item->product->productStock->where('warehouse_id', $this->warehouse_id)->first();

            $this->products[] = [
                'id'         => $item->product->id,
                'name'       => $item->product->name,
                'sku'        => $item->product->sku,
                'stock'      => $stock ? $stock->quantity : 0,
                'stock_weight' => $stock ? $stock->net_weight : 0,
                'quantity'   => $item->quantity,
                'net_weight' => $item->net_weight,
                'unit'       => $item->product->unit->name ?? '',
                'price'      => $item->price,
                'total'      => $item->price * $item->quantity,
            ];
        }

        $this->recalculateTotals();
        $this->getTotalPrice();
    }
    public function getProducts()

    {
        $warehouse = $this->warehouse_id;
        if (!$warehouse) {
            $this->searchResults = [];
            $this->dispatch('notify', status: 'error', message: 'Select Whereouse First');
        }
        $products  = Product::query()->whereHas('productStock', function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse)->where('quantity', '>', 0);
        });

        $products = $products->with('productStock')->where(function ($query) {
            $query->searchable(['name', 'sku']);
        });

        $this->searchResults = $products->with('unit')->get();
    }
    public function getProductsSearchable()

    {
        $warehouse = $this->warehouse_id;

        if (!$warehouse) {
            $this->searchAbleProducts = [];
            $this->dispatch('notify', status: 'error', message: 'Select Whereouse First');
        } else {

            $this->searchAbleProducts = Product::query()->whereHas('productStock', function ($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse)->where('quantity', '>', 0);
            })->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'text' => $product->name . ' (' . $product->sku . ')',
                ];
            });
        }
    }

    public function updated($name, $value)
    {
        if ($name === 'searchTerm') {
            $this->loadSales();
        }
        if ($name === 'searchByDate') {
            $this->loadSales();
        }
        if ($name === 'searchQuery') {
            $this->getProducts();
        }
        if ($name === 'received_amount') {
            $this->recalculateTotals();
            $this->getTotalPrice();
        }
        if ($name === 'warehouse_id') {

            $this->getProductsSearchable();
        }

        if ($name === 'selected_product_id') {
            $this->addProduct($value);
        }
        if ($name === 'discount') {
            $this->recalculateTotals();
            $this->getTotalPrice();
        }
        if (str_contains($name, 'quantity')) {
            $this->checkStockAvailability();
            $this->recalculateTotals();
            $this->getTotalPrice();
        }
        if (str($name)->contains(['price'])) {
            $this->recalculateTotals();
            $this->getTotalPrice();
        }
        if (str($name)->contains(['net_weight'])) {
            $this->recalculateTotals();
            $this->getTotalPrice();
            $this->checkWeightStockAvailability();
        }
        if($name === 'expense_type_id'){
            $this->expense_type_id = (int)$value;
            $expenseType = ExpenseType::find($this->expense_type_id);
            if($expenseType && $expenseType->name == 'Fare'){
                $this->exp_amount= $this->fare;
            } elseif($expenseType && $expenseType->name == 'Loading'){
                $this->exp_amount = $this->loading;
            }
        }
    }
    public function recalculateTotals()
    {

        foreach ($this->products as $index => $product) {
            $price = $product['price'] ?? 0;
            $quantity = $product['quantity'] ?? 0;
            $net_weight = $product['net_weight'] ?? 1;
            if ($product['unit'] == 'kg' || $product['unit'] == 'Kg' || $product['unit'] == 'KG') {

                $this->products[$index]['total'] = (float)$price  * (float)$net_weight;
            } else {

                $this->products[$index]['total'] = (float)$price * (float)$quantity;
            }
        }
    }
    public function resetForm()
    {
        $this->reset([
            'products',
            'saleId',
            'customer_id',
            'warehouse_id',
            'note',
            'discount',
            'searchQuery',
            'searchResults',
            'searchAbleProducts',
            'received_amount',
            'vehicle_number',
            'driver_name',
            'driver_contact',
            'fare',
            'loading',
            'sale_date',
            'invoice_no',
            'total_price',
            'due_amount',
            'receivable_amount',
            'searchInput',
            'selected_product_id',
        ]);
    }

    public function addProduct($productId)
    {
        $product = Product::find($productId);

        if (!$product || !$product->productStock) return;
        $stock = $product->productStock->where('warehouse_id', $this->warehouse_id)->first();
        $this->products[] = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price ?? 0,
            'quantity' => 1,
            'net_weight' => 0,
            'unit' =>  $product->unit->name ?? '',
            'stock' => $stock ? $stock->quantity : 0,
            'stock_weight' => $stock ? $stock->net_weight : 0,
            'total' => $product->price ?? 0,
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->searchAbleProducts = [];
    }


    public function removeProduct($index)
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products); // reindex
    }

    public function getTotalPrice()
    {
        $this->total_price = collect($this->products)->sum('total');
        $this->receivable_amount = (float)$this->total_price - (float)$this->discount;
        $this->due_amount = (float)$this->receivable_amount - (float)$this->received_amount;
    }
    public function saveSale()
    {

        $this->validate([
            'invoice_no'    => 'required',
            'customer_id'   => 'required',
            'sale_date'     => 'required',
            'warehouse_id'  => 'required',
            'products'      => 'required|array',
            'products.*.id' => 'required',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.net_weight' => 'nullable',
            'products.*.price'    => 'required|numeric|min:0',
        ]);

        // Calculate total price
        $totalPrice = collect($this->products)->sum(function ($product) {
            return $product['quantity'] * $product['price'];
        });

        // Discount validation
        if (!empty($this->discount) && $this->discount > $totalPrice) {
            $this->addError('discount', 'Discount must not be greater than total price.');
            return;
        }

        // Fetch product stock information for the warehouse
        $productIds = collect($this->products)->pluck('id')->toArray();
        $productStocks = \App\Models\ProductStock::where('warehouse_id', $this->warehouse_id)
            ->whereIn('product_id', $productIds)
            ->get();

        // Stock validation

        foreach ($this->products as $product) {
            $productId = $product['id'];
            $newQty = $product['quantity'];

            $stock = $productStocks->firstWhere('product_id', $productId);

            // If editing, get the old quantity to calculate the difference
            $oldQty = 0;
            if (!empty($this->saleId)) {
                $oldDetail = SaleDetails::where('sale_id', $this->saleId)
                    ->where('product_id', $productId)
                    ->first();
                $oldQty = $oldDetail ? $oldDetail->quantity : 0;
            }

            // Only check if new quantity is greater than old quantity
            $additionalQtyNeeded = $newQty - $oldQty;

            if ($additionalQtyNeeded > 0) {
                if (!$stock || $stock->quantity < $additionalQtyNeeded) {
                    $this->addError('products.' . $productId, 'Insufficient stock for product ID ' . $productId);
                    return;
                }
            }
        }




        DB::beginTransaction();
        try {
            // If editing, fetch the existing sale, else create a new one
            $sale = $this->saleId ? Sale::find($this->saleId) : new Sale();

            // Update sale details or create a new one
            $sale->invoice_no = $this->invoice_no;
            $sale->customer_id = $this->customer_id;
            $sale->warehouse_id = $this->warehouse_id;
            $sale->sale_date = Carbon::createFromFormat('d-m-Y', $this->sale_date)->format('Y-m-d');
            $sale->note = $this->note ?? null;
            $sale->total_price = $totalPrice;
            $sale->discount_amount = $this->discount ?? 0;
            $sale->receivable_amount = $this->receivable_amount;
            $sale->due_amount = $this->due_amount;
            $sale->received_amount = $this->received_amount ?? 0;
            $sale->vehicle_number = $this->vehicle_number;
            $sale->driver_name = $this->driver_name;
            $sale->driver_contact = $this->driver_contact;
            $sale->fare = $this->fare;
            $sale->loading = $this->loading;
            $sale->save();
            Action::newEntry($sale, $this->saleId ? 'UPDATED' : 'CREATED');
            // Prepare sale details data
            $saleDetailsData = collect($this->products)->map(function ($product) use ($sale) {
                return [
                    'sale_id' => $sale->id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'] ?? 0,
                    'net_weight' => $product['net_weight'] ?? 0,
                    'price' => $product['price'],
                    'total' => $product['total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            // Update product stock (assuming you have logic for this)
            $this->updateProductStock($this->saleId ?? null);
            // Delete existing sale details if editing (avoid duplicates)
            if ($this->saleId) {
                SaleDetails::where('sale_id', $sale->id)->delete();
            }

            // Insert new sale details
            SaleDetails::insert($saleDetailsData);

            DB::commit();

            $wareHouseDetail = new WareHouseDetailHistory();
            $wareHouseDetail->ware_house_id = $this->warehouse_id;
            $wareHouseDetail->product_id = $product['id'];
            $wareHouseDetail->supplier_id = 0;
            $wareHouseDetail->customer_id = $this->customer_id;
            $wareHouseDetail->date = now();
            $wareHouseDetail->stock_in = 0;
            $wareHouseDetail->stock_out = $product['quantity'];
            $wareHouseDetail->amount = $product['total'];
            $wareHouseDetail->save();

            // Notify the user of success
            $this->dispatch('notify', status: 'success', message: 'Sale saved successfully');

            // Reset state and reload sales
            $this->isCreating = !$this->isCreating;
            $this->resetForm();
            $this->loadSales();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('form', 'An error occurred: ' . $e->getMessage());
        }
    }
    public function openExpenseModal($id)
    {
        $this->saleId = $id ?? null;
        $selectedSale = Sale::find($id);
        $this->fare = $selectedSale->fare ?? 0;
        $this->loading = $selectedSale->loading ?? 0;
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
        if($this->exp_amount <= 0){
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
            'App\Models\Sale',
            $this->saleId
        );
        $notification = $this->selected_id ? 'Expense updated successfully' : 'Expense added successfully';
        $this->dispatch('close-modal');
        $this->dispatch('notify', status: 'success', message: $notification);

        $this->reset(['expense_type_id', 'date_of_expense', 'exp_amount', 'note', 'bank_id', 'expense_id']);
    }

    protected function checkStockAvailability()
    {
        $products = $this->products;
        $notify = [];

        foreach ($products as $index => $product) {
            $product = (object) $product;

            $productStock = ProductStock::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouse_id)
                ->first();

            // Calculate old quantity if in edit mode
            $oldQty = 0;
            if (!empty($this->saleId)) {
                $oldDetail = SaleDetails::where('sale_id', $this->saleId)
                    ->where('product_id', $product->id)
                    ->first();
                $oldQty = $oldDetail ? $oldDetail->quantity : 0;
            }

            // How much more stock is needed?
            $additionalQtyNeeded = (int)$product->quantity - (int)$oldQty;

            // Check if the additional quantity can be fulfilled
            if ($additionalQtyNeeded > 0 && (!$productStock || $additionalQtyNeeded > $productStock->quantity)) {
                $maxQty = $oldQty + ($productStock ? $productStock->quantity : 0);

                // Update product quantity to maximum allowed
                $this->products[$index]['quantity'] = $maxQty;
                $this->products[$index]['total'] = $product->price * $maxQty;

                $notify[] = [
                    'product' => $product->name ?? 'Unnamed Product',
                    'available' => $productStock ? $productStock->quantity : 0,
                    'requested' => $additionalQtyNeeded,
                ];
            }
        }

        if (count($notify) > 0) {
            $message = 'Stock not available for the following products: ';
            foreach ($notify as $item) {
                $message .= $item['product'] . ' (Available: ' . $item['available'] . ', Requested: ' . $item['requested'] . ') ';
            }
            $this->dispatch('notify', status: 'error', message: $message);
            return false;
        }

        return true;
    }
    protected function checkWeightStockAvailability()
    {
        $products = $this->products;
        $notify = [];

        foreach ($products as $index => $product) {
            $product = (object) $product;

            $productStock = ProductStock::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouse_id)
                ->first();

            // Calculate old net weight if in edit mode
            $oldNetWeight = 0;
            if (!empty($this->saleId)) {
                $oldDetail = SaleDetails::where('sale_id', $this->saleId)
                    ->where('product_id', $product->id)
                    ->first();
                $oldNetWeight = $oldDetail ? $oldDetail->net_weight : 0;
            }

            // How much more net weight is needed?
            $additionalNetWeightNeeded = (float)$product->net_weight - (float)$oldNetWeight;

            // Check if the additional net weight can be fulfilled
            if ($additionalNetWeightNeeded > 0 && (!$productStock || $additionalNetWeightNeeded > $productStock->net_weight)) {
                $maxNetWeight = (float)$oldNetWeight + ($productStock ? (float)$productStock->net_weight : 0);

                // Update product net weight to maximum allowed
                $this->products[$index]['net_weight'] = $maxNetWeight;
                $this->products[$index]['total'] = (float)$product->price * (float)$maxNetWeight;

                $notify[] = [
                    'product' => $product->name ?? 'Unnamed Product',
                    'available' => $productStock ? (float)$productStock->net_weight : 0,
                    'requested' => (float)$additionalNetWeightNeeded,
                ];
            }
        }

        if (count($notify) > 0) {
            $message = 'Insufficient weight stock for the following products: ';
            foreach ($notify as $item) {
                $message .= "{$item['product']} (Available: {$item['available']} kg, Requested: {$item['requested']} kg) ";
            }
            $this->dispatch('notify', status: 'error', message: $message);
            return false;
        }

        return true;
    }

    protected function updateProductStock($saleId = null)
    {
        if ($saleId) {
            // Restore old stock before updating
            $oldSaleDetails = SaleDetails::where('sale_id', $saleId)->get();

            foreach ($oldSaleDetails as $detail) {
                $productStock = ProductStock::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $this->warehouse_id)
                    ->first();

                if ($productStock) {
                    $productStock->quantity += $detail->quantity; // Restore stock
                    $productStock->net_weight += $detail->net_weight ?? 0; // Restore net weight
                    $productStock->save();
                }
            }
        }

        // Deduct new product quantities
        foreach ($this->products as $product) {
            $product = (object) $product;

            if (empty($product->quantity) || $product->quantity <= 0) continue;

            $productStock = ProductStock::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouse_id)
                ->first();

            if ($productStock) {
                $productStock->quantity -= $product->quantity;
                $productStock->net_weight -= $product->net_weight ?? 0; // Deduct net weight
                $productStock->save();
            }
            $productTotal = Product::find($product->id);
            $productTotal->total_sale += $product->quantity;
            $productTotal->total_sale_weight += $product->net_weight ?? 0;
            $productTotal->save();
        }
    }
    public function payMentModal($id)
    {
        $this->banks = Bank::all();
        $this->paymentSale = Sale::with('customer')->find($id);
        $this->modal_invoice_no = $this->paymentSale->invoice_no;
        $this->modal_customer_name = $this->paymentSale->customer()->first()->name;
        $this->modal_receivable_amount = $this->paymentSale->due_amount;
        $this->modal_payment_method = $this->paymentSale->payment_method ?? 'cash';
        $this->modal_rec_bank = 0.00;
        $this->modal_rec_amount = 0.00;
    }


    public function submitPayment()
    {
        $this->validate();

        $sale = $this->paymentSale;
        $isPaying = $sale->due_amount < 0;
        $amount_cash = $this->modal_rec_amount;
        $amount_bank = $this->modal_rec_bank;
        $amount = $this->modal_payment_method == 'cash' ? $amount_cash : $amount_bank;
        $amount = $this->modal_payment_method == 'bank' ? $amount_bank : $amount_cash;
        $amount = $this->modal_payment_method == 'both' ? $amount_cash + $amount_bank : $amount;
        $amount = abs($amount);

        $sale->received_amount += $amount;
        $sale->due_amount -= $amount;
        $sale->payment_method = $this->modal_payment_method;
        $sale->received_amount_cash += $amount_cash;
        $sale->received_amount_bank += $amount_bank;
        $sale->bank_id = $this->bankId;
        $sale->save();

        if ($isPaying) {
            $amount *= -1;
            $remark = 'RETURNED_EXTRA_PAYMENT_FROM_SALE';
            $notification = 'Payment completed successfully';
        } else {
            $remark = 'RECEIVED_PAYMENT_FOR_SALE';
            $notification = 'Payment received successfully';
        }
        $payment = new CustomerPayment();
        $payment->customer_id = $sale->customer_id;
        $payment->sale_id = $sale->id;
        $payment->amount = $amount;
        $payment->trx = getTrx();
        $payment->remark = $remark;

        $payment->save();
        Action::newEntry($payment, 'CREATED');

        $lastTransaction = ModelsCustomerTransaction::where('customer_id', $sale->customer_id)
            ->orderBy('id', 'desc')
            ->first();

        // Check if it's the first transaction
        if ($lastTransaction) {
            $openingBalance = $lastTransaction->closing_balance;
        } else {
            // First transaction: get opening balance from the customer table
            $customer = Customer::findOrFail($sale->customer_id);
            $openingBalance = $customer->opening_balance ?? 0.00;
        }

        // Debit amount (sale)
        $debitAmount = $amount;
        $creditAmount = 0.00;

        // Calculate new closing balance
        $closingBalance = $openingBalance + $debitAmount;

        // Save transaction
        $customerTransaction = new ModelsCustomerTransaction();
        $customerTransaction->customer_id      = $sale->customer_id;
        $customerTransaction->credit_amount    = $creditAmount;
        $customerTransaction->debit_amount     = $debitAmount;
        $customerTransaction->opening_balance  = $openingBalance;
        $customerTransaction->closing_balance  = $closingBalance;
        $customerTransaction->source           = 'Sale Transaction';
        $customerTransaction->bank_id          = $sale->bank_id;
        $customerTransaction->save();

        // Determine the amount based on payment method
        if ($this->modal_payment_method === 'cash') {
            // --- CASH LOGIC BLOCK ---
            $bank = Bank::where('name', 'Cash')->first();

            if (!$bank) {
                return;
            }

            $amount = $this->modal_rec_amount;

            $lastTransaction = BankTransaction::where('bank_id', $bank->id)->latest()->first();

            $openingBalance = $lastTransaction ? $lastTransaction->closing_balance : ($bank->opening_balance ?? 0.00);

            $debitAmount = $amount;
            $creditAmount = 0.00;

            $closingBalance = $openingBalance + $debitAmount;

            try {
                $bankTransaction = new BankTransaction();
                $bankTransaction->bank_id = $bank->id;
                $bankTransaction->opening_balance = $openingBalance;
                $bankTransaction->closing_balance = $closingBalance;
                $bankTransaction->debit = $debitAmount;
                $bankTransaction->credit = $creditAmount;
                $bankTransaction->amount = $amount;
                $bankTransaction->module_id = $sale->id;
                $bankTransaction->data_model = 'Sale';
                $bankTransaction->source = 'Cash Payment Sale Received';
                $bankTransaction->save();

                $bank->current_balance = $closingBalance;
                $bank->save();
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
        }
        if ($this->modal_payment_method === 'bank') {

            // Step 1: Bank amount
            $amount = $this->modal_rec_bank; // amount received via bank

            // Step 2: Get selected bank

            $bank = Bank::find($this->bankId);
            if (!$bank) {
                return; // Bank not found, simply exit
            }

            // Step 3: Last bank transaction
            $lastTransaction = BankTransaction::where('bank_id', $bank->id)->latest()->first();

            // Step 4: Opening balance
            $openingBalance = $lastTransaction
                ? $lastTransaction->closing_balance
                : ($bank->opening_balance ?? 0.00);

            // Step 5: Bank payment means money **received** so it's a debit
            $debitAmount = $amount;
            $creditAmount = 0.00;

            // Step 6: Closing balance
            $closingBalance = $openingBalance + $debitAmount;

            // Step 7: Create new bank transaction
            $bankTransaction = new BankTransaction();
            $bankTransaction->bank_id          = $bank->id;
            $bankTransaction->opening_balance  = $openingBalance;
            $bankTransaction->closing_balance  = $closingBalance;
            $bankTransaction->debit            = $debitAmount;
            $bankTransaction->credit           = $creditAmount;
            $bankTransaction->amount           = $amount;
            $bankTransaction->module_id        = $sale->id;
            $bankTransaction->data_model        = 'Sale';
            $bankTransaction->source           = 'Bank Payment Sale Received';
            $bankTransaction->save();

            // Step 8: Update bank current balance
            $bank->current_balance = $closingBalance;
            $bank->save();
        }
        if ($this->modal_payment_method === 'both') {
            // --- CASH PART ---
            $cashAmount = $this->modal_rec_amount;
            $cashBank = Bank::where('name', 'Cash')->first();

            if ($cashBank && $cashAmount > 0) {
                $lastCashTransaction = BankTransaction::where('bank_id', $cashBank->id)->latest()->first();
                $cashOpening = $lastCashTransaction ? $lastCashTransaction->closing_balance : ($cashBank->opening_balance ?? 0.00);
                $cashClosing = $cashOpening + $cashAmount;

                $cashTransaction = new BankTransaction();
                $cashTransaction->bank_id = $cashBank->id;
                $cashTransaction->opening_balance = $cashOpening;
                $cashTransaction->closing_balance = $cashClosing;
                $cashTransaction->debit = $cashAmount;
                $cashTransaction->credit = 0.00;
                $cashTransaction->amount = $cashAmount;
                $cashTransaction->module_id = $sale->id;
                $cashTransaction->data_model = 'Sale';
                $cashTransaction->source = 'Cash Sale (Both Method)';
                $cashTransaction->save();

                $cashBank->current_balance = $cashClosing;
                $cashBank->save();
            }

            // --- BANK PART ---
            $bankAmount = $this->modal_rec_bank;
            $selectedBank = Bank::find($this->bankId);

            if ($selectedBank && $bankAmount > 0) {
                $lastBankTransaction = BankTransaction::where('bank_id', $selectedBank->id)->latest()->first();
                $bankOpening = $lastBankTransaction ? $lastBankTransaction->closing_balance : ($selectedBank->opening_balance ?? 0.00);
                $bankClosing = $bankOpening + $bankAmount;

                $bankTransaction = new BankTransaction();
                $bankTransaction->bank_id = $selectedBank->id;
                $bankTransaction->opening_balance = $bankOpening;
                $bankTransaction->closing_balance = $bankClosing;
                $bankTransaction->debit = $bankAmount;
                $bankTransaction->credit = 0.00;
                $bankTransaction->amount = $bankAmount;
                $bankTransaction->module_id = $sale->id;
                $bankTransaction->data_model = 'Sale';
                $bankTransaction->source = 'Bank Sale (Both Method)';
                $bankTransaction->save();

                $selectedBank->current_balance = $bankClosing;
                $selectedBank->save();
            }
        }

        $this->handleDailyBookEntries($amount_cash, $amount_bank, 'debit', $this->modal_payment_method, 'Sale', $sale->id);

        session()->flash('success', $notification);
        $this->resetExcept('saleId');
        $this->loadSales();
        $this->dispatch('notify', status: 'success', message: $notification);
         $this->js('setTimeout(() => window.location.reload(), 1200)');

        return; // important: stop here, don’t also return a redirect
    }


    public function render()
    {
        $this->dispatch('re-init-select-2-component');
        return view('livewire.admin.sale-management.all-sales');
    }
}
