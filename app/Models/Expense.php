<?php

namespace App\Models;

use App\Traits\ActionTakenBy;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use ActionTakenBy;
    protected $guarded = [];

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function dataModel()
    {
        return $this->morphTo(__FUNCTION__, 'data_model','model_id');
    }
    public function getDataModelNameAttribute()
    {
        return $this->data_model ? class_basename($this->data_model) : null;
    }
    public function getDataModelIdAttribute()
    {
        return $this->data_model ? $this->data_model->invoice_no : null;
    }
    //// Get Invoice NO
    public function getInvoiceNoAttribute()
    {
        return $this->data_model ? $this->data_model->invoice_no : null;
    }
}
