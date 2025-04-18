<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'account_number',
        'account_holder',
        'iban',
        'raast_id',
        'opening_balance',
        'current_balance',
    ];

}
