<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory, HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'logo',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Toutes les annexes de cette institution
     */
    public function annexes(): HasMany
    {
        return $this->hasMany(Annexe::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $institution) {
            $institution->annexes()->delete();
        });
    }

    /**
     * Annexes actives uniquement
     */
    public function activeAnnexes(): HasMany
    {
        return $this->hasMany(Annexe::class)->where('is_active', true);
    }

    /**
     * Vérifier si l'institution est active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Désactiver l'institution et toutes ses annexes
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
        $this->annexes()->update(['is_active' => false]);
    }

    /**
     * Activer l'institution
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
}

