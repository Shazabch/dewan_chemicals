<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasFactory, HasApiTokens;
    use GlobalStatus;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function badgeData()
    {
        $html = '';
        if ($this->status == Status::ENABLE) {
            $html = '<span class="badge badge--success">' . trans('Active') . '</span>';
        } else {
            $html = '<span><span class="badge badge--warning">' . trans('Banned') . '</span></span>';
        }
        return $html;
    }
    public function salaries(): HasMany
    {

        return $this->hasMany(Salary::class, 'staff_id')->orderBy('pay_period_start', 'desc');
    }
}
