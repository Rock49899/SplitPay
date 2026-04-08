<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LevelFee;

class Specialization extends Model
{
    protected $fillable = [
        'annexe_id',
        'code',
        'label',
        'description',
    ];

    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'specialization_id');
    }

    public function levelFees(): HasMany
    {
        return $this->hasMany(LevelFee::class);
    }
}
