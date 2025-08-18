<?php

namespace App\Traits;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\DB;
use App\Traits\ReverseDailyBookEntryTrait;
use App\Traits\ReverseHandlesBankPayments;
use Carbon\Carbon;


trait ReversesCustomerTransaction
{

    use ReverseDailyBookEntryTrait, ReverseHandlesBankPayments; // <-- Use the new trait
    /**
     * Reverses a customer advance/payment transaction and recalculates subsequent balances.
     *
     * @param CustomerTransaction $transactionToReverse The transaction to be reversed.
     * @param string|null $reason The reason for the reversal.
     * @return void
     * @throws \Exception
     */
    protected function reverseCustomerTransaction(CustomerTransaction $transactionToReverse, ?string $reason = null)
    {
        // Prevent double reversal
        if ($transactionToReverse->reversed_at) {
            throw new \Exception('This transaction has already been reversed.');
        }

        DB::transaction(function () use ($transactionToReverse, $reason) {
            $customerId = $transactionToReverse->customer_id;
            $transId = $transactionToReverse->id;

            $bankTransaction = BankTransaction::where('module_id', $transId)->where('data_model', 'CustomerTransaction')->first();
            if ($bankTransaction) {
                $bankTransaction->bank_id;
                $payment_method = $bankTransaction->payment_method;

                $traitTransactionType = 'debit';
                $traitTransactionTypeDay = 'credit';

                if ($bankTransaction->bank && $bankTransaction->bank->name === 'Cash') {
                    $amount_cash = $bankTransaction->amount ?? 0;
                    $amount_bank = 0;
                    $payment_method = 'cash';
                } else {
                    $amount_bank = $bankTransaction->amount ?? 0;
                    $amount_cash = 0;
                    $payment_method = 'bank';
                }

                $this->handlePaymentTransaction(
                    $payment_method,
                    $amount_cash,
                    $amount_bank,
                    $bankTransaction->bank_id,
                    $transId,
                    'CustomerTransaction',
                    $traitTransactionType // Dynamically set to 'credit' or 'debit'
                );
                $this->handleDailyBookEntries(
                    $amount_cash,
                    $amount_bank,
                    $traitTransactionTypeDay, // Dynamically set to 'credit' or 'debit'
                    $payment_method,
                    'CustomerTransaction',
                    $transId
                );
                $closing_b = $transactionToReverse->closing_balance += $bankTransaction->amount;
                $transactionToReverse->update([
                    'reversed_at'     => Carbon::now(),
                    'closing_balance' => $closing_b,
                    'reversal_reason' => $reason ?? 'Transaction reversed by user.',
                ]);
            }
        });
    }
}
