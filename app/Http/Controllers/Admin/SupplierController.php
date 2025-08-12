<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Supplier;
use App\Traits\DailyBookEntryTrait;
use App\Traits\HandlesBankPayments;
use Illuminate\Http\Request;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    use HandlesBankPayments, DailyBookEntryTrait;
    protected $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'All Suppliers';
    }

    protected function getSuppliers()
    {
        return Supplier::searchable(['name', 'mobile', 'email', 'address'], true)->with('purchases', 'purchaseReturns')->orderBy('name', 'asc');
    }

    public function index()
    {
        $pageTitle = $this->pageTitle;
        $emptyMessage = 'No supplier found';
        $banks = \App\Models\Bank::where('name', '!=', 'Cash')->get();
        $suppliers = $this->getSuppliers()->paginate(getPaginate());
        return view('admin.supplier.index', compact('pageTitle', 'suppliers', 'banks'));
    }
    // ===================================================================
    // ADVANCE PAYMENT LOGIC FOR SUPPLIERS
    // ===================================================================

    public function storeAdvance(Request $request)
    {
        $paymentMethod = $request->payment_method;
        $transactionType = $request->transaction_type;

        // Add the new field to validation
        $rules = [
            'supplier_id'      => 'required|exists:suppliers,id',
            'transaction_type' => 'required|in:payment,receive', // Money Out or Money In
            'payment_method'   => 'required|in:cash,bank,both',
            'remarks'          => 'nullable|string|max:255',
            'amount_cash'      => [Rule::requiredIf(fn() => in_array($paymentMethod, ['cash', 'both'])), 'nullable', 'numeric', 'min:0'],
            'amount_bank'      => [Rule::requiredIf(fn() => in_array($paymentMethod, ['bank', 'both'])), 'nullable', 'numeric', 'min:0'],
            'bank_id'          => [Rule::requiredIf(fn() => in_array($paymentMethod, ['bank', 'both'])), 'nullable', 'exists:banks,id'],
        ];
        $request->validate($rules);

        $supplierId = $request->supplier_id;
        $amount_cash = $request->amount_cash ?? 0;
        $amount_bank = $request->amount_bank ?? 0;
        $total_amount = $amount_cash + $amount_bank;

        if ($total_amount <= 0) {
            $notify[] = ['error', 'Total amount must be greater than zero.'];
            return back()->withNotify($notify)->withInput();
        }

        try {
            $supplierTransaction = DB::transaction(function () use ($supplierId, $total_amount, $transactionType, $request) {

                $lastTransaction = \App\Models\SupplierTransaction::where('supplier_id', $supplierId)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                $openingBalance = $lastTransaction ? $lastTransaction->closing_balance : \App\Models\Supplier::findOrFail($supplierId)->opening_balance ?? 0.00;

                // --- [NEW] Dynamic Logic Based on Transaction Type ---
                if ($transactionType == 'payment') { // Money Out (Paying a bill/advance)
                    $creditAmount = $total_amount;
                    $debitAmount = 0.00;
                    $source = 'Payment to Supplier';
                    $closingBalance = $openingBalance - $creditAmount;
                } else { // 'receive' -> Money In (Receiving a refund)
                    $debitAmount = $total_amount;
                    $creditAmount = 0.00;
                    $source = 'Refund from Supplier';
                    $closingBalance = $openingBalance + $debitAmount;
                }

                return \App\Models\SupplierTransaction::create([
                    'supplier_id'     => $supplierId,
                    'credit_amount'   => $creditAmount,
                    'debit_amount'    => $debitAmount,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'source'          => $source,
                    'bank_id'         => $request->bank_id,

                ]);
            });

            // --- [NEW] Dynamic Logic for Traits ---
            $traitTransactionType = ($transactionType == 'payment') ? 'credit' : 'debit';
            // 'credit' because money is LEAVING your accounts
            // 'debit' because money is ENTERING your accounts

            $this->handlePaymentTransaction(
                $request->payment_method,
                $amount_cash,
                $amount_bank,
                $request->bank_id,
                $supplierTransaction->id,
                'SupplierTransaction',
                $traitTransactionType
            );

            $this->handleDailyBookEntries(
                $amount_cash,
                $amount_bank,
                $traitTransactionType,
                $request->payment_method,
                'SupplierTransaction',
                $supplierTransaction->id
            );

            $notify[] = ['success', 'Transaction recorded successfully.'];
            return back()->withNotify($notify);
        } catch (\Exception $e) {
            $notify[] = ['error', 'An error occurred: ' . $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }
    }
    public function supplierPDF()
    {
        $pageTitle = $this->pageTitle;
        $suppliers = $this->getSuppliers()->get();
        return downloadPDF('pdf.supplier.list', compact('pageTitle', 'suppliers'));
    }

    public function supplierCSV()
    {
        $pageTitle = $this->pageTitle;
        $filename  = $this->downloadCsv($pageTitle, $this->getSuppliers()->get());
        return response()->download(...$filename);
    }

    protected function downloadCsv($pageTitle, $data)
    {
        $filename = "assets/files/csv/example.csv";
        $myFile   = fopen($filename, 'w');
        $column   = "name,email,mobile,company_name,address\n";
        $curSym   = gs('cur_sym');
        foreach ($data as $supplier) {
            // $payable    = $curSym . getAmount($supplier->totalPayableAmount());
            // $receivable = $curSym . getAmount($supplier->totalReceivableAmount());

            // Remove commas from company_name
            $cleanCompanyName = str_replace(',', '', $supplier->company_name);
            $cleanAddress = str_replace(',', '', $supplier->address);

            $column .= "$supplier->name,$supplier->email,$supplier->mobile,$cleanCompanyName,$cleanAddress\n";
        }

        fwrite($myFile, $column);
        $headers = [
            'Content-Type' => 'application/csv',
        ];
        $name  = $pageTitle . time() . '.csv';
        $array = [$filename, $name, $headers];
        return $array;
    }

    public function store(Request $request, $id = 0)
    {
        $this->validation($request, $id);
        if ($id) {
            $notification = 'Supplier updated successfully';
            $supplier     = Supplier::findOrFail($id);
        } else {
            // $exist = Supplier::where('mobile', $request->mobile)->first();
            // if ($exist) {
            //     $notify[] = ['error', 'The mobile number already exists'];
            //     return back()->withNotify($notify);
            // }
            $notification = 'Supplier added successfully';
            $supplier     = new Supplier();
        }

        $this->saveSupplier($request, $supplier, $id);
        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->delete();
        Action::newEntry($supplier, 'DELETED');

        $notify[] = ['success', 'Supplier deleted successfully'];
        return back()->withNotify($notify);
    }
    protected function saveSupplier($request, $supplier, $id)
    {
        $supplier->name         = $request->name;
        $supplier->email        = strtolower(trim($request->email));
        $supplier->mobile       = $request->mobile;
        $supplier->company_name = $request->company_name;
        $supplier->address      = $request->address;
        $supplier->opening_balance = $request->opening_balance ?? 0.00;
        $supplier->booklet_no = $request->booklet_no;
        $supplier->save();
        Action::newEntry($supplier, $id ? 'UPDATED' : 'CREATED');
    }

    protected function validation($request, $id = 0)
    {
        $request->validate([
            'name'         => 'required|string|max:40',
            'booklet_no'   => 'required|string|max:100',
            'email'        => 'nullable',
            'mobile'       => 'nullable',
            'company_name' => 'nullable|string|max:40',
            'address'      => 'nullable|string|max:500',
            'opening_balance' => 'nullable',
        ]);
    }

    public function import(Request $request)
    {
        $reqHeader    = ['name', 'email', 'mobile', 'company_name', 'address'];
        $importResult = importCSV($request, Supplier::class, $reqHeader, 'name');

        if ($importResult['data']) {
            $notify[] = ['success', $importResult['notify']];
        } else {
            $notify[] = ['error', 'No new data imported'];
        }
        return back()->withNotify($notify);
    }
}
