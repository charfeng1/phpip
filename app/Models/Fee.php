<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasTableComments;

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function country()
    {
        return $this->belongsTo(Country::class, 'for_country');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'for_category', 'code');
    }

    public function origin()
    {
        return $this->belongsTo(Country::class, 'for_origin', 'iso');
    }
}
