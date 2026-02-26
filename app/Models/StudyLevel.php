<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyLevel extends Model
{
    protected $fillable = [
        'code',
        'label',
        'description',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'study_level_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'study_level_id');
    }
}
