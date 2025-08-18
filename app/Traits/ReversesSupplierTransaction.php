<?php

namespace App\Traits;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\DB;
use App\Traits\ReverseDailyBookEntryTrait;
use App\Traits\ReverseHandlesBankPayments;
use Carbon\Carbon;


trait ReversesSupplierTransaction
{

    use ReverseDailyBookEntryTrait, ReverseHandlesBankPayments; // <-- Use the new trait

    protected function reverseSupplierTransaction(SupplierTransaction $transactionToReverse, ?string $reason = null)
    {
        // Prevent double reversal
        if ($transactionToReverse->reversed_at) {
            throw new \Exception('This transaction has already been reversed.');
        }

        DB::transaction(function () use ($transactionToReverse, $reason) {
            $transId = $transactionToReverse->id;
            $bankTransaction = BankTransaction::where('module_id', $transId)->where('data_model', 'SupplierTransaction')->first();
            if ($bankTransaction) {
                $bankTransaction->bank_id;
                $payment_method = $bankTransaction->payment_method;

                $traitTransactionType = 'credit';
                $traitTransactionTypeDay = 'debit';

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
                    'SupplierTransaction',
                    $traitTransactionType
                );
                $this->handleDailyBookEntries(
                    $amount_cash,
                    $amount_bank,
                    $traitTransactionTypeDay,
                    $payment_method,
                    'SupplierTransaction',
                    $transId
                );
                $closing_b = $transactionToReverse->closing_balance -= $bankTransaction->amount;
                $transactionToReverse->update([
                    'reversed_at'     => Carbon::now(),
                    'closing_balance' => $closing_b,
                    'reversal_reason' => $reason ?? 'Transaction reversed by user.',
                ]);
            }
        });
    }
}
