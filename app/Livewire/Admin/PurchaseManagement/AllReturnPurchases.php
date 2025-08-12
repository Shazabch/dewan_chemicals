<?php

namespace App\Livewire\Admin\PurchaseManagement;

use App\Constants\Status;
use App\Models\Action;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetails;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class AllReturnPurchases extends Component
{
    public Purchase $purchase;
    public ?PurchaseReturn $purchaseReturnInstance = null;

    public $purchaseId;
    public $purchaseReturnId;

    // Form fields
    public $return_date;
    public $products = [];
    public $discount = 0;
    public $fine = 0;
    public $return_expense = 0;
    public $note = '';

    // Display properties
    public $invoice_no;
    public $supplier_name;
    public $warehouse_name;

    // Calculated properties
    public $grandTotal = 0;
    public $receivableAmount = 0;
    public $receivedAmount = 0;
    public $dueAmount = 0;

    public $editMode = false;

    protected function rules()
    {
        $qtyValidation = $this->editMode ? 'gt:0' : 'gte:0';
        $weightValidation = $this->editMode ? 'gt:0' : 'gte:0'; // Similar logic for weight

        return [
            'return_date'           => 'required|date_format:Y-m-d',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|gt:0',
            'products.*.quantity'   => "required|integer|$qtyValidation",
            'products.*.net_weight' => "nullable|numeric|$weightValidation", // Add net_weight validation
            'products.*.price'      => 'required|numeric|gte:0',
            'discount'              => 'nullable|numeric|gte:0',
            'note'                  => 'nullable|string',
            // Ensure that for 'kg' units, either quantity or net_weight has a value if returning
            'products.*' => [function ($attribute, $value, $fail) {
                $productUnit = strtolower($value['unit_name'] ?? 'pcs');
                if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                    if (($value['quantity'] <= 0) && ($value['net_weight'] <= 0) && $this->editMode) {
                        // If editing an existing KG item, one of them must be > 0
                        // For new items, it's handled by products.min and initial gte:0
                    }
                } elseif (($value['quantity'] <= 0) && $this->editMode) {
                    // If editing an existing PCS item, quantity must be > 0
                }
            }],
        ];
    }

    protected $messages = [
        'products.*.quantity.gt' => 'Return Qty must be greater than 0 for existing items.',
        'products.*.quantity.gte' => 'Return Qty must be 0 or more.',
        'products.*.net_weight.gt' => 'Return Weight must be greater than 0 for existing KG items.',
        'products.*.net_weight.gte' => 'Return Weight must be 0 or more.',
        'products.min' => 'At least one product must be selected for return.',
    ];

    public function mount($purchaseId, $purchaseReturnId = null)
    {
        $this->purchaseId = $purchaseId;

        $this->purchase = Purchase::with([
            'supplier:id,name',
            'warehouse:id,name'
        ])->findOrFail($purchaseId);

        $this->purchase->load([
            'purchaseDetails.product.unit',
            'purchaseDetails.product.productStock' => function ($query) {
                $query->where('warehouse_id', $this->purchase->warehouse_id);
            }
        ]);

        $this->invoice_no = $this->purchase->invoice_no;
        $this->supplier_name = $this->purchase->supplier?->name;
        $this->warehouse_name = $this->purchase->warehouse->name;
        $this->return_date = Carbon::now()->format('Y-m-d');

        if ($purchaseReturnId) {
            $this->editMode = true;
            $this->purchaseReturnId = $purchaseReturnId;
            $this->purchaseReturnInstance = PurchaseReturn::with([
                'details.product.unit',
                'supplier:id,name'
            ])->findOrFail($purchaseReturnId);

            if ($this->purchaseReturnInstance->supplier) {
                $this->supplier_name = $this->purchaseReturnInstance->supplier->name;
            }

            $this->return_date = Carbon::parse($this->purchaseReturnInstance->return_date)->format('Y-m-d');
            $this->discount = $this->purchaseReturnInstance->discount_amount ?? 0;
            $this->fine = $this->purchaseReturnInstance->fine ?? 0;
            $this->return_expense = $this->purchaseReturnInstance->return_expense ?? 0;
            $this->note = $this->purchaseReturnInstance->note;
            $this->receivedAmount = $this->purchaseReturnInstance->received_amount ?? 0;

            $this->products = [];
            foreach ($this->purchaseReturnInstance->details as $detail) {
                $originalPurchaseDetail = $this->purchase->purchaseDetails->where('product_id', $detail->product_id)->first();
                $stock = $detail->product->productStock()->where('warehouse_id', $this->purchase->warehouse_id)->first();
                $productUnit = strtolower($detail->product->unit->name ?? 'pcs');

                $this->products[] = [
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'unit_name' => $detail->product->unit->name,
                    'purchase_quantity' => $originalPurchaseDetail ? $originalPurchaseDetail->quantity : 0,
                    'purchase_weight' => $originalPurchaseDetail ? ($originalPurchaseDetail->net_weight ?? 0) : 0, // Added
                    'stock_quantity' => $stock ? $stock->quantity : 0,
                    'stock_weight' => $stock ? ($stock->net_weight ?? 0) : 0, // Added
                    'quantity' => $detail->quantity,
                    'net_weight' => $detail->net_weight ?? 0, // Added
                    'price' => $detail->price,
                    'total' => ($productUnit === 'kg' || $productUnit === 'kilogram') ?
                        ($detail->net_weight ?? 0) * $detail->price :
                        $detail->quantity * $detail->price,
                ];
            }
        } else {
            $this->products = [];
            foreach ($this->purchase->purchaseDetails as $detail) {
                $stock = $detail->product->productStock()->where('warehouse_id', $this->purchase->warehouse_id)->first();
                $this->products[] = [
                    'product_id' => $detail->product_id,
                    'name' => $detail->product->name,
                    'unit_name' => $detail->product->unit->name,
                    'purchase_quantity' => $detail->quantity,
                    'purchase_weight' => $detail->net_weight ?? 0, // Added
                    'stock_quantity' => $stock ? $stock->quantity : 0,
                    'stock_weight' => $stock ? ($stock->net_weight ?? 0) : 0, // Added
                    'quantity' => 0,
                    'net_weight' => 0, // Added
                    'price' => $detail->price,
                    'total' => 0,
                ];
            }
        }
        $this->calculateTotals();
    }

    public function updatedProducts($value, $key)
    {
        $parts = explode('.', $key);
        $index = (int)$parts[0]; // Ensure index is an integer
        $field = $parts[1];

        if (!isset($this->products[$index])) {
            Log::warning("Product at index {$index} not found in updatedProducts.", ['key' => $key, 'current_products' => $this->products]);
            return;
        }

        $product = &$this->products[$index];
        $productUnit = strtolower($product['unit_name'] ?? 'pcs');

        if ($field === 'quantity') {
            $product['quantity'] = ctype_digit((string)$value) ? (int)$value : 0;
            if ($product['quantity'] < 0) $product['quantity'] = 0;

            if ($product['quantity'] > $product['purchase_quantity']) {
                $this->addError("products.{$index}.quantity", 'Return Qty cannot exceed Purchased Qty.');
                $product['quantity'] = $product['purchase_quantity'];
            }
            if ($product['quantity'] > $product['stock_quantity']) {
                $this->addError("products.{$index}.quantity", 'Return Qty cannot exceed current Stock Qty.');
                $product['quantity'] = $product['stock_quantity'];
            }
            // For non-kg items, quantity determines total directly. For kg, net_weight does.
            if ($productUnit !== 'kg' && $productUnit !== 'kilogram') {
                $product['total'] = $product['quantity'] * $product['price'];
            }
        } elseif ($field === 'net_weight') {
            $product['net_weight'] = is_numeric($value) ? (float)$value : 0;
            if ($product['net_weight'] < 0) $product['net_weight'] = 0;

            if ($product['net_weight'] > $product['purchase_weight']) {
                $this->addError("products.{$index}.net_weight", 'Return Weight cannot exceed Purchased Weight.');
                $product['net_weight'] = $product['purchase_weight'];
            }
            if ($product['net_weight'] > $product['stock_weight']) {
                $this->addError("products.{$index}.net_weight", 'Return Weight cannot exceed current Stock Weight.');
                $product['net_weight'] = $product['stock_weight'];
            }
            // For kg items, net_weight determines total.
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                $product['total'] = $product['net_weight'] * $product['price'];
            }
        }
        // If unit is KG and quantity changes, it does NOT affect total directly if price is per KG.
        // Total for KG items is based on net_weight * price. Quantity might be informational (e.g., 1 bag of 25kg).

        $this->calculateTotals();
        $this->validateOnly('products.' . $key);
    }

    public function updatedDiscount()
    {
        $this->discount = is_numeric($this->discount) ? (float)$this->discount : 0;
        if ($this->discount < 0) $this->discount = 0;
        $this->calculateTotals();
        $this->validateOnly('discount');
    }
    public function updatedFine()
    {
        $this->fine = (float) $this->fine;
        if ($this->fine < 0) $this->fine = 0;
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
            $productUnit = strtolower($product['unit_name'] ?? 'pcs');
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                $this->grandTotal += ($product['net_weight'] ?? 0) * ($product['price'] ?? 0);
            } else {
                $this->grandTotal += ($product['quantity'] ?? 0) * ($product['price'] ?? 0);
            }
        }

        if ($this->discount > $this->grandTotal) {
            // Let validation handle or cap it
            // $this->addError('discount', 'Discount cannot exceed total price.');
        }

        $this->receivableAmount = $this->grandTotal - $this->discount;
        $this->receivableAmount += $this->fine + $this->return_expense;

        if ($this->editMode && $this->purchaseReturnInstance) {
            $this->dueAmount = $this->receivableAmount - $this->purchaseReturnInstance->received_amount;
        } else {
            $this->dueAmount = $this->receivableAmount;
        }
    }

    public function saveReturn()
    {
        $this->validate(); // Run full validation

        $this->totalPriceFromProducts = 0; // Recalculate for save
        foreach ($this->products as $item) {
            $productUnit = strtolower($item['unit_name'] ?? 'pcs');
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                $this->totalPriceFromProducts += ($item['net_weight'] ?? 0) * ($item['price'] ?? 0);
            } else {
                $this->totalPriceFromProducts += ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
            }
        }

        if ($this->discount > $this->totalPriceFromProducts) {
            $this->addError('discount', 'Discount amount mustn\'t be greater than total price.');
            return;
        }

        $hasReturnableItems = false;
        foreach ($this->products as $pData) {
            $productUnit = strtolower($pData['unit_name'] ?? 'pcs');
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                if (($pData['net_weight'] ?? 0) > 0) {
                    $hasReturnableItems = true;
                    break;
                }
            } else {
                if (($pData['quantity'] ?? 0) > 0) {
                    $hasReturnableItems = true;
                    break;
                }
            }
        }

        if (!$hasReturnableItems && !$this->editMode) {
            $this->addError('products', 'At least one product must have a return quantity/weight greater than 0.');
            return;
        }

        $currentPurchaseReturn = $this->purchaseReturnInstance ?? new PurchaseReturn();

        // Pre-save stock and purchase quantity checks
        foreach ($this->products as $index => $productData) {
            $requestedProduct = (object) $productData;
            $productUnit = strtolower($requestedProduct->unit_name ?? 'pcs');
            $originalPurchaseDetail = $this->purchase->purchaseDetails->where('product_id', $requestedProduct->product_id)->first();
            $currentStock = ProductStock::where('warehouse_id', $this->purchase->warehouse_id)
                ->where('product_id', $requestedProduct->product_id)
                ->first();

            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                if (($requestedProduct->net_weight ?? 0) > ($originalPurchaseDetail->net_weight ?? 0)) {
                    $this->addError("products.{$index}.net_weight", "Cannot return more weight of {$requestedProduct->name} than purchased.");
                    return;
                }
                if ($currentStock && ($requestedProduct->net_weight ?? 0) > ($currentStock->net_weight ?? 0)) {
                    $this->addError("products.{$index}.net_weight", "Return weight for {$requestedProduct->name} exceeds current stock weight ({$currentStock->net_weight} kg).");
                    return;
                }
            } else {
                if (($requestedProduct->quantity ?? 0) > ($originalPurchaseDetail->quantity ?? 0)) {
                    $this->addError("products.{$index}.quantity", "Cannot return more units of {$requestedProduct->name} than purchased.");
                    return;
                }
                if ($currentStock && ($requestedProduct->quantity ?? 0) > ($currentStock->quantity ?? 0)) {
                    $this->addError("products.{$index}.quantity", "Return quantity for {$requestedProduct->name} exceeds current stock ({$currentStock->quantity}).");
                    return;
                }
            }
        }

        $currentPurchaseReturn->purchase_id       = $this->purchase->id;
        $currentPurchaseReturn->supplier_id       = $this->purchase->supplier_id;
        $currentPurchaseReturn->return_date       = Carbon::parse($this->return_date);
        $currentPurchaseReturn->total_price       = $this->totalPriceFromProducts;
        $currentPurchaseReturn->discount_amount   = $this->discount;
        $currentPurchaseReturn->fine = $this->fine;
        $currentPurchaseReturn->return_expense = $this->return_expense;

        $currentPurchaseReturn->receivable_amount = $this->receivableAmount;

        if ($this->editMode) {
            $currentPurchaseReturn->due_amount = $this->receivableAmount - $currentPurchaseReturn->received_amount;
        } else {
            $currentPurchaseReturn->received_amount = 0;
            $currentPurchaseReturn->due_amount = $this->receivableAmount;
        }
        $currentPurchaseReturn->note = $this->note;
        $currentPurchaseReturn->save();

        if ($this->purchase->return_status == Status::NO && $hasReturnableItems) {
            $this->purchase->return_status = Status::YES;
            $this->purchase->save();
        } // Add logic for setting to NO if all items removed on edit

        $processedDetailIds = [];

        foreach ($this->products as $productData) {
            $productItem = (object) $productData;
            $productUnit = strtolower($productItem->unit_name ?? 'pcs');
            $returnDetail = PurchaseReturnDetails::where('purchase_return_id', $currentPurchaseReturn->id)
                ->where('product_id', $productItem->product_id)
                ->first();

            $oldReturnQuantity = $returnDetail ? $returnDetail->quantity : 0;
            $newReturnQuantity = $productItem->quantity ?? 0;
            $quantityChangeForStockAdjustment = $newReturnQuantity - $oldReturnQuantity;

            $oldReturnWeight = $returnDetail ? ($returnDetail->net_weight ?? 0) : 0;
            $newReturnWeight = $productItem->net_weight ?? 0;
            $weightChangeForStockAdjustment = $newReturnWeight - $oldReturnWeight;

            $itemIsBeingReturned = false;
            if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                if ($newReturnWeight > 0) $itemIsBeingReturned = true;
                $currentDetailTotal = $newReturnWeight * ($productItem->price ?? 0);
            } else {
                if ($newReturnQuantity > 0) $itemIsBeingReturned = true;
                $currentDetailTotal = $newReturnQuantity * ($productItem->price ?? 0);
            }


            if ($itemIsBeingReturned) {
                if (!$returnDetail) {
                    $returnDetail = new PurchaseReturnDetails();
                }
                $returnDetail->purchase_return_id = $currentPurchaseReturn->id;
                $returnDetail->product_id         = $productItem->product_id;
                $returnDetail->quantity           = $newReturnQuantity;
                $returnDetail->net_weight         = $newReturnWeight;
                $returnDetail->price              = $productItem->price;
                $returnDetail->total              = $currentDetailTotal; // Use the calculated total for this item
                $returnDetail->save();
                $processedDetailIds[] = $returnDetail->id;
            } elseif ($returnDetail) { // If not being returned but detail existed, mark for cleanup
                // Deletion logic below will handle stock adjustment
            }

            // Update stock: For purchase returns, stock DECREASES
            $stockToUpdate = ProductStock::where('warehouse_id', $this->purchase->warehouse_id)
                ->where('product_id', $productItem->product_id)
                ->first();

            if ($stockToUpdate) {
                if ($productUnit === 'kg' || $productUnit === 'kilogram') {
                    if ($weightChangeForStockAdjustment != 0) {
                        $stockToUpdate->net_weight = ($stockToUpdate->net_weight ?? 0) - $weightChangeForStockAdjustment;
                        // If quantity represents bags/units, adjust it too if weight change implies full unit change
                        // This part can be complex depending on how you track KG items (e.g., 1 bag of 25kg)
                        // For simplicity, if quantity is just '1' for a bulk KG item, it might not change unless net_weight becomes 0.
                        // If quantity for KG represents distinct units (e.g. 2 bags of X kg each), and one is returned, quantity should also decrease.
                        // Let's assume for now quantity is also adjusted if related weight is adjusted.
                        if ($quantityChangeForStockAdjustment != 0) $stockToUpdate->quantity -= $quantityChangeForStockAdjustment;
                    }
                } else { // PCS items
                    if ($quantityChangeForStockAdjustment != 0) {
                        $stockToUpdate->quantity -= $quantityChangeForStockAdjustment;
                    }
                }
                $stockToUpdate->save();
            }
        }

        if ($this->editMode) {
            $detailsToClean = PurchaseReturnDetails::where('purchase_return_id', $currentPurchaseReturn->id)
                ->whereNotIn('id', $processedDetailIds)
                ->get();

            foreach ($detailsToClean as $detailToClean) {
                $stockToAdjust = ProductStock::where('warehouse_id', $this->purchase->warehouse_id)
                    ->where('product_id', $detailToClean->product_id)
                    ->first();
                if ($stockToAdjust) {
                    $productUnitClean = strtolower($detailToClean->product->unit->name ?? 'pcs');
                    if ($productUnitClean === 'kg' || $productUnitClean === 'kilogram') {
                        $stockToAdjust->net_weight = ($stockToAdjust->net_weight ?? 0) + ($detailToClean->net_weight ?? 0); // Add back
                        // Adjust quantity if applicable
                        $stockToAdjust->quantity += $detailToClean->quantity; // Add back associated quantity
                    } else {
                        $stockToAdjust->quantity += $detailToClean->quantity; // Add back
                    }
                    $stockToAdjust->save();
                }
                $detailToClean->delete();
            }
            // After cleaning, check if original purchase status needs to revert to NO_RETURN
            $remainingActiveDetails = PurchaseReturnDetails::where('purchase_return_id', $currentPurchaseReturn->id)->exists();
            if (!$remainingActiveDetails && $this->purchase->return_status == Status::YES) {
                $this->purchase->return_status = Status::NO;
                $this->purchase->save();
                // Optionally delete the $currentPurchaseReturn itself if it's now completely empty
                // $currentPurchaseReturn->delete();
            }
        }


        Action::newEntry($currentPurchaseReturn, $this->editMode ? 'UPDATED' : 'CREATED');

        session()->flash('notify', ['success', 'Purchase return data ' . ($this->editMode ? 'updated' : 'added') . ' successfully.']);
        return redirect()->route('admin.purchase.return.edit', $currentPurchaseReturn->id);
    }

    public function render()
    {
        return view('livewire.admin.purchase-management.all-return-purchases');
    }
}
