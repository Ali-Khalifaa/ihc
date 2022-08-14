<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable =[
        'sales_amount',
        'from_date',
        'to_date',
        'automatically_division',
        'comission_management_id',
    ];

    //relation

    public function comissionManagement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ComissionManagement::class,'comission_management_id');
    }

    public function targetEmployees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TargetEmployees::class);
    }

}
