<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleGroup extends Model
{
    use HasFactory;
	protected $fillable = ['role_id', 'name', 'value'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
