<?php

namespace App;

use App\PetActivityStream;
use Illuminate\Database\Eloquent\Model;

class ActivityStreamPhoto extends Model
{
	public function images()
	{
		return $this->belongsTo(PetActivityStream::class);
	}
}