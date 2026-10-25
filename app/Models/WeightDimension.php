<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'slab',
        'length',
        'width',
        'height',
        'status'
    ];
}
