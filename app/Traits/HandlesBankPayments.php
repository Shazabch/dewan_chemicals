<?php

namespace App\Traits;

use App\Models\Bank;
use App\Models\BankTransaction;

trait HandlesBankPayments
{
    public function handlePaymentTransaction($method, $cashAmount, $bankAmount, $bankId, $dataModelId, $dataModelName, $transaction_type)
    {
        if ($method === 'cash' || $method === 'both') {
            $cashBank = Bank::where('name', 'Cash')->first();
            if ($cashBank) {
                $this->handleBankTransaction(
                    $cashBank->id,
                    $cashAmount,
                    $dataModelId,
                    $dataModelName,
                    $method === 'both' ? 'Cash Payment for ' . $dataModelName . ' (Both)' : 'Cash Payment ' . $dataModelName,
                    $transaction_type,
                );
            }
        }

        if ($method === 'bank' || $method === 'both') {
            $selectedBank = Bank::find($bankId);
            if ($selectedBank) {
                $this->handleBankTransaction(
                    $selectedBank->id,
                    $bankAmount,
                    $dataModelId,
                    $dataModelName,
                    $method === 'both' ? 'Bank Payment for ' . $dataModelName . ' (Both)' : 'Bank Payment ' . $dataModelName,
                    $transaction_type,
                );
            }
        }
        if ($method === 'transfer') {
            $tobank = Bank::find($bankId);
            $frombank = Bank::find($dataModelId);
            if ($tobank) {
                $source = 'Bank Transfer From ' . $frombank->name;
                $this->handleBankTransaction($tobank->id, $bankAmount, $frombank->id, $dataModelName, $source, 'debit');
            }
            if ($frombank) {
                $source = 'Bank Transfer To ' . $tobank->name;
                $this->handleBankTransaction($frombank->id, $bankAmount, $tobank->id, $dataModelName, $source, 'credit');
            }
        }
    }
    public function handleBankTransaction($bankId, $amount, $moduleId, $modelName, $source, $transaction_type)
    {
        if (!$bankId || $amount <= 0) {
            return;
        }

        $bank = Bank::find($bankId);
        if (!$bank) return;

        $lastTransaction = BankTransaction::where('bank_id', $bankId)->latest()->first();
        $opening = $lastTransaction ? $lastTransaction->closing_balance : ($bank->opening_balance ?? 0.00);

        $transaction = new BankTransaction();
        $transaction->bank_id = $bankId;
        $transaction->module_id = $moduleId;
        $transaction->data_model = $modelName;
        $transaction->source = $source;
        if ($transaction_type == 'debit') {
            $closing = $opening + $amount;
            $transaction->credit = 0.00;
            $transaction->debit = $amount;
        } else {
            $closing = $opening - $amount;
            $transaction->debit = 0.00;
            $transaction->credit = $amount;
        }
        $transaction->amount = $amount;
        $transaction->opening_balance = $opening;
        $transaction->closing_balance = $closing;
        $transaction->transactable_type = $modelName;
        $transaction->transactable_id = $moduleId;
        $transaction->save();
        $bank->current_balance = $closing;
        $bank->save();
    }
}
