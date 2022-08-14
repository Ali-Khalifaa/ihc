<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TraineesPayment extends Model
{
    protected $fillable =[
        'amount',
        'lead_id',
        'seals_man_id',
        'accountant_id',
        'treasury_id',
        'product_name',
        'product_type',
        'type',
    ];

    //relations

    public function lead(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lead::class,'lead_id');
    }

    public function sealsMan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class,'seals_man_id');
    }

    public function accountant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class,'accountant_id');
    }

    public function treasury(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Treasury::class,'treasury_id');
    }

    public function treasuryNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TreasuryNotes::class);
    }
}
