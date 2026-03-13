<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
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
        'code',
        'label',
        'description',
        'scope',
    ];

    /**
     * Toutes les permissions associées à ce rôle
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Tous les utilisateurs ayant ce rôle (via user_annexes)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_annexes')
            ->withPivot(['annexe_id', 'is_principal', 'assigned_by', 'assigned_at', 'end_at']);
    }

    /**
     * Vérifier si ce rôle a une permission spécifique
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $this->permissions()->where('code', $permissionCode)->exists();
    }

    /**
     * Attribuer des permissions à ce rôle
     */
    public function givePermissions(array $permissionIds): void
    {
        $this->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Retirer des permissions de ce rôle
     */
    public function revokePermissions(array $permissionIds): void
    {
        $this->permissions()->detach($permissionIds);
    }

    /**
     * Synchroniser les permissions (remplace toutes les permissions existantes)
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Vérifier si le rôle est de scope institution
     */
    public function isInstitutionScope(): bool
    {
        return $this->scope === 'institution';
    }

    /**
     * Vérifier si le rôle est de scope annexe
     */
    public function isAnnexeScope(): bool
    {
        return $this->scope === 'annexe';
    }
}
