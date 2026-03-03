<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LevelFee;

class StudyLevel extends Model
{
    /**
     * Retourne le niveau suivant dans la progression (order + 1).
     */
    public function nextLevel(): ?self
    {
        return self::where('order', $this->order + 1)->first();
    }

    protected $fillable = [
        'code',
        'order',
        'label',
        'description',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'study_level_id');
    }

    public function levelFees(): HasMany
    {
        return $this->hasMany(LevelFee::class);
    }
}
