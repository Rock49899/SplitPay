<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LevelFee;

class StudyLevel extends Model
{
    /**
     * Retourne le niveau suivant dans la progression (order + 1).
     */
    public function nextLevel(): ?self
    {
        return self::where('annexe_id', $this->annexe_id)
            ->where('order', $this->order + 1)
            ->first();
    }

    protected $fillable = [
        'annexe_id',
        'code',
        'order',
        'label',
        'description',
    ];

    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'study_level_id');
    }

    public function levelFees(): HasMany
    {
        return $this->hasMany(LevelFee::class);
    }
}
