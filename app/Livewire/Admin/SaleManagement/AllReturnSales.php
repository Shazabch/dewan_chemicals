<?php

namespace App\Livewire\Admin\SaleManagement;

use App\Constants\Status;
use App\Models\Action;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use Carbon\Carbon;
use Livewire\Component;

class AllReturnSales extends Component
{
    public Sale $sale;
    public ?SaleReturn $saleReturnInstance = null; // Renamed to avoid conflict with blade variable

    public $saleId;
    public $saleReturnId;

    // Form fields
    public $return_date;
    public $products = []; // This will store items for the return
    public $discount = 0;
    public $fine=0;
    public $return_expense = 0;
    public $note = '';

    // Display properties
    public $invoice_no;
    public $customer_name;
    public $warehouse_name;

    // Calculated properties
    public $grandTotal = 0;
    public $payableAmount = 0; // Payable to customer
    public $paidAmount = 0;    // Already paid from sale return
    public $dueAmount = 0;     // Due to customer from this return

    public $editMode = false;

    protected function rules()
    {
        $qtyValidation = $this->editMode ? 'gt:0' : 'gte:0';
        return [
            'return_date'           => 'required|date_format:Y-m-d',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|gt:0',
            'products.*.quantity'   => "required|integer|$qtyValidation",
            'products.*.price'      => 'required|numeric|gte:0', // Price per item for the return
            'discount'              => 'nullable|numeric|gte:0',
            'note'                  => 'nullable|string',
        ];
    }

    protected $messages = [
        'products.*.quantity.gt' => 'Return Qty must be greater than 0 for existing items.',
        'products.*.quantity.gte' => 'Return Qty must be 0 or more.',
        'products.min' => 'At least one product must be selected for return.',
    ];

    public function mount($saleId, $saleReturnId = null)
    {
        $this->sale = new Sale(); // Initialize to avoid null errors
        $this->saleId = $saleId;
        $this->sale = Sale::with([
            'saleDetails.product.unit',
            'saleDetails.product.productStock' => function ($q) {
                $q->where('warehouse_id', $this->sale->warehouse_id);
            },
            'customer:id,name',
            'warehouse:id,name'
        ])->findOrFail($saleId);

        $this->invoice_no = $this->sale->invoice_no;
        $this->customer_name = $this->sale->customer->name;
        $this->warehouse_name = $this->sale->warehouse->name;
        $this->return_date = Carbon::now()->format('Y-m-d');

        if ($saleReturnId) {
            $this->editMode = true;
            $this->saleReturnId = $saleReturnId;
            $this->saleReturnInstance = SaleReturn::with('details.product.unit')->findOrFail($saleReturnId);
            $this->return_date = Carbon::parse($this->saleReturnInstance->return_date)->format('Y-m-d');
            $this->discount = $this->saleReturnInstance->discount_amount ?? 0;
            $this->fine = $this->saleReturnInstance->fine ?? 0;
            $this->return_expense = $this->saleReturnInstance->return_expense ?? 0;
            $this->note = $this->saleReturnInstance->note;
            $this->paidAmount = $this->saleReturnInstance->paid_amount ?? 0; // From existing sale return

            foreach ($this->saleReturnInstance->details as $detail) {
                $saleDetail = $this->sale->saleDetails->where('product_id', $detail->product_id)->first();
                $stock = $detail->product->productStock->where('warehouse_id', $this->sale->warehouse_id)->first();
                $productUnit = strtolower($detail->product->unit->name ?? 'pcs');
                $this->products[] = [
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'unit_name' => $detail->product->unit->name,
                    'sale_quantity' => $saleDetail ? $saleDetail->quantity : 0,
                    'sale_weight' => $saleDetail ? $saleDetail->net_weight : 0,
                    'stock_quantity' => $stock ? $stock->quantity : 0,
                    'stock_weight' => $stock ? $stock->net_weight : 0,
                    'quantity' => $detail->quantity,
                    'net_weight' => $detail->net_weight ?? 0, // Use net_weight if available
                    'price' => $detail->price,
                    'total' => $productUnit === 'kg' || $productUnit === 'kilogram' ?
                        $detail->net_weight * $detail->price :
                        $detail->quantity * $detail->price,
                ];
            }
        } else {
            // Creating a new return
            foreach ($this->sale->saleDetails as $detail) {
                $stock = $detail->product->productStock->where('warehouse_id', $this->sale->warehouse_id)->first();
                $this->products[] = [
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'unit_name' => $detail->product->unit->name,
                    'sale_quantity' => $detail->quantity,
                    'sale_weight' => $detail ? $detail->net_weight : 0,
                    'stock_quantity' => $stock ? $stock->quantity : 0,
                    'stock_weight' => $stock ? $stock->net_weight : 0,
                    'quantity' => 0, // Default to 0 for new return
                    'net_weight' => 0, // Default to 0 for new return
                    'price' => $detail->price,
                    'total' => 0,
                ];
            }
        }
        $this->calculateTotals();
    }

    public function updatedProducts($value, $key)
    {
        // $key is like '0.quantity'
        $parts = explode('.', $key);
        $index = $parts[0];
        $field = $parts[1];

        if ($field === 'quantity') {
            $product = &$this->products[$index]; // Use reference
            $product['quantity'] = ctype_digit((string)$value) ? (int)$value : 0;
            $productUnit = $product['unit_name'] ?? 'pcs';
            $productUnit = strtolower($productUnit);
            if ($product['quantity'] < 0) $product['quantity'] = 0;
            if ($product['quantity'] > $product['sale_quantity']) {

                $product['quantity'] = $product['sale_quantity']; // Cap it
            }
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
            } else {

                $product['total'] = $product['quantity'] * $product['price'];
            }
        }
        if ($field === 'net_weight') {
            $product = &$this->products[$index]; // Use reference
            $product['net_weight'] = ctype_digit((string)$value) ? (int)$value : 0;
            $productUnit = $product['unit_name'] ?? 'pcs';
            $productUnit = strtolower($productUnit);
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {

                $product['total'] = $product['net_weight'] * $product['price'];
            }
            if ($product['net_weight'] < 0) $product['net_weight'] = 0;
            if ($product['net_weight'] > $product['sale_weight']) {

                $product['net_weight'] = $product['sale_weight']; // Cap it
            }
        }

        $this->calculateTotals();
        // Trigger validation for the specific field
        $this->validateOnly('products.' . $key);
    }

    public function updatedDiscount()
    {
        $this->discount = (float) $this->discount;
        if ($this->discount < 0) $this->discount = 0;
        $this->calculateTotals();
        $this->validateOnly('discount');
    }
    public function updatedFine()
    {
        // $this->fine = (float) $this->fine;
        // if ($this->fine < 0) $this->fine = 0;
        $this->calculateTotals();
    }
        public function updatedReturnExpense()
    {
        $this->return_expense = (float) $this->return_expense;
        if ($this->return_expense < 0) $this->return_expense = 0;
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->grandTotal = 0;
        foreach ($this->products as $product) {
            if (isset($product['total'])) { // Ensure 'total' is set
                $this->grandTotal += $product['total'];
            }
        }



        if ($this->discount > $this->grandTotal) {
            // $this->addError('discount', 'Discount cannot exceed total price.');
            // This should be handled by validation rule ideally or capped
            // $this->discount = $this->grandTotal;
        }

        $this->payableAmount = $this->grandTotal - $this->discount;
         $this->payableAmount+=(float)$this->fine + (float)$this->return_expense;
        if ($this->editMode) {
            $this->dueAmount = $this->payableAmount - $this->paidAmount;
        } else {
            // For new returns, dueAmount is same as payableAmount as nothing is paid yet for the return
            $this->dueAmount = $this->payableAmount;
        }
    }

    public function saveReturn()
    {
        $this->validate();

        // Additional custom validations previously in controller
        $this->totalPriceFromProducts = array_sum(array_map(function ($item) {
            return $item['total'] ?? 0;
        }, $this->products));


        if ($this->discount > $this->totalPriceFromProducts) {
            $this->addError('discount', 'Discount amount mustn\'t be greater than total price');
            return;
        }

        if ($this->totalPriceFromProducts <= 0 && !$this->editMode) { // Allow update to 0 quantity for an item.
            $this->addError('products', 'Sale return quantity/weight items is empty or total is zero.');
            return;
        }


        $currentSaleReturn = $this->saleReturnInstance ?? new SaleReturn();

        // Check stock availability (if reducing quantity on edit)
        if ($this->editMode) {
            $productIdsFromRequest = collect($this->products)->pluck('product_id')->toArray();
            $productStocks = ProductStock::where('warehouse_id', $this->sale->warehouse_id)
                ->whereIn('product_id', $productIdsFromRequest)
                ->get();

            foreach ($this->products as $index => $productData) {
                $requestedProduct = (object) $productData;
                $returnDetail = $currentSaleReturn->details->where('product_id', $requestedProduct->product_id)->first();
                $saleDetail = $this->sale->saleDetails->where('product_id', $requestedProduct->product_id)->first();

                if ($saleDetail && $requestedProduct->quantity > $saleDetail->quantity) {
                    $this->addError("products.{$index}.quantity", 'Return quantity exceeds sale quantity.');
                    return;
                }
                if ($saleDetail && $requestedProduct->net_weight > $saleDetail->net_weight) {
                    $this->addError("products.{$index}.net_weight", 'Return weight exceeds sale weight.');
                    return;
                }

                if ($returnDetail) { // Existing item in return
                    $productStock = $productStocks->where('product_id', $requestedProduct->product_id)->first();
                    $oldQuantity  = $returnDetail->quantity;
                    $quantityDiff = $oldQuantity - $requestedProduct->quantity; // Positive if reducing return, negative if increasing
                    $oldWeight  = $returnDetail->net_weight;
                    $net_weightDiff = $oldWeight - $requestedProduct->net_weight;
                    // If we are reducing the return quantity (meaning product was given back to customer from stock)
                    // and the amount we are reducing by is more than what's in stock currently.
                    if ($quantityDiff > 0 && $productStock && $quantityDiff > $productStock->quantity) {
                        $this->addError("products.{$index}.quantity", 'Reduced quantity exceeds available stock for ' . $requestedProduct->name);
                        return;
                    }
                    if ($net_weightDiff > 0 && $productStock && $net_weightDiff > $productStock->net_weight) {
                        $this->addError("products.{$index}.net_weight", 'Reduced Weight exceeds available stock for ' . $requestedProduct->name);
                        return;
                    }
                }
            }
        }


        // --- Save Sale Return Data ---
        $currentSaleReturn->sale_id         = $this->sale->id;
        $currentSaleReturn->customer_id     = $this->sale->customer_id;
        $currentSaleReturn->return_date     = Carbon::parse($this->return_date);
        $currentSaleReturn->total_price     = $this->totalPriceFromProducts;
        $currentSaleReturn->discount_amount = $this->discount;
        $currentSaleReturn->fine = $this->fine;
        $currentSaleReturn->return_expense = $this->return_expense;
        $currentSaleReturn->payable_amount  = $this->payableAmount; // This is total_price - discount

        // Due amount needs to be calculated based on what was already paid for this return
        if ($this->editMode) {
            $currentSaleReturn->due_amount = $this->payableAmount - $currentSaleReturn->paid_amount;
        } else {
            $currentSaleReturn->due_amount = $this->payableAmount; // For new return, nothing is paid yet for this return.
            // On new return, paid_amount for the return itself is 0. It might be set later if partial payment for return is made.
            $currentSaleReturn->paid_amount = 0;
        }
        $currentSaleReturn->note            = $this->note;
        $currentSaleReturn->save();

        if ($this->sale->return_status == Status::NO) {
            $this->sale->return_status = Status::YES;
            $this->sale->save();
        }

        // --- Update Stock & Store Sale Return Details ---
        $productStocks = ProductStock::where('warehouse_id', $this->sale->warehouse_id)
            ->whereIn('product_id', collect($this->products)->pluck('product_id')->toArray())
            ->get();

        $existingDetailIds = [];

        foreach ($this->products as $productData) {
            $productItem = (object) $productData;
            $returnDetails = SaleReturnDetails::where('sale_return_id', $currentSaleReturn->id)
                ->where('product_id', $productItem->product_id)
                ->first();

            $oldReturnQuantity = $returnDetails ? $returnDetails->quantity : 0;
            $newReturnQuantity = $productItem->quantity;
            $quantityChangeForStock = $newReturnQuantity - $oldReturnQuantity; // Positive if more items returned, negative if less

            $oldReturnWeight = $returnDetails ? $returnDetails->net_weight : 0;
            $newReturnWeight = $productItem->net_weight;
            $net_weightChangeForStock = $newReturnWeight - $oldReturnWeight;

            if ($productItem->quantity > 0 || $productItem->net_weight > 0) {
                if (!$returnDetails) {
                    $returnDetails = new SaleReturnDetails();
                }
                $returnDetails->sale_return_id = $currentSaleReturn->id;
                $returnDetails->product_id     = $productItem->product_id;
                $returnDetails->quantity       = $productItem->quantity;
                $returnDetails->net_weight     = $productItem->net_weight; // Use net_weight if available
                $returnDetails->price          = $productItem->price;
                $returnDetails->total          = $productItem->total;
                $returnDetails->save();
                $existingDetailIds[] = $returnDetails->id;
            }


            // Update stock and product total sale
            if ($quantityChangeForStock != 0) {
                $stock    = $productStocks->where('product_id', $productItem->product_id)->first();
                if ($stock) {
                    $stock->quantity   += $quantityChangeForStock; // Add to stock if returned, subtract if return is reduced
                    $stock->save();
                }

                $productModel              = Product::find($productItem->product_id);
                if ($productModel) {
                    $productModel->total_sale -= $quantityChangeForStock; // Decrease sale count if returned, increase if return is reduced
                    $productModel->save();
                }
            }
        }
        // Delete details that were removed
        if ($this->editMode) {
            SaleReturnDetails::where('sale_return_id', $currentSaleReturn->id)
                ->whereNotIn('id', $existingDetailIds)
                ->get()
                ->each(function ($detailToClean) use ($productStocks) {
                    // Adjust stock for removed items
                    $stock = $productStocks->where('product_id', $detailToClean->product_id)->first();
                    if ($stock) {
                        $stock->quantity -= $detailToClean->quantity; // It was returned, now it's not, so stock decreases
                        $stock->save();
                    }
                    $productModel = Product::find($detailToClean->product_id);
                    if ($productModel) {
                        $productModel->total_sale += $detailToClean->quantity; // Sale count increases as it's no longer returned
                        $productModel->save();
                    }
                    $detailToClean->delete();
                });
        }


        Action::newEntry($currentSaleReturn, $this->editMode ? 'UPDATED' : 'CREATED');

        session()->flash('notify', ['success', 'Sale return data ' . ($this->editMode ? 'updated' : 'added') . ' successfully']);
        return redirect()->route('admin.sale.return.edit', $currentSaleReturn->id);
    }


    public function render()
    {
        return view('livewire.admin.sale-management.all-return-sales');
    }
}
