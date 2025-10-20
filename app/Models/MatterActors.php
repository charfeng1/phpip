<?php

namespace App\Models;

use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

class MatterActors extends Model
{
    use HasTranslationsExtended;

    public $timestamps = false;

    // Define which attributes are translatable
    public $translatable = ['role_name'];

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function actor()
    {
        return $this->belongsTo(Actor::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_code');
    }

    public function company()
    {
        return $this->belongsTo(Actor::class, 'company_id');
    }
}
