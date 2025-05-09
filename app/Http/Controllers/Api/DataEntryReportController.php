<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{
    Action, Adjustment, Customer, CustomerPayment,
    Expense, Product, Purchase, PurchaseReturn,
    Sale, SaleReturn, Supplier, SupplierPayment, Transfer
};
use Illuminate\Http\Request;

class DataEntryReportController extends Controller
{
    private $relations = [];
    private $model;

    public function product(Request $request)
    {
        $this->model = Product::class;
        return $this->entries($request);
    }

    public function customer(Request $request)
    {
        $this->model = Customer::class;
        return $this->entries($request);
    }

    public function supplier(Request $request)
    {
        $this->model = Supplier::class;
        return $this->entries($request);
    }

    public function purchase(Request $request)
    {
        $this->model = Purchase::class;
        return $this->entries($request);
    }

    public function purchaseReturn(Request $request)
    {
        $this->model = PurchaseReturn::class;
        $this->relations = ['actionable.purchase'];
        return $this->entries($request);
    }

    public function sale(Request $request)
    {
        $this->model = Sale::class;
        return $this->entries($request);
    }

    public function saleReturn(Request $request)
    {
        $this->model = SaleReturn::class;
        $this->relations = ['actionable.sale'];
        return $this->entries($request);
    }

    public function adjustment(Request $request)
    {
        $this->model = Adjustment::class;
        return $this->entries($request);
    }

    public function transfer(Request $request)
    {
        $this->model = Transfer::class;
        return $this->entries($request);
    }

    public function expense(Request $request)
    {
        $this->model = Expense::class;
        $this->relations = ['actionable.expenseType'];
        return $this->entries($request);
    }

    public function supplierPayment(Request $request)
    {
        $this->model = SupplierPayment::class;
        $this->relations = ['actionable.supplier'];
        return $this->entries($request);
    }

    public function customerPayment(Request $request)
    {
        $this->model = CustomerPayment::class;
        $this->relations = ['actionable.customer'];
        return $this->entries($request);
    }

    private function entries($type)
    {

        $entries    = Action::where('actionable_type', $this->model)->with('actionable', 'admin');
        if (count($this->relations)) {
            $entries->with($this->relations);
        }
        $entries = $entries->latest()->get();
        return response()->json([
            'data' => $entries,

        ]);

    }
}
