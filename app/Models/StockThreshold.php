<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'size',
        'min_quantity',
        'alert_email',
    ];
}
