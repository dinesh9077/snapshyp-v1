<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodRemittanceHoliday extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'description'];

    protected $casts = [
        'date' => 'date',
    ];
}
