<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\DailyBookDetail;

trait StockServicePaymentsTrait
{
    public function handleServicePayments($customer_id,$amount, $source, $bank_id)
    {

        $lastTransaction = CustomerTransaction::where('customer_id', $customer_id) ->orderBy('id', 'desc')->first();
        // Check if it's the first transaction
        if ($lastTransaction) {
            $openingBalance = $lastTransaction->closing_balance;
        } else {
            $customer = Customer::findOrFail($customer_id);
            $openingBalance = $customer->opening_balance ?? 0.00;
        }

        $debitAmount = $amount;
        $creditAmount = 0.00;
        $closingBalance = $openingBalance + $debitAmount;
        $customerTransaction = new CustomerTransaction();
        $customerTransaction->customer_id      = $customer_id;
        $customerTransaction->credit_amount    = $creditAmount;
        $customerTransaction->debit_amount     = $debitAmount;
        $customerTransaction->opening_balance  = $openingBalance;
        $customerTransaction->closing_balance  = $closingBalance;
        $customerTransaction->source           = $source;
        $customerTransaction->bank_id          = $bank_id;
        $customerTransaction->save();
    }
}
