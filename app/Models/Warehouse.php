<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'location', 'description'];

    public function tires()
    {
        return $this->hasMany(Tire::class);
    }
}
