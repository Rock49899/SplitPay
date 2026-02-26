<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialization extends Model
{
    protected $fillable = [
        'code',
        'label',
        'description',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'specialization_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'specialization_id');
    }
}
