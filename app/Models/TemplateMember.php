<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Model;

class TemplateMember extends Model
{
    use HasTableComments;

    protected $guarded = ['created_at', 'updated_at'];

    public function class()
    {
        return $this->belongsTo(TemplateClass::class);
    }
}
