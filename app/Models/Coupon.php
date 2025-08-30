<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = ['code','type','value','is_active','usage_limit','used_count','single_use_per_customer'];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }
}
