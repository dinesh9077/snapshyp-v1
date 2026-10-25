<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
	protected $fillable = [
		'user_id',
		'task_id',
		'role',
		'type',
		'text',
		'read_at',
		'created_at',
		'updated_at'
	]; 
}
