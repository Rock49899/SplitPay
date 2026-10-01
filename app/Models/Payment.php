<?php

namespace App\Models;

use App\Models\Concerns\ScopedByAnnexe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids, ScopedByAnnexe;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'installment_id',
        'payment_link_id',
        'student_id',
        'amount',
        'method', // mtn|moov
        'payer_name',
        'payer_email',
        'payer_phone',
        'reference',
        'payplus_transaction_id',
        'status',
        'metadata',
        'paid_at',
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

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class, 'payment_link_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    /**
     * Chaque utilisateur ne voit que les paiements des étudiants de ses annexes
     */
    protected static function booted()
    {
        static::addGlobalScope('annexe', function (Builder $query) {
            static::scopeToTenantAnnexesThroughStudent($query);
        });
    }
}
