<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

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
     * @var list<string>
     */
    protected $fillable = [
        'annexe_id',
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'scope',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Annexe principale de l'utilisateur
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class, 'annexe_id');
    }

    /**
     * Toutes les annexes accessibles par l'utilisateur (via user_annexes)
     */
    public function annexes(): BelongsToMany
    {
        return $this->belongsToMany(Annexe::class, 'user_annexes')
            ->withPivot(['role_id', 'is_principal', 'assigned_by', 'assigned_at', 'end_at'])
            ->withTimestamps();
    }

    /**
     * Tous les rôles de l'utilisateur (via user_annexes)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_annexes')
            ->withPivot(['annexe_id', 'is_principal', 'assigned_by', 'assigned_at', 'end_at'])
            ->withTimestamps();
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionCode) {
                $query->where('code', $permissionCode);
            })
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('code', $roleCode)->exists();
    }

    /**
     * Vérifier si l'utilisateur est super admin institution
     */
    public function isSuperAdminInstitution(): bool
    {
        return $this->scope === 'institution' && $this->hasRole('super_admin_institution');
    }

    /**
     * Vérifier si l'utilisateur est super admin annexe
     */
    public function isSuperAdminAnnexe(): bool
    {
        return $this->scope === 'annexe' && $this->hasRole('super_admin_annexe');
    }
}
