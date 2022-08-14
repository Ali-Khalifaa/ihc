<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hubspot extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'hubspot_id',
        'create_date',
        'last_modified_date',
    ];
}
