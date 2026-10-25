<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppBanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'banner_image',
        'status',
    ];

    public function getBannerImageUrlAttribute()
    {
        return $this->banner_image ? url('storage/' . $this->banner_image) : null;
    }
}
