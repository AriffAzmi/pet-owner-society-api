<?php

namespace App;

use App\ActivityStreamPhoto;
use Illuminate\Database\Eloquent\Model;

class PetActivityStream extends Model
{
	public function images()
	{
		return $this->hasMany(ActivityStreamPhoto::class);
	}
}