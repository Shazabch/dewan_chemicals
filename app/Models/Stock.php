<?php

namespace App\Models;

use App\Traits\ActionTakenBy;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use ActionTakenBy;
    protected $guarded = [];
    protected static function booted()
    {
        // static::creating(function ($stock) {
        //     $stock->tracking_id = self::generateTrackingId($stock->stock_type);
        // });
    }

    public static function generateTrackingId($type)
    {

        if (!in_array($type, ['in', 'out'])) {
            $prefix = 'G-'; // Default to 'GIN-' if type is not recognized
        }
        $prefix = strtoupper($type) === 'OUT' ? 'GOUT-' : 'GIN-';

        // Get the latest tracking_id for this type
        $lastTracking = self::where('tracking_id', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('tracking_id');

        $lastNumber = 10000; // Default start

        if ($lastTracking && preg_match('/' . $prefix . '(\d+)/', $lastTracking, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix . ($lastNumber + 1);
    }


    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function stockInOuts()
    {
        return $this->hasMany(StockInOut::class);
    }
    public function stockIn()
    {
        return $this->hasMany(StockInOut::class)->where('stock_in_out_type', 'in');
    }
    public function stockOut()
    {
        return $this->hasMany(StockInOut::class)->where('stock_in_out_type', 'out');
    }
    public function user()
    {
        return $this->morphTo(__FUNCTION__, 'user_model', 'user_id');
    }
     public function userClient()
    {
        return $this->morphTo(__FUNCTION__, 'user_client_model', 'user_client_id');
    }
}
