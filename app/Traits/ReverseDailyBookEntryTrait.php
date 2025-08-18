<?php

// app/Traits/DailyBookEntryTrait.php
namespace App\Traits;


use App\Models\DailyBookDetail;

trait ReverseDailyBookEntryTrait
{
    public function handleDailyBookEntries($amountPaid,$amountPaidBank,$transaction_type,$payment_method,$dataModel_name,$dataModel_id)
    {

        ////////////////////////////   Daily Book Entry ////////////////////////////

        $dailyBook_balance = 0;
        $dailyBook_opening = 0;
        $dailyBook_closing = 0;


        // Initialize today's DailyBook records
        $dailydetails = DailyBookDetail::whereDate('date', '=', now()->format('Y-m-d'))
            ->orderBy('date', 'desc')
            ->get();

        // DailyBook opening/closing
        if ($dailydetails->isEmpty()) {
            $dailyBook_opening = $this->lastDayClosingBalance();
            $dailyBook_closing = $dailyBook_opening;
        } else {
            $dailyBook_opening = $dailydetails->first()?->opening_balance ?? 0;
            $dailyBook_closing = $dailydetails->last()?->closing_balance ?? 0;
        }

        // Calculate updated balances
        if ($transaction_type == 'debit') {
            if ($payment_method == 'both') {
                $dailyBook_balance = $dailyBook_closing + $amountPaid + $amountPaidBank;
            } else if ($payment_method == 'cash') {
                $dailyBook_balance = $dailyBook_closing + $amountPaid;
            } else {
                $dailyBook_balance = $dailyBook_closing + $amountPaidBank;
            }
        } else {
            if ($payment_method == 'both') {
                $dailyBook_balance = $dailyBook_closing - $amountPaid - $amountPaidBank;
            } else if ($payment_method == 'cash') {
                $dailyBook_balance = $dailyBook_closing - $amountPaid;
            } else {
                $dailyBook_balance = $dailyBook_closing - $amountPaidBank;
            }
        }

        $dailyBook_dataModelId = $dataModel_id;

        // DailyBook Entry
        if (
            $payment_method == 'bank' ||
            $payment_method == 'cash' ||
            $payment_method == 'both'
        ) {
            $dailyEntry = new DailyBookDetail();
            $dailyEntry->date = now()->format('Y-m-d');

            switch ($payment_method) {
                case 'bank':
                    $dailyEntry->source = 'Reverse '.$dataModel_name.' (Bank)';
                    $finalAmount = $amountPaidBank;
                    break;
                case 'cash':
                    $dailyEntry->source = 'Reverse '.$dataModel_name.' (Cash)';
                    $finalAmount = $amountPaid;
                    break;
                case 'both':
                    $dailyEntry->source = 'Reverse '.$dataModel_name.' (Both-Bank)';
                    $finalAmount = $amountPaidBank;
                    break;
                default:
                    $dailyEntry->source = 'Reverse '.$dataModel_name.' (Unpaid)';
                    $finalAmount = 0;
            }


            $dailyEntry->debit = $transaction_type == 'debit' ? $finalAmount : null;
            $dailyEntry->credit = $transaction_type == 'debit' ? null : $finalAmount;
            $dailyEntry->balance = $dailyBook_balance;
            $dailyEntry->opening_balance = $dailyBook_opening;
            $dailyEntry->closing_balance = $dailyBook_balance;
            $dailyEntry->module_id = $dailyBook_dataModelId;
            $dailyEntry->data_model = $dataModel_name;
            $dailyEntry->save();

            // Extra entry for both (cash part)
            if ($payment_method == 'both') {
                $dailyEntryExtra = new DailyBookDetail();
                $dailyEntryExtra->date = now()->format('Y-m-d');
                $dailyEntryExtra->source =  $dataModel_name.' (Both-Cash)';
                $dailyEntryExtra->debit = $transaction_type == 'debit' ? $amountPaid : null;
                $dailyEntryExtra->credit = $transaction_type == 'debit' ? null : $amountPaid;
                $dailyEntryExtra->balance = $dailyBook_balance;
                $dailyEntryExtra->opening_balance = $dailyBook_opening;
                $dailyEntryExtra->closing_balance = $dailyBook_balance;
                $dailyEntryExtra->module_id = $dailyBook_dataModelId;
                $dailyEntryExtra->data_model = $dataModel_name;
                $dailyEntryExtra->save();
            }
        }

        ////////////////////////////   Daily Book Entry End ////////////////////////////
    }
    private function lastDayClosingBalance()
    {

            $last = DailyBookDetail::whereDate('date', '<', now()->format('Y-m-d'))
                ->orderBy('id', 'desc')
                ->orderBy('date', 'desc')
                ->first();

            return $last?->closing_balance ;

    }
}
