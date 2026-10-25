<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model; 

	class Role extends Model
	{
		use HasFactory;
		 
		protected $fillable = [
			'id',
			'user_id',
			'name',
			'status',
			'created_at',
			'updated_at'
		];  
		  
		public function user()
		{
			return $this->belongsTo(Admin::class, 'user_id');
		}
	
		public function roleGroups()
		{
			return $this->hasMany(RoleGroup::class, 'role_id');
		}
	}
