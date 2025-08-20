<?php
// app/Models/Salary.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'staff_id',
        'pay_period_start',
        'pay_period_end',
        'base_salary',
        'allowances',
        'deductions',
        'gross_salary',
        'net_salary',
        'status',
        'payment_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allowances' => 'array',
        'deductions' => 'array',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
    ];

    /**
     * Get the user (staff member) that this salary belongs to.
     */
    public function user(): BelongsTo
    {
        // We specify 'staff_id' as the foreign key because it doesn't follow
        // the Laravel convention of 'user_id'.
        return $this->belongsTo(Admin::class, 'staff_id');
    }
}