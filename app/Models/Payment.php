<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'installment_id',
        'reference',
        'amount',
        'method',
        'status',
        'payplus_transaction_id',
        'payer_name',
        'payer_email',
        'payer_phone',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'json',
        'paid_at' => 'datetime',
    ];


    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function paymentLink()
    {
       return $this->installment->paymentLink();
    }

}
